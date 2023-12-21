<?php

namespace App\Services\Integration\Transport;

use App\Enums\OrderSystemStatus;
use App\Events\System\SystemFinalPriceNoEanException;
use App\Exceptions\Integration\Imap\ManyAttachmentsException;
use App\Exceptions\Integration\Imap\NoAttachmentsException;
use App\Exceptions\Integration\Imap\NoXlsAttachmentsException;
use App\Exceptions\Integration\Imap\WrongFormatAttachmentsException;
use App\Jobs\SDG\SDGSendInboundJob;
use App\Models\Integration;
use App\Models\IntegrationReport;
use App\Models\Product;
use App\Models\ProductActual;
use App\Models\Provider;
use App\Models\ProductPrice;
use App\Models\ProviderProduct;
use App\Services\Excel\WithExcelHelper;
use App\Services\Integration\BaseIntegration;
use App\Services\Integration\Transport\Enums\ImapXlsType;
use App\Services\Integration\Transport\Mailable\OrderToProviderMail;
use App\Services\Integration\Transport\Mailable\SuccessMail;
use App\Traits\WithDynamicComparator;
use BeyondCode\Mailbox\InboundEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImapXlsTransport
{
    use WithExcelHelper, WithDynamicComparator;

    public ?InboundEmail $origin       = null;
    protected string     $providerName = '';
    public string        $filename;

    public Spreadsheet  $excel;
    public Provider     $provider;
    public array        $data         = [];
    public array        $isCatalog    = [];
    public array        $isPrices     = [];
    public array        $isFinal      = [];
    public array        $parseCatalog = [];
    public array        $parsePrices  = [];
    public array        $sendPrices   = [];
    public ?ImapXlsType $type         = null;
    public ?array       $report       = [
        'success'   => [],
        'not_found' => [],
        'ignored'   => [],
    ];

    protected int $satisfyCount = 0;
    //protected int $satisfyCount     = 50;
    protected int $satisfyExireDays = 1;
    //protected int $satisfyExireDays = 7;
    //protected float $multiplyPrice = 1.7;

    public Carbon $now;

    protected function satisfyPrice (float $rrc): float
    {
        return 10000000;
        //return ($rrc * 0.7) / 2;
    }

    public function getParsedValue ($cell, $position, Product $product = null)
    {
        if ($cell instanceof \Closure) {
            return $cell($position, $product);
        }
        if (str_contains($cell, ':')) {
            list($cell, $type) = explode(':', $cell);
            $formatted = true;
            if ($type === 'source') {
                $formatted = false;
            }
            return $this->castType($this->getExcelValue(cell: $cell . $position, formatted: $formatted), $type);
        }
        return $this->getExcelValue(cell: $cell . $position, formatted: true);
    }

    public function parseExcel (): array
    {
        $config = $this->{"parse" . ucfirst($this->type->value)};
        $data = [];

        for ($i = $config['skip'] - 1; $i <= $this->excel->getSheet(0)->getHighestRow(); $i++) {
            $ean = $this->getParsedValue($config['ean'], $i);
            if ($ean) {
                $product = Product::findByEan($ean);
                if (!$product) {
                    $this->report['not_found'][] = $ean;
                    continue;
                }

                $new = [
                    'ean' => $ean
                ];

                foreach ($config['data'] as $attribute => $column) {
                    $new[$attribute] = $this->getParsedValue($column, $i, $product);
                }
                $data[] = $new;
            }
        }

        return $data;
    }

    public function processCatalog (array $data): void
    {
        foreach ($data as $line) {
            $product = Product::findByEan($line['ean']);
            if ($product) {
                $update = [];
                foreach ($line as $k => $v) {
                    if (in_array($k, ['ean', 'extra', 'external_id'])) continue;
                    if (!$product->{$k} && in_array($k, array_keys($product->attributesToArray()))) {
                        $update[$k] = $v;
                    }
                }
                if (count($update)) {
                    $product->update($update);
                }
                if ($line['external_id'] ?? false) {
                    $provider_product = ProviderProduct::where('provider_id', $this->provider->id)->where('external_id', $line['external_id'])->first();
                    if (!$provider_product) {
                        $provider_product = ProviderProduct::create([
                            'provider_id' => $this->provider->id,
                            'external_id' => $line['external_id'],
                            'product_id'  => $product->id,
                            'extra'       => $line['extra'] ?? null
                        ]);
                    }
                    if (!empty($line['extra']) && json_encode($provider_product->extra) !== json_encode($line['extra'])) {
                        $provider_product->update(['extra' => $line['extra']]);
                    }
                }
            }
        }
    }

    public function processPrices (array $data): void
    {
        ProductActual::removeUnusedTodayByProvider($this->provider);

        $multiplyPrice = $this->provider->margin / 100 + 1;

        foreach ($data as $line) {
            $product = Product::findByEan($line['ean']);
            if ($product) {
                ProductPrice::create([
                    'provider_id'     => $this->provider->id,
                    'product_id'      => $product->id,
                    'date'            => $this->now->format('Y-m-d'),
                    'count'           => $line['count'],
                    'price'           => $line['finish_price'] * $multiplyPrice,
                    'start_price'     => $line['rrc'] ?? $product->price,
                    'finish_price'    => $line['finish_price'],
                    'manufactured_at' => $line['manufactured_at'],
                    'expired_at'      => $line['expired_at']
                ]);

                if (!$product->expire_days) {
                    $product->update([
                        'expire_days' => Carbon::parse($line['expired_at'])->diffInDays(Carbon::parse($line['manufactured_at']))
                    ]);
                }
            }
        }
    }

    public function processFinal (array $data): void
    {
        $result = [];
        foreach ($data as $item) {
            $product = Product::findByEan($item['ean']);
            if (!$product) {
                event(new SystemFinalPriceNoEanException($item['ean']));
                continue;
            }
            $result[$product->id . ':' . $item['expired_at']] = [
                'product_id'              => $product->id,
                'product_name'            => $product->name,
                'product_expired_at'      => $item['expired_at'],
                'product_manufactured_at' => $item['manufactured_at'],
                'provider_id'             => $this->provider->id,
                'finish_count'            => $item['finish_count']
            ];
        }

        $this->report = $result;
    }

    public function filterCatalog (array $data): array
    {
        $this->report['success'] = $data;
        return $data;
    }

    public function filterPrices (array $data): array
    {
        $result = [];

        foreach ($data as $line) {
            if (!($line['count'] >= $this->satisfyCount)) {
                $this->report['ignored'][] = [
                    'data' => $line, 'reason' => [
                        'count',
                        [
                            'count'   => $line['count'],
                            'satisfy' => $this->satisfyCount
                        ]
                    ]
                ];
                continue;
            }
            if (!(
                Carbon::parse($line['expired_at'])->gt($this->now->format('Y-m-d')) &&
                Carbon::parse($line['expired_at'])->diffInDays($this->now->format('Y-m-d')) >= $this->satisfyExireDays
            )) {
                $this->report['ignored'][] = [
                    'data' => $line, 'reason' => [
                        'expired',
                        [
                            'expire_days' => Carbon::parse($line['expired_at'])->diffInDays($this->now->format('Y-m-d')),
                            'satisfy'     => $this->satisfyExireDays
                        ],
                    ]
                ];
                continue;
            }

            $product = Product::findByEan($line['ean']);
            if (!$product->price) {
                $product->price = $line['rrc'];
                $product->save();
            }

            if (!($product->price && $this->satisfyPrice($product->price) >= $line['finish_price'])) {
                $this->report['ignored'][] = [
                    'data' => $line, 'reason' => [
                        'price',
                        [
                            'rrc'     => $product->price,
                            'price'   => $line['finish_price'],
                            'satisfy' => $this->satisfyPrice($product->price)
                        ]
                    ]
                ];
                continue;
            }

            $result[] = $line;
        }

        $this->report['success'] = $result;
        return $result;
    }

    public function filterFinal (array $data): array
    {
        $result = [];

        foreach ($data as $line) {
            if ((int)$line['need_count'] > 0) {
                $result[] = $line;
            }
        }

        return $result;
    }

    public function processedEmail (): void
    {
        $attachments = $this->origin->attachments();
        $filename = $attachments[0]->getFilename();
        $pathinfo = pathinfo($attachments[0]->getFilename());
        $extension = strtolower($pathinfo['extension']);

        if (!count($attachments)) {
            throw new NoAttachmentsException;
        }
        if (count($attachments) !== 1) {
            throw new ManyAttachmentsException;
        }
        if (!in_array($extension, ['xls', 'xlsx'])) {
            throw new NoXlsAttachmentsException;
        }

        $this->filename = $this->saveMail();

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($extension));
        $sheetnames = $reader->listWorksheetNames($this->filename);
        $reader->setLoadSheetsOnly($sheetnames[0]);
        $this->setExcel($reader->load($this->filename));
        if ($this->type) {
            $writer = new Xlsx($this->excel);
            $writer->save($this->filename);
        }

        Storage::disk('local')->setVisibility($this->filename, 'public');
    }

    public function setExcel (Spreadsheet $excel): void
    {
        $this->excel = $excel;

        if ($this->checkingExcel($this->isCatalog)) {
            $this->type = ImapXlsType::CATALOG;
        } else if ($this->checkingExcel($this->isPrices)) {
            $this->type = ImapXlsType::PRICES;
        } else if ($this->checkingExcel($this->isFinal)) {
            $this->type = ImapXlsType::FINAL_FROM_PROVIDER;
        }

        if (!$this->type || $this->type !== ImapXlsType::FINAL_FROM_PROVIDER) {
            throw new WrongFormatAttachmentsException;
        }
    }

    protected function saveMail (): string
    {
        $dir = implode('/', [
            'integration',
            'imap',
            $this->now->format('Y/m/d'),
            $this->providerName(),
            $this->origin->id()
        ]);
        $attach = $this->origin->attachments()[0];

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->setVisibility($dir, 'public');
        Storage::disk('local')->put($dir . '/' . $this->origin->id() . '.eml', $this->origin->message);
        $attach->saveContent(storage_path('app/' . $dir . '/' . $attach->getFilename()));

        return storage_path('app/' . $dir . '/' . $attach->getFilename());
    }

    protected function providerName (): string
    {
        if ($this->providerName) return $this->providerName;
        return substr(class_basename(get_class($this)), 0, -11);
    }

    protected function checkingExcel (array $checking): bool
    {
        foreach ($checking as $cell => $value) {
            if (is_array($value) && count($value) === 2) {
                if (!$this->is(strtolower($this->getExcelValue(cell: $cell)), $value[0], strtolower($value[1]))) {
                    return false;
                }
            } elseif ($value instanceof \Closure) {
                if (!$value()) {
                    return false;
                }
            } else {
                if (strtolower($this->getExcelValue(cell: $cell)) !== strtolower($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function castType (string $value, string $type): mixed
    {
        $tmp = $value;

        switch ($type) {
            case 'boolean':
            case 'bool':
            case 'integer':
            case 'int':
            case 'float':
            case 'double':
            case 'string':
            {
                settype($tmp, $type);
                break;
            }
            case 'date':
            {
                $formats = ['Y-m-d', 'Y.m.d', 'Y.d.m', 'd.m.Y', 'm.d.Y', 'Y/m/d', 'Y/d/m', 'm/d/Y', 'd/m/Y'];
                $date = null;
                foreach ($formats as $format) {
                    try {
                        if ($date = Carbon::createFromFormat($format, $tmp)) {
                            break;
                        }
                    } catch (\Exception $e) {
                    }
                }
                return $date->format('Y-m-d');
            }
            case 'price':
            {
                return round((float)str_replace(',', '.', $tmp), 2);
            }
        }

        return $tmp;
    }

    public function response (): void
    {
        switch ($this->type) {
            case ImapXlsType::PRICES:
            {
                Integration::create([
                    'date'        => Carbon::now(),
                    'type'        => $this->type,
                    'provider_id' => $this->provider->id,
                    'data'        => $this->data,
                    'extra'       => ['report' => $this->report]
                ]);

                Mail::to($this->provider->integration_emails)->send(new SuccessMail(
                    provider: $this->provider->name,
                    date: $this->now,
                    type: $this->type,
                    report: $this->report
                ));
                break;
            }

            case ImapXlsType::TO_PROVIDER:
            {

                $prices_id = Integration::query()
                    ->where('date', $this->now->format('Y-m-d'))
                    ->where('type', ImapXlsType::PRICES)
                    ->where('provider_id', $this->provider->id)
                    ->first()
                    ?->id;

                Integration::create([
                    'parent_id'   => $prices_id ?? 0,
                    'date'        => Carbon::now(),
                    'type'        => $this->type,
                    'provider_id' => $this->provider->id,
                    'data'        => $this->data,
                    'extra'       => ['report' => $this->report]
                ]);

                Mail::to($this->provider->integration_emails)->send(new OrderToProviderMail(
                    provider: $this->provider->name,
                    date: $this->now,
                    filename: $this->filename
                ));

                BaseIntegration::updateOrdersToProvider($this->provider->id);
                break;
            }

            case ImapXlsType::FINAL_FROM_PROVIDER:
            {
                /*
                $to_provider_id = Integration::query()
                    ->where('date', $this->now->format('Y-m-d'))
                    ->where('type', ImapXlsType::TO_PROVIDER)
                    ->where('provider_id', $this->provider->id)
                    ->first()
                    ?->id;
                */
                $integration = Integration::create([
                    //'parent_id'   => $to_provider_id ?? 0,
                    'date'        => Carbon::now(),
                    'type'        => $this->type,
                    'provider_id' => $this->provider->id,
                    'data'        => $this->data,
                ]);

                dispatch(new SDGSendInboundJob(extra: [
                    'integration' => $integration,
                    'data'        => $this->data,
                    'report'      => $this->report
                ]));
                //BaseIntegration::updateOrdersFinalFromProvider($this->report, integration: $integration);
                break;
            }
        }
    }

    private function closure_dump (\Closure $c)
    {
        $str = 'function (';
        $r = new \ReflectionFunction($c);
        $params = array();
        foreach ($r->getParameters() as $p) {
            $s = '';
            if ($p->isArray()) {
                $s .= 'array ';
            } else if ($p->getClass()) {
                $s .= $p->getClass()->name . ' ';
            }
            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
            }
            $params [] = $s;
        }
        $str .= implode(', ', $params);
        $str .= '){' . PHP_EOL;
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
            $str .= $lines[$l];
        }
        return $str;
    }

    public static function sendToProvider (IntegrationReport $report, array $result)
    {
        $integration = new static();

        $integration->provider = Provider::find($report->provider_id);
        $integration->now = Carbon::now();
        $integration->setExcel(IOFactory::load($report->file));
        $integration->type = ImapXlsType::TO_PROVIDER;
        $integration->origin = InboundEmail::find($report->mailbox_id);
        $integration->data = $result;

        foreach ($integration->sendPrices as $v) {
            if (is_array($v)) {
                foreach ($v as $k => $v2) {
                    if (is_string($k)) {
                        $integration->excel->getSheet(0)->getCell($k)->setValue($v2);
                    }
                }
            } else if ($v instanceof \Closure) {
                $v($result);
            }
        }

        $dir = 'integration/imap/' . Carbon::now()->format('Y/m/d') . '/' . $integration->provider->slug . '/send_to_provider';
        $integration->filename = storage_path('app/' . $dir . '/order-' . $integration->provider->slug . '-' . Carbon::now()->format('Ymd-His') . '.xlsx');

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->setVisibility($dir, 'public');
        $writer = new Xlsx($integration->excel);
        $writer->save($integration->filename);
        Storage::disk('local')->setVisibility($integration->filename, 'public');

        $integration->log(extra: [
            'order' => $result
        ]);
        $integration->response();
    }

    public static function readMailbox (InboundEmail $email, string $provider = ''): bool
    {
        $integration = new static();

        if ($provider) {
            $integration->provider = Provider::where('slug', $provider)->first();
        }

        $integration->now = Carbon::now();
        $integration->origin = $email;
        $integration->report = null;
        $integration->processedEmail();

        $integration->data = $integration->parseExcel();

        switch ($integration->type) {
            case ImapXlsType::CATALOG:
            {
                $filtered = $integration->filterCatalog($integration->data);
                $integration->processCatalog($filtered);
                break;
            }
            case ImapXlsType::PRICES:
            {
                if (now()->format('H') >= 15) return false;
                $filtered = $integration->filterPrices($integration->data);
                $integration->processPrices($filtered);
                break;
            }
            case ImapXlsType::FINAL_FROM_PROVIDER:
            {
                $integration->processFinal($integration->data);
                break;
            }
        }

        $integration->log();
        $integration->response();
        return true;
    }

    public function log (?array $extra = null)
    {
        IntegrationReport::create([
            'provider_id'  => $this->provider->id,
            'mailbox_id'   => $this->origin->id,
            'mailbox_type' => $this->type,
            'file'         => $this->filename,
            'report'       => $this->report,
            'date'         => $this->now,
            'extra'        => $extra
        ]);
    }
}
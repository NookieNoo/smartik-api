<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{
    private Spreadsheet $excel;

    public function __construct () {}

    public function read (string $file)
    {
        $path = false;

        if (is_string($file) && Storage::disk('local')->exists($file)) {
            $path = Storage::disk('local')->path($file);
        }

        if ($path) {
            $this->excel = IOFactory::load($path);
            return $this;
        }

        return false;
    }

    public function toArray (): array
    {
        return $this->excel->getActiveSheet()->toArray();
    }
}
<?php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

trait WithExcelHelper
{
    public function getExcelValue (?Spreadsheet $excel = null, int $sheet = 0, string $cell = 'A0', bool $formatted = false): string
    {
        if (!$excel && $this->excel) {
            $excel = $this->excel;
        }
        if (!$excel) {
            throw new \Exception("no excel");
        }
        return trim($this->excel->getSheet($sheet)->getCell($cell)->{$formatted ? "getFormattedValue" : "getValue"}() ?? "");
    }
}
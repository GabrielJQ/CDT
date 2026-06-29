<?php

namespace App\Exports;

use App\Exports\Contracts\Exportable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class BaseExport implements Exportable
{
    public function map(array $row): array
    {
        return $row;
    }

    protected function writeXlsx(iterable $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $keys = array_keys($this->headings());
        $headers = array_values($this->headings());
        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Calibri'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '988256']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach ($headers as $col => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1).'1', $header);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            $mapped = $this->map($row);
            foreach ($keys as $col => $key) {
                $cell = Coordinate::stringFromColumnIndex($col + 1).$rowNum;
                $value = $this->resolveKey($mapped, $key);
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
            $rowNum++;
        }

        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $sheet->setAutoFilter("A1:{$lastCol}{$rowNum}");

        $tempFile = tempnam(sys_get_temp_dir(), 'cdt_xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return $tempFile;
    }

    public function download(array $filters): BinaryFileResponse
    {
        $tempFile = $this->writeXlsx($this->data($filters));

        return response()->download($tempFile, $this->filename(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function resolveKey(array $data, string $key): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return '';
            }
            $data = $data[$segment];
        }

        return $data ?? '';
    }
}

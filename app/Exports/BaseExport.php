<?php

namespace App\Exports;

use App\Exports\Contracts\Exportable;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class BaseExport implements Exportable
{
    protected function wirteCsv(iterable $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cdt_csv_');
        $csv = Writer::createFromPath($tempFile, 'w+');
        $csv->setOutputBOM(Writer::BOM_UTF8);

        $csv->insertOne(array_values($this->headings()));

        foreach ($rows as $row) {
            $csv->insertOne($this->map($row));
        }

        return $tempFile;
    }

    public function download(array $filters): BinaryFileResponse
    {
        $tempFile = $this->wirteCsv($this->data($filters));

        return response()->download($tempFile, $this->filename(), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }
}

<?php

namespace App\Servicios;

use SplFileObject;

class ServicioSanitizadorCsv
{
    public function sanitizar(string $inputPath, string $outputPath, string $delimiter = ','): array
    {
        $in = new SplFileObject($inputPath, 'r');
        $out = new SplFileObject($outputPath, 'w');

        $stats = [
            'total_lines' => 0,
            'encoding_fixes' => 0,
            'control_chars_removed' => 0,
        ];

        while (! $in->eof()) {
            $linea = $in->fgets();
            if ($linea === false) {
                break;
            }

            $stats['total_lines']++;

            $encoding = mb_detect_encoding($linea, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            if ($encoding !== false && $encoding !== 'UTF-8') {
                $linea = mb_convert_encoding($linea, 'UTF-8', $encoding);
                $stats['encoding_fixes']++;
            }

            $limpia = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $linea);
            if ($limpia !== $linea) {
                $stats['control_chars_removed']++;
                $linea = $limpia;
            }

            $linea = rtrim($linea, "\r\n")."\n";

            $out->fwrite($linea);
        }

        unset($in, $out);

        return $stats;
    }

    public function contarFilas(string $csvPath, bool $skipHeader = true): int
    {
        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV);

        $count = 0;
        while (! $file->eof()) {
            $file->fgets();
            $count++;
        }

        return $skipHeader ? max(0, $count - 1) : $count;
    }

    public function extraerHeader(string $csvPath, string $delimiter = ','): array
    {
        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $header = $file->fgetcsv();
        if ($header === false || $header === []) {
            return [];
        }

        return $header;
    }

    public function dividirEnChunks(string $csvPath, string $chunkDir, int $chunkSize, string $delimiter = ','): array
    {
        @mkdir($chunkDir, 0755, true);

        $file = new SplFileObject($csvPath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $header = $file->fgetcsv();
        if ($header === false) {
            return [];
        }

        $chunkFiles = [];
        $chunkIndex = 0;
        $lineCount = 0;
        $out = null;

        while (! $file->eof()) {
            $row = $file->fgetcsv();
            if ($row === false || $row === [null]) {
                continue;
            }

            if ($lineCount % $chunkSize === 0) {
                if ($out !== null) {
                    fclose($out);
                }

                $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}.csv";
                $out = fopen($chunkPath, 'w');
                fputcsv($out, $header, $delimiter);
                $chunkFiles[] = $chunkPath;
                $chunkIndex++;
            }

            fputcsv($out, $row, $delimiter);
            $lineCount++;
        }

        if ($out !== null) {
            fclose($out);
        }

        return $chunkFiles;
    }
}

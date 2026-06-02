<?php

namespace App\Servicios;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioExportacion
{
    public static function csvResponse(array $data, array $columns, string $filename): StreamedResponse
    {
        $callback = function () use ($data, $columns) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, array_values($columns));

            foreach ($data as $row) {
                $values = [];
                foreach (array_keys($columns) as $key) {
                    $val = self::extractValue($row, $key);
                    $values[] = $val;
                }
                fputcsv($output, $values);
            }

            fclose($output);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function extractValue(array $row, string $key): string
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $val = $row;
            foreach ($parts as $part) {
                $val = $val[$part] ?? '';
            }
        } else {
            $val = $row[$key] ?? '';
        }

        if (is_array($val)) {
            $flattened = [];
            array_walk_recursive($val, function ($v) use (&$flattened) {
                $flattened[] = $v;
            });
            return implode('; ', $flattened);
        }

        if (is_object($val)) {
            if (method_exists($val, 'format')) {
                return $val->format('d/m/Y');
            }
            if (method_exists($val, '__toString')) {
                return (string) $val;
            }
            return '';
        }

        return (string) $val;
    }
}

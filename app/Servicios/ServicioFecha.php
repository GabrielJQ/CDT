<?php

namespace App\Servicios;

use Carbon\Carbon;

class ServicioFecha
{
    public function parsear(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '' || trim($value) === '0') return null;

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date !== false) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            $date = Carbon::parse(trim($value));
            if ($date->year > 2000) return $date;
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}

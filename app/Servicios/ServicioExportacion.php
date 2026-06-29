<?php

namespace App\Servicios;

use App\Exports\Contracts\Exportable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServicioExportacion
{
    public static function download(Exportable $export, array $filters = []): BinaryFileResponse
    {
        return $export->download($filters);
    }
}

<?php

namespace App\Servicios;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ServicioUpload
{
    public function storeImportFile(UploadedFile $file): string
    {
        $dir = 'imports';
        Storage::disk('local')->makeDirectory($dir);

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            .'_'.now()->format('Ymd_His')
            .'.csv';

        return $file->storeAs($dir, $name, 'local');
    }

    public function storeCasaPorCasaFile(UploadedFile $file): string
    {
        $dir = 'imports/casa-x-casa';
        Storage::disk('local')->makeDirectory($dir);

        $name = 'cxc_'.now()->format('Ymd_His').'.'.$file->extension();

        return $file->storeAs($dir, $name, 'local');
    }

    public function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }
    }

    public function fullPath(string $relativePath): string
    {
        return Storage::disk('local')->path($relativePath);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizarImportacionJob;
use App\Jobs\ProcesarChunkCsvJob;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioSanitizadorCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function index()
    {
        $importsDir = storage_path('app/imports');

        $archivos = [];
        if (is_dir($importsDir)) {
            $files = glob($importsDir.'/*.csv');
            $archivos = array_map(function ($f) {
                return [
                    'name' => basename($f),
                    'size' => filesize($f),
                    'modified' => filemtime($f),
                ];
            }, $files);
            rsort($archivos);
        }

        $chunksDir = storage_path('app/imports/_chunks');
        $chunkCount = 0;
        if (is_dir($chunksDir)) {
            $chunkCount = count(glob($chunksDir.'/chunk_*.csv'));
        }

        $stagingCount = false;
        try {
            $stagingCount = DB::connection('pgsql_imports')
                ->table('staging_import')
                ->count();
        } catch (\Throwable) {
        }

        return view('imports', [
            'archivos' => $archivos,
            'chunkCount' => $chunkCount,
            'stagingCount' => $stagingCount,
        ]);
    }

    public function upload(Request $request, ServicioSanitizadorCsv $sanitizer)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200',
        ]);

        $file = $request->file('csv_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            .'_'.now()->format('Ymd_His')
            .'.csv';

        $destPath = storage_path('app/imports/'.$originalName);
        $file->move(storage_path('app/imports'), $originalName);

        $sanitizedDir = storage_path('app/imports/_sanitized');
        @mkdir($sanitizedDir, 0755, true);
        $sanitizedPath = "{$sanitizedDir}/{$originalName}";

        $stats = $sanitizer->sanitizar($destPath, $sanitizedPath);

        $header = $sanitizer->extraerHeader($sanitizedPath);
        $mapper = ServicioMapeoColumnas::make();
        $advertencias = $mapper->validarColumnas($header);

        $chunkDir = storage_path('app/imports/_chunks');
        $chunkFiles = $sanitizer->dividirEnChunks($sanitizedPath, $chunkDir, 100000);

        $jobs = [];
        foreach ($chunkFiles as $index => $chunkPath) {
            $jobs[] = new ProcesarChunkCsvJob(
                chunkPath: $chunkPath,
                chunkIndex: $index,
            );
        }

        $batch = Bus::batch($jobs)
            ->name("Importación web: {$originalName}")
            ->allowFailures()
            ->onQueue('imports')
            ->then(function () {
                FinalizarImportacionJob::dispatch()->onQueue('imports');
            })
            ->dispatch();

        return redirect()->route('imports.index')->with('success', sprintf(
            'Archivo subido: %s (%d filas, %d chunks). Batch #%s',
            $originalName,
            $stats['total_lines'] - 1,
            count($chunkFiles),
            $batch->id,
        ));
    }

    public function uploadForm()
    {
        return view('upload-form');
    }
}

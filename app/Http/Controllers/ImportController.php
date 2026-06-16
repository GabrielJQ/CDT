<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizarImportacionJob;
use App\Jobs\ProcesarChunkCsvJob;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioPeriodosImportacion;
use App\Servicios\ServicioSanitizadorCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function index()
    {
        $periodos = app(ServicioPeriodosImportacion::class);

        return view('imports', [
            'periodosActivos' => $periodos->activos(),
            'trimestres' => $periodos->trimestres(),
            'currentYear' => (int) now()->year,
        ]);
    }

    public function upload(Request $request, ServicioSanitizadorCsv $sanitizer, ServicioPeriodosImportacion $periodos)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200',
            'anio' => 'required|integer|min:2020|max:2100',
            'trimestre' => 'required|in:T1,T2,T3,T4',
            'fecha_corte' => 'nullable|date',
        ]);

        $anio = (int) $request->input('anio');
        $trimestre = $request->input('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        try {
            if ($periodos->existe(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre) && ! $reemplazar) {
                return back()
                    ->withInput($request->except('csv_file'))
                    ->with('error', $periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre));
            }
        } catch (\Throwable $e) {
            return back()->withInput($request->except('csv_file'))->with('error', $e->getMessage());
        }

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

        try {
            $periodo = $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_REGULAR,
                $anio,
                $trimestre,
                $request->input('fecha_corte'),
                $originalName,
                $reemplazar,
            );
        } catch (\Throwable $e) {
            return back()->withInput($request->except('csv_file'))->with('error', $e->getMessage());
        }

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
            ->then(function () use ($periodo) {
                FinalizarImportacionJob::dispatch((int) $periodo->id)->onQueue('imports');
            })
            ->dispatch();

        return redirect()->route('imports.index')->with('success', sprintf(
            'Archivo subido: %s (%d filas). Periodo: %s.',
            $originalName,
            $stats['total_lines'] - 1,
            $periodo->nombre,
        ));
    }

    public function uploadCasaPorCasa(Request $request, ServicioPeriodosImportacion $periodos)
    {
        $request->validate([
            'xlsx_file' => 'required|file|mimes:xlsx,xls|max:51200',
            'anio' => 'required|integer|min:2020|max:2100',
            'trimestre' => 'required|in:T1,T2,T3,T4',
            'fecha_corte' => 'nullable|date',
        ]);

        $anio = (int) $request->input('anio');
        $trimestre = $request->input('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        if ($periodos->existe(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre) && ! $reemplazar) {
            return back()
                ->withInput($request->except('xlsx_file'))
                ->with('error', $periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre));
        }

        $file = $request->file('xlsx_file');
        $dir = 'imports/casa-x-casa';

        Storage::disk('local')->makeDirectory($dir);

        $path = $file->storeAs($dir, 'cxc_'.now()->format('Ymd_His').'.'.$file->extension(), 'local');

        $fullPath = Storage::disk('local')->path($path);

        try {
            $periodo = $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_CASA_X_CASA,
                $anio,
                $trimestre,
                $request->input('fecha_corte'),
                basename($path),
                $reemplazar,
            );
        } catch (\Throwable $e) {
            return back()->withInput($request->except('xlsx_file'))->with('error', $e->getMessage());
        }

        $exitCode = Artisan::call('casa-x-casa:import', [
            'file' => $fullPath,
            '--periodo' => $periodo->id,
            '--no-interaction' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            return back()->with('error', 'Error al importar: '.$output);
        }

        DashboardController::invalidateDashboardCache();

        $lines = explode("\n", trim($output));
        $summary = collect($lines)->filter(fn ($l) => str_starts_with($l, 'Importación'))->first() ?? 'OK';

        return back()->with('success', "Archivo CxC importado correctamente. {$summary}");
    }

    public function uploadForm()
    {
        return view('upload-form');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function refresh()
    {
        $this->invalidateDashboardCache();

        return back()->with('success', 'Cache actualizado correctamente desde la base local.');
    }

    public static function invalidateDashboardCache(): void
    {
        Cache::increment('dashboard_metrics_version');
    }
}

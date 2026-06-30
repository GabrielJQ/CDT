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
        static::invalidateAllCaches();

        return back()->with('success', 'Cache actualizado.');
    }

    public static function invalidateAllCaches(): void
    {
        Cache::flush();
    }
}

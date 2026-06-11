<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function applyRegionFilter(): array
    {
        return [
            'region' => request()->cookie('region_filter', ''),
            'uo' => request()->cookie('uo_filter', ''),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AperturaController extends Controller
{
    public function index(Request $request)
    {
        return view('aperturas');
    }
}

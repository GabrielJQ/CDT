<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    public function index(Request $request)
    {
        return view('connectivity');
    }
}

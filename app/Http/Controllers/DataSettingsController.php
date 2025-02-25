<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DataSettingsController extends Controller
{
    public function index()
    {
        return view('data_settings.index');
    }
}

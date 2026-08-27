<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('logistics.dashboard');
    }
}
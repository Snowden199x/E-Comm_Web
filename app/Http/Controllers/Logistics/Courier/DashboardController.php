<?php

namespace App\Http\Controllers\Logistics\Courier;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('logistics.courier.dashboard');
    }
}
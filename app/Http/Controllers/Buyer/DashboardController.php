<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('buyer.dashboard');
    }
}
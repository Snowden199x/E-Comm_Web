<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\Announcement;
use App\Models\Complaint;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'total_sales' => Order::where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_users' => User::where('role', 'buyer')->count(),
            'total_sellers' => User::where('role', 'seller')->count(),
        ];

        // Sales Summary
        $salesSummary = [
            'gross_sales' => Order::where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => Order::count(),
            'average_order_value' => Order::where('status', '!=', 'cancelled')->avg('total_amount') ?? 0,
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'return_refund' => Order::where('status', 'returned')->count(),
        ];

        // Sales Overview Chart (last 6 weeks)
        $chartLabels = [];
        $chartSales = [];
        $chartOrders = [];

        for ($i = 5; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            $chartLabels[] = $weekStart->format('M j');
            $chartSales[] = Order::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            $chartOrders[] = Order::whereBetween('created_at', [$weekStart, $weekEnd])->count();
        }

        $chartData = [
            'labels' => $chartLabels,
            'sales' => $chartSales,
            'orders' => $chartOrders,
        ];

        // Pending Registrations count per role
        $pendingRegistrations = [
            'sellers' => User::where('role', 'seller')->where('status', 'pending')->count(),
            'couriers' => User::where('role', 'courier')->where('status', 'pending')->count(),
            'buyers' => User::where('role', 'buyer')->where('status', 'pending')->count(),
        ];

        // Latest Notifications
        $notifications = Notification::latest()->take(5)->get();

        // Recent Registrations (latest 5, any status)
        $recentRegistrations = User::whereIn('role', ['seller', 'courier', 'buyer'])
            ->latest()
            ->take(5)
            ->get();

        // Recent Complaints (latest 5)
        $recentComplaints = Complaint::with(['order', 'complainant', 'respondent'])
            ->latest()
            ->take(5)
            ->get();
            
        // Latest active announcement
        $announcement = Announcement::where('is_active', true)->latest()->first();

        return view('dashboard', compact(
            'stats',
            'salesSummary',
            'chartData',
            'pendingRegistrations',
            'notifications',
            'recentRegistrations',
            'recentComplaints',
            'announcement'
        ));
    }
}
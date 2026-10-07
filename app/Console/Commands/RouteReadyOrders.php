<?php

namespace App\Console\Commands;

use App\Services\OrderRoutingService;
use Illuminate\Console\Command;

class RouteReadyOrders extends Command
{
    protected $signature = 'orders:route-ready';
    protected $description = 'Assign unassigned ready orders to unique active origin and destination centers';

    public function handle(OrderRoutingService $routing): int
    {
        $count = $routing->routeUnresolvedReady();

        $this->components->info("Reviewed {$count} ready orders for routing.");

        return self::SUCCESS;
    }
}

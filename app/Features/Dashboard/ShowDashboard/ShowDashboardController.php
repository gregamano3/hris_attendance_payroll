<?php

namespace App\Features\Dashboard\ShowDashboard;

use Illuminate\View\View;

class ShowDashboardController
{
    public function __invoke(GetDashboardStats $stats): View
    {
        return view('dashboard::index', ['stats' => $stats->handle()]);
    }
}

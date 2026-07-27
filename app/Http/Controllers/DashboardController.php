<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * Sales figures are only sent to users who can view sales/products, so
     * the dashboard never leaks revenue/stock data to a role that isn't
     * granted those permissions elsewhere in the app.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $canViewSales = $user->can('sales.view');
        $canViewProducts = $user->can('products.view');

        return Inertia::render('Dashboard', [
            'totalUsers' => User::count(),
            'salesSummary' => $canViewSales ? $this->dashboardService->salesSummary() : null,
            'salesChart' => $canViewSales ? $this->dashboardService->salesChart() : null,
            'topProducts' => $canViewSales ? $this->dashboardService->topSellingProducts() : null,
            'lowStockProducts' => $canViewProducts ? $this->dashboardService->lowStockProducts() : null,
            'recentSales' => $canViewSales ? $this->dashboardService->recentSales() : null,
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Products whose total quantity across all branches is at or below this
     * are flagged as low stock. No per-product threshold exists yet, so a
     * single project-wide default is used.
     */
    private const LOW_STOCK_THRESHOLD = 10;

    /**
     * Today / weekly (trailing 7 days) / monthly (month to date) revenue
     * and sale count, completed sales only.
     */
    public function salesSummary(): array
    {
        $now = Carbon::now();

        return [
            'today' => $this->totalsBetween($now->copy()->startOfDay(), $now->copy()->endOfDay()),
            'weekly' => $this->totalsBetween($now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()),
            'monthly' => $this->totalsBetween($now->copy()->startOfMonth(), $now->copy()->endOfDay()),
        ];
    }

    private function totalsBetween(Carbon $from, Carbon $to): array
    {
        $total = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sale_date', [$from, $to])
            ->sum('total_amount');

        $count = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sale_date', [$from, $to])
            ->count();

        return ['total' => (float) $total, 'count' => $count];
    }

    /**
     * Chart series for the Today / Weekly / Monthly toggle. Buckets are
     * built in PHP rather than DB date functions (HOUR()/DATE()) so the
     * same code works against MySQL in production and SQLite in tests.
     */
    public function salesChart(): array
    {
        return [
            'today' => $this->hourlySeries(),
            'weekly' => $this->dailySeries(7),
            'monthly' => $this->dailySeries(30),
        ];
    }

    private function hourlySeries(): array
    {
        $start = Carbon::today();
        $end = Carbon::today()->endOfDay();

        $buckets = array_fill(0, 24, 0.0);

        Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sale_date', [$start, $end])
            ->get(['total_amount', 'sale_date'])
            ->each(function (Sale $sale) use (&$buckets) {
                $buckets[$sale->sale_date->hour] += (float) $sale->total_amount;
            });

        return [
            'labels' => array_map(fn ($hour) => Carbon::today()->setTime($hour, 0)->format('g A'), range(0, 23)),
            'data' => array_values($buckets),
        ];
    }

    private function dailySeries(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $end = Carbon::today()->endOfDay();

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $buckets[$start->copy()->addDays($i)->toDateString()] = 0.0;
        }

        Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sale_date', [$start, $end])
            ->get(['total_amount', 'sale_date'])
            ->each(function (Sale $sale) use (&$buckets) {
                $key = $sale->sale_date->toDateString();
                if (array_key_exists($key, $buckets)) {
                    $buckets[$key] += (float) $sale->total_amount;
                }
            });

        return [
            'labels' => array_map(fn ($date) => Carbon::parse($date)->format('M j'), array_keys($buckets)),
            'data' => array_values($buckets),
        ];
    }

    /**
     * Best sellers for the current month by revenue, completed sales only.
     */
    public function topSellingProducts(int $limit = 5): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.sale_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_items.subtotal)'))
            ->limit($limit)
            ->get([
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * Products at or below LOW_STOCK_THRESHOLD, summed across branches,
     * most urgent (lowest quantity) first.
     */
    public function lowStockProducts(int $limit = 5): array
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->havingRaw('SUM(product_stocks.quantity) <= ?', [self::LOW_STOCK_THRESHOLD])
            ->orderBy(DB::raw('SUM(product_stocks.quantity)'))
            ->limit($limit)
            ->get([
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(product_stocks.quantity) as quantity'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'quantity' => (int) $row->quantity,
            ])
            ->all();
    }

    public function recentSales(int $limit = 5): array
    {
        return Sale::with('customer')
            ->latest('sale_date')
            ->limit($limit)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer?->name,
                'total_amount' => (float) $sale->total_amount,
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'sale_date' => $sale->sale_date->toDateTimeString(),
            ])
            ->all();
    }
}

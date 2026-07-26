<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeKpiResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KpiLeaderboardController extends Controller
{
    /**
     * Employees ranked by kpi_score (ARCHITECTURE.md §5.3), Admin-only.
     *
     * Supports `search` (matches employee name/email).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        $employees = User::role('Employee')
            ->search($filters['search'] ?? null)
            ->orderByDesc('kpi_score')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Kpi/Leaderboard', [
            'employees' => EmployeeKpiResource::collection($employees)->response()->getData(true),
            'filters' => $filters,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\LostCustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LostCustomerController extends Controller
{
    /**
     * List customers matching Customer::scopeLost() (ARCHITECTURE.md §5.2).
     *
     * Supports `search` (matches name/phone/email, same as CustomerController).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        $customers = Customer::lost()
            ->search($filters['search'] ?? null)
            ->withMax('sales', 'sale_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('LostCustomers/Index', [
            'customers' => LostCustomerResource::collection($customers)->response()->getData(true),
            'filters' => $filters,
            'lostCustomerDays' => config('crm.lost_customer_days'),
        ]);
    }
}

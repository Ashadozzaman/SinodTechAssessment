<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Supports `search` (matches name/phone/email).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        $customers = Customer::search($filters['search'] ?? null)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => CustomerResource::collection($customers)->response()->getData(true),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Customers/CreateCustomer');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        Customer::create($request->validated());

        return to_route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource, including purchase history.
     *
     * last_purchase_at/purchase_frequency are computed live off `sales`
     * (ARCHITECTURE.md §4.2) and passed separately from CustomerResource so
     * the paginated Customers/Index list (which reuses CustomerResource)
     * doesn't pay for two extra queries per row.
     */
    public function show(Customer $customer)
    {
        $sales = $customer->sales()
            ->with(['items.product', 'branch'])
            ->latest('sale_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Customers/Show', [
            'customer' => CustomerResource::make($customer),
            'sales' => SaleResource::collection($sales)->response()->getData(true),
            'lastPurchaseAt' => $customer->lastPurchaseAt(),
            'purchaseFrequency' => $customer->purchaseFrequency(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/EditCustomer', [
            'customer' => CustomerResource::make($customer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return to_route('customers.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return to_route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Supports `search` (matches name/phone/email). A user who only holds
     * `customers.view` (i.e. Employee) is narrowed to customers with an
     * active `customer_assignments` row pointing to them (CLAUDE.md §3a).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $user = $request->user();

        $customers = Customer::search($filters['search'] ?? null)
            ->when($this->isViewOnly($user), fn (Builder $query) => $query->whereHas(
                'assignments',
                fn (Builder $query) => $query->where('employee_id', $user->id)->where('status', AssignmentStatus::Active)
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => CustomerResource::collection($customers)->response()->getData(true),
            'filters' => $filters,
        ]);
    }

    /**
     * True when the user's granted permissions are limited to
     * `customers.view` — i.e. an Employee, who may only see customers
     * actively assigned to them (CLAUDE.md §3a).
     */
    private function isViewOnly(User $user): bool
    {
        $permissions = $user->getAllPermissions()->pluck('name');

        return $permissions->contains('customers.view')
            && $permissions->intersect(['customers.create', 'customers.update', 'customers.delete'])->isEmpty();
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
    public function show(Request $request, Customer $customer)
    {
        $user = $request->user();

        abort_if(
            $this->isViewOnly($user) && ! $customer->assignments()
                ->where('employee_id', $user->id)
                ->where('status', AssignmentStatus::Active)
                ->exists(),
            403
        );

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

<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateActiveAssignmentException;
use App\Http\Requests\StoreCustomerAssignmentRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;

class CustomerAssignmentController extends Controller
{
    public function __construct(private readonly CrmService $crmService) {}

    /**
     * Assign a customer to an employee (Admin-only action, see the "Assign
     * to employee" button on the Lost Customers page).
     */
    public function store(StoreCustomerAssignmentRequest $request, Customer $customer)
    {
        $employee = User::findOrFail($request->validated('employee_id'));

        try {
            $this->crmService->assignCustomer($customer, $employee, $request->user());
        } catch (DuplicateActiveAssignmentException $e) {
            return back()->withErrors(['employee_id' => $e->getMessage()]);
        }

        return back()->with('success', "{$customer->name} assigned to {$employee->name}.");
    }
}

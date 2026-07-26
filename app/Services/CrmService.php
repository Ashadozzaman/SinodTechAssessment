<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Exceptions\DuplicateActiveAssignmentException;
use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CrmService
{
    /**
     * Assign a customer to an employee, guarding against a second `active`
     * assignment for the same customer (ARCHITECTURE.md §5.3).
     *
     * @throws DuplicateActiveAssignmentException
     */
    public function assignCustomer(Customer $customer, User $employee, User $assignedBy): CustomerAssignment
    {
        return DB::transaction(function () use ($customer, $employee, $assignedBy) {
            // customer_assignments has no existing row to lock before the
            // first assignment is created, so lock the parent customer row
            // instead — it always exists and serializes concurrent attempts.
            Customer::query()->whereKey($customer->id)->lockForUpdate()->first();

            $hasActiveAssignment = CustomerAssignment::query()
                ->where('customer_id', $customer->id)
                ->where('status', AssignmentStatus::Active)
                ->exists();

            if ($hasActiveAssignment) {
                throw DuplicateActiveAssignmentException::forCustomer($customer);
            }

            return CustomerAssignment::create([
                'customer_id' => $customer->id,
                'employee_id' => $employee->id,
                'assigned_by' => $assignedBy->id,
                'status' => AssignmentStatus::Active,
                'assigned_at' => now(),
            ]);
        });
    }
}

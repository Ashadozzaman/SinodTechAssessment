<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Exceptions\DuplicateActiveAssignmentException;
use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\Sale;
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

    /**
     * Award KPI points to the employee actively assigned to this sale's
     * customer, and resolve that assignment (ARCHITECTURE.md §5.1/§5.3).
     * No-op if the customer has no active assignment.
     */
    public function awardKpiForSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $assignment = CustomerAssignment::query()
                ->where('customer_id', $sale->customer_id)
                ->where('status', AssignmentStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $assignment) {
                return;
            }

            User::query()
                ->whereKey($assignment->employee_id)
                ->increment('kpi_score', config('crm.kpi_award_points'));

            $assignment->update([
                'status' => AssignmentStatus::Resolved,
                'resolved_at' => now(),
            ]);
        });
    }
}

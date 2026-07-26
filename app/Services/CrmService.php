<?php

namespace App\Services;

use App\Contracts\ReengagementChannel;
use App\Enums\AssignmentStatus;
use App\Enums\EngagementChannel;
use App\Enums\EngagementStatus;
use App\Exceptions\DuplicateActiveAssignmentException;
use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\CustomerEngagement;
use App\Models\Sale;
use App\Models\User;
use App\Services\Channels\EmailReengagementChannel;
use App\Services\Channels\SmsReengagementChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrmService
{
    /**
     * channel => implementation map (Dependency Inversion / Open-Closed,
     * CLAUDE.md §2a). Resolving the concrete class from this map — instead
     * of a switch statement — means adding a WhatsApp channel later is one
     * new class plus one new map entry, with zero changes below.
     *
     * @var array<string, class-string<ReengagementChannel>>
     */
    private const CHANNEL_MAP = [
        EngagementChannel::Email->value => EmailReengagementChannel::class,
        EngagementChannel::Sms->value => SmsReengagementChannel::class,
    ];

    /**
     * The status a successful send is recorded with, per channel. This is
     * bookkeeping CrmService owns (per ARCHITECTURE.md §5.4: email is real,
     * SMS is simulated) — it is intentionally not on the channel interface,
     * which stays narrow to just "send" (Interface Segregation).
     *
     * @var array<string, EngagementStatus>
     */
    private const SUCCESS_STATUS_MAP = [
        EngagementChannel::Email->value => EngagementStatus::Sent,
        EngagementChannel::Sms->value => EngagementStatus::Simulated,
    ];

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

    /**
     * Send (or simulate) a re-engagement message to a customer and record
     * the attempt (ARCHITECTURE.md §5.4).
     *
     * Channel dispatch is delegated to whichever `ReengagementChannel`
     * implementation CHANNEL_MAP resolves to — this method never branches
     * on the channel string to decide *how* to send, only to decide what
     * status to record. If the channel throws (e.g. the customer has no
     * email on file), the attempt is recorded as `failed` rather than
     * bubbling the exception up to the controller.
     */
    public function sendReengagement(Customer $customer, string $channel, string $message, ?User $triggeredBy): CustomerEngagement
    {
        $channelEnum = EngagementChannel::from($channel);
        $status = self::SUCCESS_STATUS_MAP[$channelEnum->value];

        try {
            $this->resolveChannel($channelEnum)->send($customer, $message);
        } catch (Throwable $e) {
            Log::error("Re-engagement send failed for customer {$customer->id} via {$channelEnum->value}: {$e->getMessage()}");
            $status = EngagementStatus::Failed;
        }

        return CustomerEngagement::create([
            'customer_id' => $customer->id,
            'channel' => $channelEnum,
            'message' => $message,
            'status' => $status,
            'sent_at' => now(),
            'triggered_by' => $triggeredBy?->id,
        ]);
    }

    private function resolveChannel(EngagementChannel $channel): ReengagementChannel
    {
        return app(self::CHANNEL_MAP[$channel->value]);
    }
}

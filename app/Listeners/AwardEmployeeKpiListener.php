<?php

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * No-op for now — filled in at Prompt 10 (KPI Tracking). Will look up an
 * active customer_assignments row for the sale's customer and, if found,
 * increment the assigned employee's kpi_score and mark the assignment
 * resolved.
 */
class AwardEmployeeKpiListener
{
    public function handle(SaleCompleted $event): void {}
}

<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Services\CrmService;

class AwardEmployeeKpiListener
{
    public function __construct(private readonly CrmService $crmService) {}

    public function handle(SaleCompleted $event): void
    {
        $this->crmService->awardKpiForSale($event->sale);
    }
}

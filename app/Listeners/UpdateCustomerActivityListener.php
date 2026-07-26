<?php

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * No-op for now. Customer purchase activity (last_purchase_at, purchase
 * frequency) is derived live from `sales` rather than cached (Prompt 6),
 * so there's no write to make here yet — this is a placeholder for
 * invalidating a cache if one is introduced later.
 */
class UpdateCustomerActivityListener
{
    public function handle(SaleCompleted $event): void {}
}

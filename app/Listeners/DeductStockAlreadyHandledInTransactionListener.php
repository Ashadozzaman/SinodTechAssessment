<?php

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * No-op by design. Stock deduction happens inside SaleService::create()'s
 * DB transaction, not here — a listener runs after commit and firing after
 * commit is too late to be relied on for data-integrity-critical work.
 * This class exists purely so that fact is discoverable next to the other
 * SaleCompleted listeners instead of only living in a comment.
 */
class DeductStockAlreadyHandledInTransactionListener
{
    public function handle(SaleCompleted $event): void {}
}

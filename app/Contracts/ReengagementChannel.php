<?php

namespace App\Contracts;

use App\Models\Customer;

/**
 * Open/Closed (CLAUDE.md §2a): adding a new delivery channel (e.g.
 * WhatsApp) means adding one new implementation of this interface and one
 * entry in CrmService's channel map — zero changes to CrmService itself.
 *
 * Kept to a single method (Interface Segregation) — anything beyond
 * "deliver this message" (e.g. logging the engagement) is CrmService's
 * job, not the channel's.
 */
interface ReengagementChannel
{
    /**
     * Deliver (or simulate delivering) a re-engagement message to a
     * customer. Implementations throw on failure; they never swallow
     * errors, so CrmService can record an accurate engagement status.
     */
    public function send(Customer $customer, string $message): void;
}

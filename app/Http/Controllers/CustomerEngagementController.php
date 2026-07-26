<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendReengagementRequest;
use App\Models\Customer;
use App\Services\CrmService;

class CustomerEngagementController extends Controller
{
    public function __construct(private readonly CrmService $crmService) {}

    /**
     * Send (or simulate) a re-engagement message to a customer
     * (Admin/Manager action, see the "Send re-engagement" control on the
     * Lost Customers page).
     */
    public function store(SendReengagementRequest $request, Customer $customer)
    {
        $this->crmService->sendReengagement(
            $customer,
            $request->validated('channel'),
            $request->validated('message'),
            $request->user(),
        );

        return back()->with('success', "Re-engagement message sent to {$customer->name}.");
    }
}

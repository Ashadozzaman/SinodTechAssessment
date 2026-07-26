<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lost Customer Threshold
    |--------------------------------------------------------------------------
    |
    | Number of days without a sale before a customer is considered "lost".
    | This is never stored as a status flag — Customer::scopeLost() always
    | computes it live off the sales table (see ARCHITECTURE.md §5.2).
    |
    */

    'lost_customer_days' => env('CRM_LOST_CUSTOMER_DAYS', 90),

];

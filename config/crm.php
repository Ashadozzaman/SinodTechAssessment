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

    /*
    |--------------------------------------------------------------------------
    | KPI Award Points
    |--------------------------------------------------------------------------
    |
    | Points added to an employee's kpi_score when a sale is completed for
    | a customer they hold an active assignment for (ARCHITECTURE.md §5.3).
    |
    */

    'kpi_award_points' => env('CRM_KPI_AWARD_POINTS', 10),

];

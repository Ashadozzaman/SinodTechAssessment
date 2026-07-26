<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LostCustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * `last_purchase_at` comes from the `sales_max_sale_date` attribute
     * added by the controller's `withMax('sales', 'sale_date')` — still
     * derived live off `sales`, just computed in one query for the whole
     * paginated page instead of one query per row (ARCHITECTURE.md §4.2).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'last_purchase_at' => $this->sales_max_sale_date,
            'created_at' => $this->created_at,
        ];
    }
}

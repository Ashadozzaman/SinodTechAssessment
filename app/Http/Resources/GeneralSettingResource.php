<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'company_name' => $this->company_name,
            'currency_symbol' => $this->currency_symbol,
            'logo_url' => $this->logo_url,
        ];
    }
}

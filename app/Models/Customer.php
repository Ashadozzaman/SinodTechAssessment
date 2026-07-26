<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Derived, not stored (ARCHITECTURE.md §4.2) — matches the same
     * unfiltered-by-status definition of "a purchase" used by
     * Customer::scopeLost().
     */
    public function lastPurchaseAt(): ?Carbon
    {
        $maxDate = $this->sales()->max('sale_date');

        return $maxDate ? Carbon::parse($maxDate) : null;
    }

    public function purchaseFrequency(): int
    {
        return $this->sales()->count();
    }

    /**
     * Customers with no sale in the last N days (ARCHITECTURE.md §5.2).
     * Computed live off `sales` — never a stored status column.
     */
    public function scopeLost(Builder $query, ?int $days = null): Builder
    {
        $days ??= config('crm.lost_customer_days');

        return $query->whereDoesntHave('sales', fn (Builder $query) => $query->where('sale_date', '>=', now()->subDays($days)));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}

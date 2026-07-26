<?php

namespace App\Models;

use App\Enums\EngagementChannel;
use App\Enums\EngagementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEngagement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'channel',
        'message',
        'status',
        'sent_at',
        'triggered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => EngagementChannel::class,
            'status' => EngagementStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}

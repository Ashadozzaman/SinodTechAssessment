<?php

namespace App\Enums;

enum EngagementStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Simulated = 'simulated';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Simulated => 'Simulated',
        };
    }
}

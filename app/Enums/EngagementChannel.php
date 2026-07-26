<?php

namespace App\Enums;

enum EngagementChannel: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
        };
    }
}

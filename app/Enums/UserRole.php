<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User => 'User',
        };
    }

    public function canAccessAdmin(): bool
    {
        return match ($this) {
            self::Admin => true,
            self::User => false,
        };
    }
}

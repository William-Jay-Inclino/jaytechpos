<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Store Manager',
            self::Cashier => 'Cashier',
        };
    }

    public function canAccessAdmin(): bool
    {
        return match ($this) {
            self::Admin => true,
            self::Manager => false,
            self::Cashier => false,
        };
    }
}

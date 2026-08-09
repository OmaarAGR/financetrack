<?php

namespace App\Enums;

enum AccountType: string
{
    case Bank = 'bank';
    case Wallet = 'wallet';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case Savings = 'savings';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Cuenta bancaria',
            self::Wallet => 'Billetera digital',
            self::Cash => 'Efectivo',
            self::CreditCard => 'Tarjeta de crédito',
            self::Savings => 'Cuenta de ahorros',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bank, self::Savings => 'banknotes',
            self::Wallet => 'wallet',
            self::Cash => 'wallet',
            self::CreditCard => 'wallet',
        };
    }
}

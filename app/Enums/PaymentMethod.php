<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::DebitCard => 'Tarjeta débito',
            self::CreditCard => 'Tarjeta crédito',
            self::BankTransfer => 'Transferencia bancaria',
            self::Other => 'Otro',
        };
    }
}

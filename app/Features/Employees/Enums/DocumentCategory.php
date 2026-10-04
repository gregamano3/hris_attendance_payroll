<?php

namespace App\Features\Employees\Enums;

use App\Shared\Concerns\HasOptions;

enum DocumentCategory: string
{
    use HasOptions;

    case Contract = 'contract';
    case GovernmentId = 'government_id';
    case Certificate = 'certificate';
    case Evaluation = 'evaluation';
    case Clearance = 'clearance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Contract / appointment',
            self::GovernmentId => 'Government ID',
            self::Certificate => 'Certificate / diploma',
            self::Evaluation => 'Performance evaluation',
            self::Clearance => 'Clearance',
            self::Other => 'Other',
        };
    }
}

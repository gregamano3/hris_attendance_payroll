<?php

namespace App\Features\Recruitment\Enums;

enum ApplicantStage: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Applied => 'secondary',
            self::Screening, self::Interview => 'info',
            self::Offer => 'warning',
            self::Hired => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Stages shown as pipeline columns.
     *
     * @return list<self>
     */
    public static function pipeline(): array
    {
        return [self::Applied, self::Screening, self::Interview, self::Offer, self::Hired, self::Rejected];
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Hired, self::Rejected], true);
    }
}

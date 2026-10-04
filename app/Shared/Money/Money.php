<?php

namespace App\Shared\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable Philippine peso amount stored as integer centavos to avoid
 * floating point rounding errors in payroll computations.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(public int $centavos) {}

    public static function ofCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    /**
     * Build from a peso amount such as "1,234.56", 1234.5 or 1234.
     */
    public static function ofPesos(string|int|float $pesos): self
    {
        $normalized = str_replace([',', ' ', '₱'], '', trim((string) $pesos));

        if (! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException("Invalid peso amount [{$pesos}].");
        }

        $negative = str_starts_with($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($normalized, '-')), 2, '0');

        // Round half up on the third decimal.
        $fraction = str_pad($fraction, 3, '0');
        $centavos = (int) $whole * 100 + (int) substr($fraction, 0, 2) + ((int) $fraction[2] >= 5 ? 1 : 0);

        return new self($negative ? -$centavos : $centavos);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->centavos + $other->centavos);
    }

    public function minus(self $other): self
    {
        return new self($this->centavos - $other->centavos);
    }

    /**
     * Multiply by a factor (rate, hours, percentage...) rounding half up.
     */
    public function multipliedBy(int|float|string $factor): self
    {
        return new self(self::roundHalfUp($this->centavos * (float) $factor));
    }

    public function dividedBy(int|float $divisor): self
    {
        if ((float) $divisor === 0.0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return new self(self::roundHalfUp($this->centavos / $divisor));
    }

    public function max(self $other): self
    {
        return $this->centavos >= $other->centavos ? $this : $other;
    }

    public function min(self $other): self
    {
        return $this->centavos <= $other->centavos ? $this : $other;
    }

    public function isZero(): bool
    {
        return $this->centavos === 0;
    }

    public function isNegative(): bool
    {
        return $this->centavos < 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->centavos > $other->centavos;
    }

    public function isLessThan(self $other): bool
    {
        return $this->centavos < $other->centavos;
    }

    public function equals(self $other): bool
    {
        return $this->centavos === $other->centavos;
    }

    /**
     * Decimal string without grouping, e.g. "1234.50".
     */
    public function toDecimal(): string
    {
        $sign = $this->centavos < 0 ? '-' : '';
        $abs = abs($this->centavos);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }

    public function toFloat(): float
    {
        return $this->centavos / 100;
    }

    /**
     * Human readable amount, e.g. "₱1,234.50".
     */
    public function format(bool $withSymbol = true): string
    {
        $sign = $this->centavos < 0 ? '-' : '';
        $formatted = number_format(abs($this->centavos) / 100, 2);

        return $sign.($withSymbol ? '₱' : '').$formatted;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimal();
    }

    private static function roundHalfUp(float $value): int
    {
        return (int) round($value, 0, PHP_ROUND_HALF_UP);
    }
}

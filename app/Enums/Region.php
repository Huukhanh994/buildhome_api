<?php

declare(strict_types=1);

namespace App\Enums;

enum Region: string
{
    case HoChiMinh   = 'ho_chi_minh';
    case Hanoi       = 'hanoi';
    case MienTrung   = 'mien_trung';
    case RuralVietnam = 'rural_vietnam';
    case Default_    = 'default';

    /** Regional price multiplier applied to all material + labour costs */
    public function multiplier(): float
    {
        return match ($this) {
            self::HoChiMinh    => 1.15,
            self::Hanoi        => 1.10,
            self::MienTrung    => 1.00,
            self::RuralVietnam => 0.90,
            self::Default_     => 1.00,
        };
    }

    /** Price overrides for key materials (VND / unit).
     *  Null = use global default from PricingService. */
    public function cementPriceOverride(): ?int
    {
        return match ($this) {
            self::HoChiMinh => 95_000,
            self::Hanoi     => 93_000,
            default         => null,
        };
    }
}

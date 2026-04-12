<?php

declare(strict_types=1);

namespace App\Enums;

enum HouseType: string
{
    case ThaiRoof    = 'thai_roof';
    case JapaneseRoof = 'japanese_roof';
    case GardenVilla = 'garden_villa';
    case OneFloor    = 'one_floor';
    case TwoFloor    = 'two_floor';

    /** Base cost per m² in VND */
    public function costPerM2(): int
    {
        return match ($this) {
            self::ThaiRoof     => 6_500_000,
            self::JapaneseRoof => 7_000_000,
            self::GardenVilla  => 9_000_000,
            self::OneFloor     => 5_500_000,
            self::TwoFloor     => 6_000_000,
        };
    }
}

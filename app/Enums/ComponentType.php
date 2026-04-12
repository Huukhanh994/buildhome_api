<?php

declare(strict_types=1);

namespace App\Enums;

enum ComponentType: string
{
    case RoofMain    = 'roof_main';
    case RoofSub     = 'roof_sub';
    case WallFront   = 'wall_front';
    case DoorMain    = 'door_main';
    case WindowFront = 'window_front';
    case Column      = 'column';
    case Foundation  = 'foundation';
    case Balcony     = 'balcony';
    case Garden      = 'garden';

    public function label(): string
    {
        return match ($this) {
            self::RoofMain    => 'Mái chính',
            self::RoofSub     => 'Mái phụ (diềm)',
            self::WallFront   => 'Tường bao',
            self::DoorMain    => 'Cửa chính',
            self::WindowFront => 'Cửa sổ',
            self::Column      => 'Cột',
            self::Foundation  => 'Móng',
            self::Balcony     => 'Ban công',
            self::Garden      => 'Sân vườn',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::RoofMain, self::RoofSub          => 'roof',
            self::Column, self::Foundation          => 'structure',
            self::WallFront, self::DoorMain,
            self::WindowFront                       => 'wall_system',
            self::Balcony, self::Garden             => 'exterior',
        };
    }
}

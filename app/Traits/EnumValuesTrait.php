<?php

namespace App\Traits;

trait EnumValuesTrait
{
    public static function values(): array
    {
        return array_column(self::cases(), 'name' , 'value');
    }
}

<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LogicType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case LOGIC_AND = 'and';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOGIC_AND => '逻辑与',
        };
    }
}

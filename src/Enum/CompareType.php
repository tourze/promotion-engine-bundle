<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum CompareType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case EQUAL = 'equal';
    case NOT_EQUAL = 'not-equal';
    case GTE = 'gte';
    case LTE = 'lte';
    case IN = 'in';
    case NOT_IN = 'not-in';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUAL => '等于',
            self::NOT_EQUAL => '不等于',
            self::GTE => '大于等于',
            self::LTE => '小于等于',
            self::IN => '包含于',
            self::NOT_IN => '不包含于',
        };
    }
}

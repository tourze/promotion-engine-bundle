<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum DiscountType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case REDUCTION = 'reduction';
    case DISCOUNT = 'discount';
    case FREE_FREIGHT = 'free-freight';
    case BUY_GIVE = 'buy-give';
    case BUY_N_GET_M = 'buy_n_get_m';
    case PROGRESSIVE_DISCOUNT_SCHEME = 'progressive_discount_scheme';
    case SPEND_THRESHOLD_WITH_ADD_ON = 'spend_threshold_with_add_on';

    public function getLabel(): string
    {
        return match ($this) {
            self::REDUCTION => '整单减价',
            self::DISCOUNT => '整单打折',
            self::FREE_FREIGHT => '免邮费',
            self::BUY_GIVE => '赠品',
            self::BUY_N_GET_M => '买N送M',
            self::PROGRESSIVE_DISCOUNT_SCHEME => '累进折扣',
            self::SPEND_THRESHOLD_WITH_ADD_ON => '加价购',
        };
    }
}

<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ActivityType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case LIMITED_TIME_DISCOUNT = 'limited_time_discount';
    case LIMITED_TIME_SECKILL = 'limited_time_seckill';
    case LIMITED_QUANTITY_PURCHASE = 'limited_quantity_purchase';

    public function getLabel(): string
    {
        return match ($this) {
            self::LIMITED_TIME_DISCOUNT => '限时折扣',
            self::LIMITED_TIME_SECKILL => '限时秒杀',
            self::LIMITED_QUANTITY_PURCHASE => '限量抢购',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toSelect(): array
    {
        $items = [];
        foreach (self::cases() as $case) {
            $items[$case->getLabel()] = $case->value;
        }

        return $items;
    }
}

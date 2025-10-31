<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ActivityStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case ACTIVE = 'active';
    case FINISHED = 'finished';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待开始',
            self::ACTIVE => '进行中',
            self::FINISHED => '已结束',
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

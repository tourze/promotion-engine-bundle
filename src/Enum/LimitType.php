<?php

namespace PromotionEngineBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LimitType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ORDER_PRICE = 'order-price';
    case FIRST_PURCHASE_USER = 'first-purchase-user';
    case SECONDARY_PURCHASE_USER = 'secondary-purchase-user';
    case REPURCHASE_USER = 'repurchase-user';
    case SPU_ID = 'spu-id';
    case SKU_ID = 'sku-id';
    case SPU_PER_QUANTITY = 'spu-per-quantity';
    case SKU_PER_QUANTITY = 'sku-per-quantity';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORDER_PRICE => '整单价格',
            self::FIRST_PURCHASE_USER => '首次购买用户',
            self::SECONDARY_PURCHASE_USER => '二次购买用户',
            self::REPURCHASE_USER => '复购用户',
            self::SPU_ID => 'SPU ID',
            self::SKU_ID => 'SKU ID',
            self::SPU_PER_QUANTITY => 'SPU单品数量',
            self::SKU_PER_QUANTITY => 'SKU单品数量',
        };
    }
}

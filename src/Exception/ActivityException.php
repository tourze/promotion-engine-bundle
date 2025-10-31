<?php

namespace PromotionEngineBundle\Exception;

class ActivityException extends \Exception
{
    public static function activityConflict(string $message): self
    {
        return new self("活动冲突: {$message}");
    }

    public static function invalidTimeRange(string $message): self
    {
        return new self("时间范围无效: {$message}");
    }

    public static function activityNotFound(string $activityId): self
    {
        return new self("活动不存在: {$activityId}");
    }

    public static function activityExpired(string $activityId): self
    {
        return new self("活动已过期: {$activityId}");
    }

    public static function soldOut(string $activityId): self
    {
        return new self("活动商品已售罄: {$activityId}");
    }

    public static function invalidProductIds(string $message): self
    {
        return new self("商品ID无效: {$message}");
    }

    public static function insufficientStock(string $message): self
    {
        return new self("库存不足: {$message}");
    }

    public static function stockOperationFailed(string $message): self
    {
        return new self("库存操作失败: {$message}");
    }
}

<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class CreateTimeLimitActivityParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '活动名称')]
        #[Assert\NotBlank(message: '活动名称不能为空')]
        public string $name,

        #[MethodParam(description: '活动描述')]
        #[Assert\NotBlank(message: '活动描述不能为空')]
        public string $description,

        #[MethodParam(description: '开始时间(YYYY-MM-DD HH:mm:ss)')]
        #[Assert\NotBlank(message: '开始时间不能为空')]
        public string $startTime,

        #[MethodParam(description: '结束时间(YYYY-MM-DD HH:mm:ss)')]
        #[Assert\NotBlank(message: '结束时间不能为空')]
        public string $endTime,

        #[MethodParam(description: '活动类型')]
        #[Assert\NotBlank(message: '活动类型不能为空')]
        public string $activityType,

        /** @var string[] */
        #[MethodParam(description: '参与商品ID列表')]
        #[Assert\NotBlank(message: '商品ID列表不能为空')]
        #[Assert\Type(type: 'array', message: '商品ID列表必须是数组')]
        #[Assert\Count(min: 1, minMessage: '商品ID列表不能为空')]
        public array $productIds,

        #[MethodParam(description: '活动优先级,默认为0')]
        public int $priority = 0,

        #[MethodParam(description: '是否独占(同商品不能参与其他活动),默认为false')]
        public bool $exclusive = false,

        #[MethodParam(description: '活动限量(限量抢购专用),默认为null')]
        public ?int $totalLimit = null,

        #[MethodParam(description: '是否启用预热,默认为false')]
        public bool $preheatEnabled = false,

        #[MethodParam(description: '预热开始时间(YYYY-MM-DD HH:mm:ss),默认为null')]
        public ?string $preheatStartTime = null,
    ) {
    }
}

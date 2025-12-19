<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class ApplyActivityToProductsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '活动ID')]
        #[Assert\NotBlank(message: '活动ID不能为空')]
        public string $activityId,

        /**
         * @var array<array<string, mixed>>
         */
        #[MethodParam(description: '商品信息列表')]
        #[Assert\NotBlank(message: '商品列表不能为空')]
        #[Assert\Type(type: 'array', message: '商品列表必须是数组')]
        #[Assert\Count(min: 1, minMessage: '商品列表不能为空')]
        public array $products,
    ) {
    }
}

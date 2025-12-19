<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class CalculateActivityDiscountParam implements RpcParamInterface
{
    public function __construct(
        /**
         * @var array<array{productId: string, skuId: string, quantity: int, price: float}>
         */
        #[MethodParam(description: '商品列表,包含productId、skuId、quantity、price')]
        #[Assert\NotBlank(message: '商品列表不能为空')]
        #[Assert\Type(type: 'array', message: '商品列表必须是数组')]
        #[Assert\Count(min: 1, minMessage: '商品列表不能为空')]
        public array $items,

        #[MethodParam(description: '用户ID(可选)')]
        public ?string $userId = null,
    ) {
    }
}

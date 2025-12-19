<?php

namespace PromotionEngineBundle\Procedure;

use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Param\CalculateActivityDiscountParam;
use PromotionEngineBundle\Service\ActivityDiscountService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[MethodExpose(method: 'CalculateActivityDiscount')]
#[MethodTag(name: '限时活动模块')]
#[MethodDoc(summary: '计算活动优惠')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Autoconfigure(public: true)]
#[Log]
class CalculateActivityDiscount extends LockableProcedure
{
    public function __construct(
        private readonly ActivityDiscountService $activityDiscountService,
    ) {
    }

    /**
     * @phpstan-param CalculateActivityDiscountParam $param
     */
    public function execute(CalculateActivityDiscountParam|RpcParamInterface $param): ArrayResult
    {
        $input = $this->buildInput($param);
        $result = $this->activityDiscountService->calculateDiscount($input);

        return $result->toArray();
    }

    private function buildInput(CalculateActivityDiscountParam $param): CalculateActivityDiscountInput
    {
        $items = array_map(function (array $itemData) {
            // 根据 PHPDoc 类型定义，这些字段已经保证存在，无需 isset 检查
            return new CalculateActivityDiscountItem(
                productId: $itemData['productId'],
                skuId: $itemData['skuId'],
                quantity: $itemData['quantity'],
                price: $itemData['price']
            );
        }, $param->items);

        return new CalculateActivityDiscountInput(
            items: $items,
            userId: $param->userId
        );
    }

    /**
     * @return array<string, mixed>|null
     */
}

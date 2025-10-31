<?php

namespace PromotionEngineBundle\Procedure;

use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Service\ActivityDiscountService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
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
    /**
     * @var array<array{productId: string, skuId: string, quantity: int, price: float}>
     */
    #[MethodParam(description: '商品列表，包含productId、skuId、quantity、price')]
    public array $items;

    #[MethodParam(description: '用户ID（可选）')]
    public ?string $userId = null;

    public function __construct(
        private readonly ActivityDiscountService $activityDiscountService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $input = $this->buildInput();
        $result = $this->activityDiscountService->calculateDiscount($input);

        return $result->toArray();
    }

    private function buildInput(): CalculateActivityDiscountInput
    {
        $items = array_map(function (array $itemData) {
            // 根据 PHPDoc 类型定义，这些字段已经保证存在，无需 isset 检查
            return new CalculateActivityDiscountItem(
                productId: $itemData['productId'],
                skuId: $itemData['skuId'],
                quantity: $itemData['quantity'],
                price: $itemData['price']
            );
        }, $this->items);

        return new CalculateActivityDiscountInput(
            items: $items,
            userId: $this->userId
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getMockResult(): ?array
    {
        return [
            'success' => true,
            'message' => '计算成功',
            'items' => [
                [
                    'productId' => '1234567890123456789',
                    'skuId' => '9876543210987654321',
                    'quantity' => 2,
                    'originalPrice' => 100.0,
                    'originalAmount' => 200.0,
                    'discountAmount' => 20.0,
                    'finalPrice' => 90.0,
                    'finalAmount' => 180.0,
                    'savings' => 20.0,
                    'discountRate' => 10.0,
                    'appliedActivities' => [
                        [
                            'activityId' => '1111111111111111111',
                            'activityName' => '春季限时折扣',
                            'activityType' => 'limited_time_discount',
                            'discountAmount' => 20.0,
                        ],
                    ],
                    'discountDetails' => [
                        [
                            'activityId' => '1111111111111111111',
                            'activityName' => '春季限时折扣',
                            'activityType' => 'limited_time_discount',
                            'discountType' => 'discount',
                            'discountValue' => 90.0,
                            'discountAmount' => 20.0,
                            'reason' => '参与限时折扣活动',
                            'metadata' => [
                                'activityStartTime' => '2024-03-01 00:00:00',
                                'activityEndTime' => '2024-03-31 23:59:59',
                                'activityPrice' => '90.00',
                                'originalPrice' => 100.0,
                                'quantity' => 2,
                                'limitPerUser' => 10,
                                'remainingStock' => 100,
                            ],
                        ],
                    ],
                ],
            ],
            'originalTotalAmount' => 200.0,
            'discountTotalAmount' => 20.0,
            'finalTotalAmount' => 180.0,
            'totalSavings' => 20.0,
            'discountRate' => 10.0,
            'appliedActivities' => [
                [
                    'activityId' => '1111111111111111111',
                    'activityName' => '春季限时折扣',
                    'activityType' => 'limited_time_discount',
                    'discountAmount' => 20.0,
                ],
            ],
            'discountDetails' => [
                [
                    'activityId' => '1111111111111111111',
                    'activityName' => '春季限时折扣',
                    'activityType' => 'limited_time_discount',
                    'discountType' => 'discount',
                    'discountValue' => 90.0,
                    'discountAmount' => 20.0,
                    'reason' => '参与限时折扣活动',
                    'metadata' => [
                        'activityStartTime' => '2024-03-01 00:00:00',
                        'activityEndTime' => '2024-03-31 23:59:59',
                        'activityPrice' => '90.00',
                        'originalPrice' => 100.0,
                        'quantity' => 2,
                        'limitPerUser' => 10,
                        'remainingStock' => 100,
                    ],
                ],
            ],
        ];
    }
}

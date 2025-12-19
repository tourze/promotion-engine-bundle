<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Param\CalculateActivityDiscountParam;
use Symfony\Component\Validator\Validation;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * CalculateActivityDiscountParam 单元测试
 *
 * @internal
 */
#[CoversClass(CalculateActivityDiscountParam::class)]
final class CalculateActivityDiscountParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'PROD001',
                    'skuId' => 'SKU001',
                    'quantity' => 2,
                    'price' => 100.0,
                ],
            ],
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredValues(): void
    {
        $items = [
            [
                'productId' => 'PROD001',
                'skuId' => 'SKU001',
                'quantity' => 2,
                'price' => 100.0,
            ],
        ];

        $param = new CalculateActivityDiscountParam(
            items: $items,
        );

        $this->assertSame($items, $param->items);
        $this->assertNull($param->userId);
    }

    public function testConstructorWithOptionalUserId(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'PROD001',
                    'skuId' => 'SKU001',
                    'quantity' => 2,
                    'price' => 100.0,
                ],
            ],
            userId: 'USER123',
        );

        $this->assertSame('USER123', $param->userId);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(CalculateActivityDiscountParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testValidationPassesWithValidData(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'PROD001',
                    'skuId' => 'SKU001',
                    'quantity' => 2,
                    'price' => 100.0,
                ],
            ],
            userId: 'USER123',
        );
        $violations = $validator->validate($param);

        $this->assertCount(0, $violations);
    }

    public function testValidationFailsWithEmptyItems(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new CalculateActivityDiscountParam(
            items: [],
        );
        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }
}

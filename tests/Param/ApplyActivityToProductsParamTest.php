<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Param\ApplyActivityToProductsParam;
use Symfony\Component\Validator\Validation;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * ApplyActivityToProductsParam 单元测试
 *
 * @internal
 */
#[CoversClass(ApplyActivityToProductsParam::class)]
final class ApplyActivityToProductsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new ApplyActivityToProductsParam(
            activityId: '1234567890123456789',
            products: [
                [
                    'productId' => 'PROD001',
                    'activityPrice' => '99.00',
                    'limitPerUser' => 5,
                    'activityStock' => 100,
                ],
            ],
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredValues(): void
    {
        $products = [
            [
                'productId' => 'PROD001',
                'activityPrice' => '99.00',
                'limitPerUser' => 5,
                'activityStock' => 100,
            ],
        ];

        $param = new ApplyActivityToProductsParam(
            activityId: '1234567890123456789',
            products: $products,
        );

        $this->assertSame('1234567890123456789', $param->activityId);
        $this->assertSame($products, $param->products);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ApplyActivityToProductsParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testValidationPassesWithValidData(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new ApplyActivityToProductsParam(
            activityId: '1234567890123456789',
            products: [
                [
                    'productId' => 'PROD001',
                    'activityPrice' => '99.00',
                    'limitPerUser' => 5,
                    'activityStock' => 100,
                ],
            ],
        );
        $violations = $validator->validate($param);

        $this->assertCount(0, $violations);
    }

    public function testValidationFailsWithEmptyActivityId(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new ApplyActivityToProductsParam(
            activityId: '',
            products: [
                [
                    'productId' => 'PROD001',
                    'activityPrice' => '99.00',
                ],
            ],
        );
        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWithEmptyProducts(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new ApplyActivityToProductsParam(
            activityId: '1234567890123456789',
            products: [],
        );
        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }
}

<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ApplyActivityProductInput;

/**
 * @internal
 */
#[CoversClass(ApplyActivityProductInput::class)]
class ApplyActivityProductInputTest extends TestCase
{
    public function testConstructor(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-123',
            activityPrice: '99.99',
            limitPerUser: 3,
            activityStock: 100
        );

        $this->assertEquals('prod-123', $input->productId);
        $this->assertEquals('99.99', $input->activityPrice);
        $this->assertEquals(3, $input->limitPerUser);
        $this->assertEquals(100, $input->activityStock);
    }

    public function testConstructorWithDefaults(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-456',
            activityPrice: '49.99'
        );

        $this->assertEquals('prod-456', $input->productId);
        $this->assertEquals('49.99', $input->activityPrice);
        $this->assertEquals(1, $input->limitPerUser);
        $this->assertEquals(0, $input->activityStock);
    }

    public function testIsValidPriceWithValidPrice(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99');
        $this->assertTrue($input->isValidPrice());
    }

    public function testIsValidPriceWithZeroPrice(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '0.00');
        $this->assertTrue($input->isValidPrice());
    }

    public function testIsValidPriceWithNegativePrice(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '-10.00');
        $this->assertFalse($input->isValidPrice());
    }

    public function testIsValidPriceWithInvalidString(): void
    {
        $input = new ApplyActivityProductInput('prod-1', 'invalid');
        $this->assertFalse($input->isValidPrice());
    }

    public function testIsValidLimitPerUserWithValidLimit(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', 5);
        $this->assertTrue($input->isValidLimitPerUser());
    }

    public function testIsValidLimitPerUserWithZeroLimit(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', 0);
        $this->assertFalse($input->isValidLimitPerUser());
    }

    public function testIsValidLimitPerUserWithNegativeLimit(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', -1);
        $this->assertFalse($input->isValidLimitPerUser());
    }

    public function testIsValidActivityStockWithValidStock(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', 1, 100);
        $this->assertTrue($input->isValidActivityStock());
    }

    public function testIsValidActivityStockWithZeroStock(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', 1, 0);
        $this->assertTrue($input->isValidActivityStock());
    }

    public function testIsValidActivityStockWithNegativeStock(): void
    {
        $input = new ApplyActivityProductInput('prod-1', '99.99', 1, -1);
        $this->assertFalse($input->isValidActivityStock());
    }

    public function testIsValidWithValidData(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-1',
            activityPrice: '99.99',
            limitPerUser: 3,
            activityStock: 100
        );

        $this->assertTrue($input->isValid());
    }

    public function testIsValidWithInvalidPrice(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-1',
            activityPrice: 'invalid',
            limitPerUser: 3,
            activityStock: 100
        );

        $this->assertFalse($input->isValid());
    }

    public function testIsValidWithInvalidLimit(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-1',
            activityPrice: '99.99',
            limitPerUser: 0,
            activityStock: 100
        );

        $this->assertFalse($input->isValid());
    }

    public function testIsValidWithInvalidStock(): void
    {
        $input = new ApplyActivityProductInput(
            productId: 'prod-1',
            activityPrice: '99.99',
            limitPerUser: 3,
            activityStock: -1
        );

        $this->assertFalse($input->isValid());
    }
}

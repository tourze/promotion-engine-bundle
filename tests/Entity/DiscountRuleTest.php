<?php

namespace PromotionEngineBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Enum\DiscountType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountRule::class)]
final class DiscountRuleTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        return new DiscountRule();
    }

    /**
     * 测试活动ID属性的 getter 和 setter
     */
    public function testGetSetActivityId(): void
    {
        $discountRule = new DiscountRule();
        $activityId = '123456789012345678';

        $discountRule->setActivityId($activityId);
        $this->assertEquals($activityId, $discountRule->getActivityId());
    }

    /**
     * 测试折扣类型属性的 getter 和 setter
     */
    public function testGetSetDiscountType(): void
    {
        $discountRule = new DiscountRule();
        $discountType = DiscountType::REDUCTION;

        $discountRule->setDiscountType($discountType);
        $this->assertEquals($discountType, $discountRule->getDiscountType());
    }

    /**
     * 测试折扣值属性的 getter 和 setter
     */
    public function testGetSetDiscountValue(): void
    {
        $discountRule = new DiscountRule();
        $discountValue = '10.50';

        $discountRule->setDiscountValue($discountValue);
        $this->assertEquals($discountValue, $discountRule->getDiscountValue());
    }

    /**
     * 测试最低消费门槛属性的 getter 和 setter
     */
    public function testGetSetMinAmount(): void
    {
        $discountRule = new DiscountRule();
        $minAmount = '100.00';

        $discountRule->setMinAmount($minAmount);
        $this->assertEquals($minAmount, $discountRule->getMinAmount());

        // 测试空值
        $discountRule->setMinAmount(null);
        $this->assertNull($discountRule->getMinAmount());
    }

    /**
     * 测试最大优惠金额属性的 getter 和 setter
     */
    public function testGetSetMaxDiscountAmount(): void
    {
        $discountRule = new DiscountRule();
        $maxDiscountAmount = '50.00';

        $discountRule->setMaxDiscountAmount($maxDiscountAmount);
        $this->assertEquals($maxDiscountAmount, $discountRule->getMaxDiscountAmount());

        // 测试空值
        $discountRule->setMaxDiscountAmount(null);
        $this->assertNull($discountRule->getMaxDiscountAmount());
    }

    /**
     * 测试满足数量要求属性的 getter 和 setter
     */
    public function testGetSetRequiredQuantity(): void
    {
        $discountRule = new DiscountRule();
        $requiredQuantity = 5;

        $discountRule->setRequiredQuantity($requiredQuantity);
        $this->assertEquals($requiredQuantity, $discountRule->getRequiredQuantity());

        // 测试空值
        $discountRule->setRequiredQuantity(null);
        $this->assertNull($discountRule->getRequiredQuantity());
    }

    /**
     * 测试赠送数量属性的 getter 和 setter
     */
    public function testGetSetGiftQuantity(): void
    {
        $discountRule = new DiscountRule();
        $giftQuantity = 2;

        $discountRule->setGiftQuantity($giftQuantity);
        $this->assertEquals($giftQuantity, $discountRule->getGiftQuantity());

        // 测试空值
        $discountRule->setGiftQuantity(null);
        $this->assertNull($discountRule->getGiftQuantity());
    }

    /**
     * 测试赠品商品ID列表属性的 getter 和 setter
     */
    public function testGetSetGiftProductIds(): void
    {
        $discountRule = new DiscountRule();
        $giftProductIds = ['product1', 'product2', 'product3'];

        $discountRule->setGiftProductIds($giftProductIds);
        $this->assertEquals($giftProductIds, $discountRule->getGiftProductIds());

        // 测试空值
        $discountRule->setGiftProductIds(null);
        $this->assertNull($discountRule->getGiftProductIds());
    }

    /**
     * 测试扩展配置属性的 getter 和 setter
     */
    public function testGetSetConfig(): void
    {
        $discountRule = new DiscountRule();
        $config = [
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ];

        $discountRule->setConfig($config);
        $this->assertEquals($config, $discountRule->getConfig());

        // 测试空值
        $discountRule->setConfig(null);
        $this->assertNull($discountRule->getConfig());
    }

    /**
     * 测试有效属性的 getter 和 setter
     */
    public function testGetSetValid(): void
    {
        $discountRule = new DiscountRule();

        $discountRule->setValid(true);
        $this->assertTrue($discountRule->isValid());

        $discountRule->setValid(false);
        $this->assertFalse($discountRule->isValid());

        $discountRule->setValid(null);
        $this->assertNull($discountRule->isValid());
    }

    /**
     * 测试获取折扣值为浮点数
     */
    public function testGetDiscount(): void
    {
        $discountRule = new DiscountRule();
        $discountRule->setDiscountValue('15.75');

        $this->assertEquals(15.75, $discountRule->getDiscount());
    }

    /**
     * 测试获取最低金额为浮点数
     */
    public function testGetMinAmountAsFloat(): void
    {
        $discountRule = new DiscountRule();

        // 测试有值的情况
        $discountRule->setMinAmount('100.50');
        $this->assertEquals(100.5, $discountRule->getMinAmountAsFloat());

        // 测试空值的情况
        $discountRule->setMinAmount(null);
        $this->assertEquals(0.0, $discountRule->getMinAmountAsFloat());
    }

    /**
     * 测试获取最大优惠金额为浮点数
     */
    public function testGetMaxDiscountAmountAsFloat(): void
    {
        $discountRule = new DiscountRule();

        // 测试有值的情况
        $discountRule->setMaxDiscountAmount('50.25');
        $this->assertEquals(50.25, $discountRule->getMaxDiscountAmountAsFloat());

        // 测试空值的情况
        $discountRule->setMaxDiscountAmount(null);
        $this->assertNull($discountRule->getMaxDiscountAmountAsFloat());
    }

    /**
     * 测试金额资格验证
     */
    public function testIsAmountQualified(): void
    {
        $discountRule = new DiscountRule();
        $discountRule->setMinAmount('100.00');

        $this->assertTrue($discountRule->isAmountQualified(100.0));
        $this->assertTrue($discountRule->isAmountQualified(150.0));
        $this->assertFalse($discountRule->isAmountQualified(99.99));
    }

    /**
     * 测试数量资格验证
     */
    public function testIsQuantityQualified(): void
    {
        $discountRule = new DiscountRule();

        // 测试没有数量要求的情况
        $discountRule->setRequiredQuantity(null);
        $this->assertTrue($discountRule->isQuantityQualified(1));
        $this->assertTrue($discountRule->isQuantityQualified(100));

        // 测试有数量要求的情况
        $discountRule->setRequiredQuantity(5);
        $this->assertTrue($discountRule->isQuantityQualified(5));
        $this->assertTrue($discountRule->isQuantityQualified(10));
        $this->assertFalse($discountRule->isQuantityQualified(4));
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $discountRule = new DiscountRule();

        // ID 为空时应返回空字符串
        $this->assertEquals('', (string) $discountRule);

        // 设置折扣类型后应返回对应标签
        $discountRule->setDiscountType(DiscountType::REDUCTION);

        // 使用反射设置 ID，因为 ID 是生成的
        $reflection = new \ReflectionClass($discountRule);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discountRule, '123456789');

        $this->assertEquals(DiscountType::REDUCTION->getLabel(), (string) $discountRule);
    }

    /**
     * 测试 toAdminArray 方法
     */
    public function testToAdminArray(): void
    {
        $discountRule = new DiscountRule();
        $discountRule->setActivityId('123456789012345678');
        $discountRule->setDiscountType(DiscountType::REDUCTION);
        $discountRule->setDiscountValue('10.00');
        $discountRule->setMinAmount('100.00');
        $discountRule->setValid(true);

        $adminArray = $discountRule->toAdminArray();

        $this->assertArrayHasKey('id', $adminArray);
        $this->assertArrayHasKey('activityId', $adminArray);
        $this->assertArrayHasKey('discountType', $adminArray);
        $this->assertArrayHasKey('discountTypeLabel', $adminArray);
        $this->assertArrayHasKey('discountValue', $adminArray);
        $this->assertArrayHasKey('minAmount', $adminArray);
        $this->assertArrayHasKey('valid', $adminArray);
        $this->assertArrayHasKey('createdAt', $adminArray);
        $this->assertArrayHasKey('updatedAt', $adminArray);

        $this->assertEquals('123456789012345678', $adminArray['activityId']);
        $this->assertEquals(DiscountType::REDUCTION->value, $adminArray['discountType']);
        $this->assertEquals(DiscountType::REDUCTION->getLabel(), $adminArray['discountTypeLabel']);
        $this->assertEquals('10.00', $adminArray['discountValue']);
        $this->assertEquals('100.00', $adminArray['minAmount']);
        $this->assertTrue($adminArray['valid']);
    }

    /**
     * 测试 retrieveAdminArray 方法
     */
    public function testRetrieveAdminArray(): void
    {
        $discountRule = new DiscountRule();
        $discountRule->setActivityId('123456789012345678');
        $discountRule->setDiscountType(DiscountType::DISCOUNT);

        $adminArray = $discountRule->retrieveAdminArray();
        $toAdminArray = $discountRule->toAdminArray();

        $this->assertEquals($toAdminArray, $adminArray);
    }

    /**
     * 测试时间戳相关方法
     */
    public function testTimestampMethods(): void
    {
        $discountRule = new DiscountRule();
        $now = new \DateTimeImmutable();

        $discountRule->setCreateTime($now);
        $this->assertEquals($now, $discountRule->getCreateTime());

        $updateTime = new \DateTimeImmutable();
        $discountRule->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $discountRule->getUpdateTime());
    }

    /**
     * 测试跟踪人员相关方法
     */
    public function testTrackingMethods(): void
    {
        $discountRule = new DiscountRule();
        $creator = 'admin';
        $updater = 'manager';

        $discountRule->setCreatedBy($creator);
        $this->assertEquals($creator, $discountRule->getCreatedBy());

        $discountRule->setUpdatedBy($updater);
        $this->assertEquals($updater, $discountRule->getUpdatedBy());
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return \Generator<string, array{string, mixed}>
     */
    public static function propertiesProvider(): \Generator
    {
        yield 'activityId' => ['activityId', '123456789012345678'];
        yield 'discountType' => ['discountType', DiscountType::REDUCTION];
        yield 'discountValue' => ['discountValue', '10.50'];
        yield 'minAmount' => ['minAmount', '100.00'];
        yield 'maxDiscountAmount' => ['maxDiscountAmount', '50.00'];
        yield 'requiredQuantity' => ['requiredQuantity', 5];
        yield 'giftQuantity' => ['giftQuantity', 2];
        yield 'giftProductIds' => ['giftProductIds', ['product1', 'product2']];
        yield 'config' => ['config', ['key' => 'value']];
        yield 'valid' => ['valid', true];
    }
}

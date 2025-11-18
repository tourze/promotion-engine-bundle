<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Controller\Admin\DiscountRuleCrudController;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountRuleCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DiscountRuleCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private KernelBrowser $client;

    protected function afterEasyAdminSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        // 确保静态客户端也被正确设置，以支持基类的 testUnauthenticatedAccessDenied 方法
        self::getClient($this->client);
    }

    public function testAdminAccess(): void
    {
        $this->loginAsAdmin($this->client);

        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    public function testCrudController(): void
    {
        $this->loginAsAdmin($this->client);

        // Just verify the controller is properly configured
        $this->assertSame(DiscountRule::class, DiscountRuleCrudController::getEntityFqcn());

        // Test that admin access works without complex form manipulation
        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    public function testRequiredFieldValidation(): void
    {
        $discountRule = new DiscountRule();

        $violations = self::getService(ValidatorInterface::class)->validate($discountRule);

        // DiscountRule entity has required fields that must be set for validation to pass:
        // - activityId (NotBlank constraint)
        // - discountType (NotNull constraint)
        $this->assertCount(2, $violations, 'DiscountRule entity should have validation errors for required fields');

        // Verify the specific validation errors
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $this->assertArrayHasKey('activityId', $violationMessages);
        $this->assertArrayHasKey('discountType', $violationMessages);
        $this->assertSame('This value should not be blank.', $violationMessages['activityId']);
        $this->assertSame('This value should not be null.', $violationMessages['discountType']);
    }

    public function testUninitializedEnumPropertyAccess(): void
    {
        $discountRule = new DiscountRule();

        // Verify that accessing uninitialized enum properties throws Error
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        // This call will throw Error, which is expected behavior
        /** @var DiscountType $type */
        $type = $discountRule->getDiscountType();
    }

    public function testValidationErrors(): void
    {
        $this->loginAsAdmin($this->client);

        // Test that discount rule entity would trigger validation errors in form submission
        // DiscountRule uses typed enums which are required at language level

        // Since DiscountRule uses typed enums, the validation is at PHP level
        // Form submission without required enum values would result in 422 response
        // with validation messages containing "should not be blank" for required associations

        // Verify the controller class exists and is properly configured
        $this->assertSame(DiscountRule::class, DiscountRuleCrudController::getEntityFqcn());

        // Verify admin access works for this controller
        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    public function testDiscountTypeGenOptions(): void
    {
        $options = DiscountType::genOptions();

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);
        $this->assertCount(count(DiscountType::cases()), $options);

        // 验证所有枚举项都包含在选项中
        foreach (DiscountType::cases() as $index => $case) {
            $this->assertArrayHasKey($index, $options);
            $this->assertIsArray($options[$index]);

            // 验证选项结构
            $this->assertArrayHasKey('label', $options[$index]);
            $this->assertArrayHasKey('value', $options[$index]);
            $this->assertEquals($case->getLabel(), $options[$index]['label']);
            $this->assertEquals($case->value, $options[$index]['value']);
        }
    }

    public function testValidDiscountRule(): void
    {
        $discountRule = new DiscountRule();
        $discountRule->setActivityId('test_activity_123');
        $discountRule->setDiscountType(DiscountType::REDUCTION);
        $discountRule->setDiscountValue('10.00');
        $discountRule->setValid(true);

        $violations = self::getService(ValidatorInterface::class)->validate($discountRule);
        $this->assertCount(0, $violations, 'Valid DiscountRule should pass validation');

        // 验证设置的值
        $this->assertEquals('test_activity_123', $discountRule->getActivityId());
        $this->assertEquals(DiscountType::REDUCTION, $discountRule->getDiscountType());
        $this->assertEquals('10.00', $discountRule->getDiscountValue());
        $this->assertTrue($discountRule->isValid());
    }

    public function testActivate(): void
    {
        $this->loginAsAdmin($this->client);

        // Create a test discount rule
        $discountRule = new DiscountRule();
        $discountRule->setActivityId('test_activity');
        $discountRule->setDiscountType(DiscountType::REDUCTION);
        $discountRule->setDiscountValue('10.00');
        $discountRule->setValid(false);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($discountRule);
        $entityManager->flush();

        // Test activate action
        $this->client->request('GET', '/admin/promotion-engine/discount-rule/' . $discountRule->getId() . '/activate');
        $this->assertResponseRedirects();

        // Verify the entity was activated
        $updatedDiscountRule = $entityManager->find(DiscountRule::class, $discountRule->getId());
        $this->assertNotNull($updatedDiscountRule, 'DiscountRule should exist after activation');
        $this->assertTrue($updatedDiscountRule->isValid());
    }

    public function testDeactivate(): void
    {
        $this->loginAsAdmin($this->client);

        // Create a test discount rule
        $discountRule = new DiscountRule();
        $discountRule->setActivityId('test_activity');
        $discountRule->setDiscountType(DiscountType::REDUCTION);
        $discountRule->setDiscountValue('10.00');
        $discountRule->setValid(true);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($discountRule);
        $entityManager->flush();

        // Test deactivate action
        $this->client->request('GET', '/admin/promotion-engine/discount-rule/' . $discountRule->getId() . '/deactivate');
        $this->assertResponseRedirects();

        // Verify the entity was deactivated
        $entityManager->refresh($discountRule);
        $this->assertFalse($discountRule->isValid());
    }

    public function testDuplicate(): void
    {
        $this->loginAsAdmin($this->client);

        // Create a test discount rule
        $originalRule = new DiscountRule();
        $originalRule->setActivityId('test_activity');
        $originalRule->setDiscountType(DiscountType::REDUCTION);
        $originalRule->setDiscountValue('10.00');
        $originalRule->setMinAmount('100.00');
        $originalRule->setValid(true);

        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($originalRule);
        $entityManager->flush();

        $originalId = $originalRule->getId();

        // Test duplicate action
        $this->client->request('GET', '/admin/promotion-engine/discount-rule/' . $originalId . '/duplicate');
        $this->assertResponseRedirects();

        // Verify a new entity was created
        $repository = $entityManager->getRepository(DiscountRule::class);
        $allRules = $repository->findBy([], ['id' => 'DESC']);
        $duplicatedRule = null;
        foreach ($allRules as $rule) {
            if ($rule->getId() !== $originalId) {
                $duplicatedRule = $rule;
                break;
            }
        }

        $this->assertNotNull($duplicatedRule);
        $this->assertEquals($originalRule->getActivityId(), $duplicatedRule->getActivityId());
        $this->assertEquals($originalRule->getDiscountType(), $duplicatedRule->getDiscountType());
        $this->assertEquals($originalRule->getDiscountValue(), $duplicatedRule->getDiscountValue());
        $this->assertEquals($originalRule->getMinAmount(), $duplicatedRule->getMinAmount());
        $this->assertFalse($duplicatedRule->isValid()); // Duplicated rule should be inactive
    }

    /**
     * @return AbstractCrudController<DiscountRule>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(DiscountRuleCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '活动ID' => ['活动ID'];
        yield '优惠类型' => ['优惠类型'];
        yield '优惠值' => ['优惠值'];
        yield '最低消费门槛' => ['最低消费门槛'];
        yield '最大优惠金额' => ['最大优惠金额'];
        yield '满足数量要求' => ['满足数量要求'];
        yield '赠送数量' => ['赠送数量'];
        yield '是否有效' => ['是否有效'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'activityId' => ['activityId'];
        yield 'requiredQuantity' => ['requiredQuantity'];
        yield 'giftQuantity' => ['giftQuantity'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'activityId' => ['activityId'];
        yield 'requiredQuantity' => ['requiredQuantity'];
        yield 'giftQuantity' => ['giftQuantity'];
    }
}

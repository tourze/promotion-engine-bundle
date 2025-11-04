<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Controller\Admin\ParticipationCrudController;
use PromotionEngineBundle\Entity\Participation;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ParticipationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ParticipationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private KernelBrowser $client;

    protected function onAfterSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        // 确保静态客户端也被正确设置，以支持基类的 testUnauthenticatedAccessDenied 方法
        self::getClient($this->client);
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(Participation::class, ParticipationCrudController::getEntityFqcn());
    }

    public function testAdminAccess(): void
    {
        $this->loginAsAdmin($this->client);

        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return ParticipationCrudController
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ParticipationCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID column' => ['ID'];
        yield 'User column' => ['参与用户'];
        yield 'Total price column' => ['订单总价'];
        yield 'Discount price column' => ['优惠扣减'];
        yield 'Create time column' => ['创建时间'];
        yield 'Update time column' => ['更新时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'User field' => ['user'];
        yield 'Total price field' => ['totalPrice'];
        yield 'Discount price field' => ['discountPrice'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'User field' => ['user'];
        yield 'Total price field' => ['totalPrice'];
        yield 'Discount price field' => ['discountPrice'];
    }
}

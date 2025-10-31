<?php

namespace PromotionEngineBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\Participation;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface&MockObject $linkGenerator;

    private ItemInterface&MockObject $item;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->linkGenerator->method('getCurdListPage')
            ->willReturnCallback(function (string $entityClass): string {
                return match ($entityClass) {
                    Campaign::class => '/admin/campaign',
                    Constraint::class => '/admin/constraint',
                    Discount::class => '/admin/discount',
                    ProductRelation::class => '/admin/product-relation',
                    Participation::class => '/admin/participation',
                    default => '/admin/unknown',
                };
            })
        ;
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
        $this->item = $this->createMock(ItemInterface::class);
    }

    public function testInvoke(): void
    {
        // 创建所有需要的mock对象
        $marketingCenterMenu = $this->createMock(ItemInterface::class);
        $statisticsMenu = $this->createMock(ItemInterface::class);

        // 创建所有菜单项的Mock
        $campaignMenu = $this->createMock(ItemInterface::class);
        $timeLimitActivityMenu = $this->createMock(ItemInterface::class);
        $constraintMenu = $this->createMock(ItemInterface::class);
        $discountMenu = $this->createMock(ItemInterface::class);
        $discountRuleMenu = $this->createMock(ItemInterface::class);
        $discountConditionMenu = $this->createMock(ItemInterface::class);
        $discountFreeConditionMenu = $this->createMock(ItemInterface::class);
        $productRelationMenu = $this->createMock(ItemInterface::class);
        $activityProductMenu = $this->createMock(ItemInterface::class);
        $participationMenu = $this->createMock(ItemInterface::class);

        // 配置所有子菜单的链式调用，返回自身以支持链式调用
        $this->configureChainableMock($campaignMenu);
        $this->configureChainableMock($timeLimitActivityMenu);
        $this->configureChainableMock($constraintMenu);
        $this->configureChainableMock($discountMenu);
        $this->configureChainableMock($discountRuleMenu);
        $this->configureChainableMock($discountConditionMenu);
        $this->configureChainableMock($discountFreeConditionMenu);
        $this->configureChainableMock($productRelationMenu);
        $this->configureChainableMock($activityProductMenu);
        $this->configureChainableMock($participationMenu);

        // 主菜单检查和创建营销中心
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('营销中心')
            ->willReturnOnConsecutiveCalls(null, $marketingCenterMenu)
        ;

        $this->item->expects($this->once())
            ->method('addChild')
            ->with('营销中心')
            ->willReturn($marketingCenterMenu)
        ;

        // 营销中心菜单添加子项 - 根据实际AdminMenu实现配置
        $expectedCalls = [
            '促销活动管理',
            '限时活动管理',
            '约束条件管理',
            '优惠设置管理',
            '优惠规则管理',
            '优惠条件管理',
            '赠品条件管理',
            '产品关系管理',
            '活动商品管理',
            '数据统计',
        ];
        $returnValues = [
            $campaignMenu,
            $timeLimitActivityMenu,
            $constraintMenu,
            $discountMenu,
            $discountRuleMenu,
            $discountConditionMenu,
            $discountFreeConditionMenu,
            $productRelationMenu,
            $activityProductMenu,
            $statisticsMenu,
        ];

        $callIndex = 0;
        $marketingCenterMenu->expects($this->exactly(10))
            ->method('addChild')
            ->willReturnCallback(function ($menuName) use ($expectedCalls, $returnValues, &$callIndex) {
                $this->assertEquals($expectedCalls[$callIndex], $menuName, "Call #{$callIndex} should be for menu '{$expectedCalls[$callIndex]}'");

                return $returnValues[$callIndex++];
            })
        ;

        // 数据统计子菜单处理
        $marketingCenterMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('数据统计')
            ->willReturnOnConsecutiveCalls(null, $statisticsMenu)
        ;

        $statisticsMenu->expects($this->once())
            ->method('addChild')
            ->with('参与记录')
            ->willReturn($participationMenu)
        ;

        // 执行测试
        ($this->adminMenu)($this->item);
    }

    private function configureChainableMock(ItemInterface&MockObject $mock): void
    {
        $mock->method('setUri')->willReturn($mock);
        $mock->method('setAttribute')->willReturn($mock);
    }
}

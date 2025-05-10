<?php

namespace PromotionEngineBundle\Tests;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\AdminMenu;
use PromotionEngineBundle\Entity\Participation;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

class AdminMenuTest extends TestCase
{
    private LinkGeneratorInterface $linkGenerator;
    private ItemInterface $item;
    private ItemInterface $subMenuItem;
    
    protected function setUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->item = $this->createMock(ItemInterface::class);
        $this->subMenuItem = $this->createMock(ItemInterface::class);
    }
    
    /**
     * 测试构造函数是否正确接收依赖
     */
    public function testConstruct(): void
    {
        $adminMenu = new AdminMenu($this->linkGenerator);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }
    
    /**
     * 测试菜单创建功能
     */
    public function testInvoke(): void
    {
        // 设置模拟对象的行为
        $this->linkGenerator->expects($this->once())
            ->method('getCurdListPage')
            ->with(Participation::class)
            ->willReturn('/admin/participation');
            
        // 模拟菜单项的行为
        $this->item->expects($this->once())
            ->method('addChild')
            ->with('营销中心')
            ->willReturn($this->subMenuItem);
            
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('营销中心')
            ->willReturn($this->subMenuItem);
            
        // 使用两个独立的 mock 对象来替代 at() 方法
        $campaignMenuMock = $this->createMock(ItemInterface::class);
        $participationMenuMock = $this->createMock(ItemInterface::class);
        
        // 模拟子菜单的 addChild 方法，第一次和第二次返回不同的菜单项
        $this->subMenuItem
            ->method('addChild')
            ->willReturnMap([
                ['促销活动', [], $campaignMenuMock],
                ['参与记录', [], $participationMenuMock],
            ]);
            
        // 模拟 campaignMenuMock 的 setUri 方法
        $campaignMenuMock->expects($this->once())
            ->method('setUri')
            ->with('/diy-list/AdminGetPromotionCampaignListPage')
            ->willReturn($campaignMenuMock);
            
        // 模拟 participationMenuMock 的 setUri 方法
        $participationMenuMock->expects($this->once())
            ->method('setUri')
            ->with('/admin/participation')
            ->willReturn($participationMenuMock);
        
        // 创建待测试对象并执行测试
        $adminMenu = new AdminMenu($this->linkGenerator);
        $adminMenu($this->item);
    }
} 
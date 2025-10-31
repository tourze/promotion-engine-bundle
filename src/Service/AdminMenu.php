<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Service;

use Knp\Menu\ItemInterface;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountCondition;
use PromotionEngineBundle\Entity\DiscountFreeCondition;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Entity\Participation;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * 促销引擎菜单服务
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('营销中心')) {
            $item->addChild('营销中心');
        }

        $marketingMenu = $item->getChild('营销中心');
        if (null === $marketingMenu) {
            return;
        }

        // 核心促销管理
        $marketingMenu->addChild('促销活动管理')
            ->setUri($this->linkGenerator->getCurdListPage(Campaign::class))
            ->setAttribute('icon', 'fas fa-bullhorn')
        ;

        $marketingMenu->addChild('限时活动管理')
            ->setUri($this->linkGenerator->getCurdListPage(TimeLimitActivity::class))
            ->setAttribute('icon', 'fas fa-clock')
        ;

        $marketingMenu->addChild('约束条件管理')
            ->setUri($this->linkGenerator->getCurdListPage(Constraint::class))
            ->setAttribute('icon', 'fas fa-filter')
        ;

        $marketingMenu->addChild('优惠设置管理')
            ->setUri($this->linkGenerator->getCurdListPage(Discount::class))
            ->setAttribute('icon', 'fas fa-percent')
        ;

        $marketingMenu->addChild('优惠规则管理')
            ->setUri($this->linkGenerator->getCurdListPage(DiscountRule::class))
            ->setAttribute('icon', 'fas fa-cogs')
        ;

        $marketingMenu->addChild('优惠条件管理')
            ->setUri($this->linkGenerator->getCurdListPage(DiscountCondition::class))
            ->setAttribute('icon', 'fas fa-list-ul')
        ;

        $marketingMenu->addChild('赠品条件管理')
            ->setUri($this->linkGenerator->getCurdListPage(DiscountFreeCondition::class))
            ->setAttribute('icon', 'fas fa-gift')
        ;

        // 产品关系管理
        $marketingMenu->addChild('产品关系管理')
            ->setUri($this->linkGenerator->getCurdListPage(ProductRelation::class))
            ->setAttribute('icon', 'fas fa-link')
        ;

        $marketingMenu->addChild('活动商品管理')
            ->setUri($this->linkGenerator->getCurdListPage(ActivityProduct::class))
            ->setAttribute('icon', 'fas fa-shopping-cart')
        ;

        // 数据统计分析
        if (null === $marketingMenu->getChild('数据统计')) {
            $marketingMenu->addChild('数据统计');
        }

        $statisticsMenu = $marketingMenu->getChild('数据统计');
        if (null !== $statisticsMenu) {
            $statisticsMenu->addChild('参与记录')
                ->setUri($this->linkGenerator->getCurdListPage(Participation::class))
                ->setAttribute('icon', 'fas fa-chart-line')
            ;
        }
    }
}

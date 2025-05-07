<?php

namespace PromotionEngineBundle;

use Knp\Menu\ItemInterface;
use PromotionEngineBundle\Entity\Participation;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

class AdminMenu implements MenuProviderInterface
{
    public function __construct(private readonly LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        $item->addChild('营销中心');

        $item->getChild('营销中心')->addChild('促销活动')->setUri('/diy-list/AdminGetPromotionCampaignListPage');
        $item->getChild('营销中心')->addChild('参与记录')->setUri($this->linkGenerator->getCurdListPage(Participation::class));
    }
}

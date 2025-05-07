<?php

namespace PromotionEngineBundle\Procedure\Admin;

use Doctrine\Common\Collections\Criteria;
use ProductBundle\Entity\Spu;
use ProductBundle\Repository\SkuRepository;
use ProductBundle\Repository\SpuRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('获取商品列表')]
#[MethodExpose('AdminGetPromotionProducts')]
class AdminGetPromotionProducts extends BaseProcedure
{
    use PaginatorTrait;

    public string $keyword = '';

    public array $spuIds = [];

    public function __construct(
        private readonly SpuRepository $spuRepository,
        private readonly SkuRepository $skuRepository,
    ) {
    }

    public function execute(): array
    {
        $qb = $this->spuRepository->createQueryBuilder('a')
            ->andWhere('a.valid = true AND a.audited = true')
            ->addOrderBy('a.sortNumber', Criteria::DESC)
            ->addOrderBy('a.id', Criteria::DESC);

        if ($this->keyword) {
            $qb->andWhere('a.title like :keyword')->setParameter('keyword', $this->keyword);
        }
        if ($this->spuIds) {
            $qb->andWhere('a.id in (:spuIds)')->setParameter('spuIds', $this->spuIds);
        }
        $result = $this->fetchList($qb, $this->formatItem(...));
        $result['items'] = $result['list'];

        return $result;
    }

    private function formatItem(Spu $spu): array
    {
        $result = [
            'id' => $spu->getId(),
            'title' => $spu->getTitle(),
            'goods_id' => $spu->getId(),
            'skuId' => $spu->getId(),
            'thumb' => $spu->getMainThumb(),
            'goodsInfo' => [
                'title' => $spu->getTitle(),
                'thumb' => $spu->getMainThumb(),
            ],
            'info' => [
                'title' => $spu->getTitle(),
                'thumb' => $spu->getMainThumb(),
            ],
        ];
        $stock = 0;
        foreach ($spu->getSkus() as $sku) {
            $stock += $sku->getValidStock();
        }
        $prices = $spu->getSalePrices();
        $marketPrice = 0;
        if ($prices) {
            $marketPrice = $prices[0]['minSalePrice'];
        }
        $result['stock'] = $stock;
        $result['total'] = $stock;
        $result['market_price'] = $marketPrice;

        return $result;
    }
}

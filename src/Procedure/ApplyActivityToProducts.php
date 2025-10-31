<?php

namespace PromotionEngineBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\DTO\ApplyActivityProductInput;
use PromotionEngineBundle\DTO\ApplyActivityToProductsInput;
use PromotionEngineBundle\DTO\ApplyActivityToProductsResult;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[MethodExpose(method: 'ApplyActivityToProducts')]
#[MethodTag(name: '限时活动模块')]
#[MethodDoc(summary: '批量添加商品到活动')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Autoconfigure(public: true)]
#[Log]
class ApplyActivityToProducts extends LockableProcedure
{
    #[MethodParam(description: '活动ID')]
    public string $activityId;

    /**
     * @var array<array<string, mixed>>
     */
    #[MethodParam(description: '商品信息列表')]
    public array $products;

    public function __construct(
        private readonly TimeLimitActivityRepository $activityRepository,
        private readonly ActivityProductRepository $activityProductRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        try {
            $input = $this->buildInput();
            $this->validateInput($input);

            $this->entityManager->beginTransaction();

            $result = $this->applyProducts($input);

            $this->entityManager->commit();

            return $result->toArray();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            return ApplyActivityToProductsResult::failure($e->getMessage())->toArray();
        }
    }

    private function buildInput(): ApplyActivityToProductsInput
    {
        $products = [];
        foreach ($this->products as $productData) {
            $productId = $productData['productId'] ?? '';
            $activityPrice = $productData['activityPrice'] ?? '0.00';
            $limitPerUser = $productData['limitPerUser'] ?? 1;
            $activityStock = $productData['activityStock'] ?? 0;

            $products[] = new ApplyActivityProductInput(
                productId: is_string($productId) ? $productId : (string) $productId,
                activityPrice: is_string($activityPrice) ? $activityPrice : (string) $activityPrice,
                limitPerUser: is_int($limitPerUser) ? $limitPerUser : (int) $limitPerUser,
                activityStock: is_int($activityStock) ? $activityStock : (int) $activityStock,
            );
        }

        return new ApplyActivityToProductsInput(
            activityId: $this->activityId,
            products: $products,
        );
    }

    private function validateInput(ApplyActivityToProductsInput $input): void
    {
        if ('' === trim($input->activityId)) {
            throw ActivityException::invalidProductIds('活动ID不能为空');
        }

        if (!$input->hasProducts()) {
            throw ActivityException::invalidProductIds('商品列表不能为空');
        }

        $activity = $this->activityRepository->find($input->activityId);
        if (null === $activity || true !== $activity->isValid()) {
            throw ActivityException::invalidProductIds('活动不存在或已失效');
        }

        foreach ($input->products as $product) {
            if (!$product->isValid()) {
                throw ActivityException::invalidProductIds("商品 {$product->productId} 参数无效");
            }
        }

        $productIds = $input->getProductIds();
        if (count($productIds) !== count(array_unique($productIds))) {
            throw ActivityException::invalidProductIds('商品ID列表中存在重复');
        }
    }

    private function applyProducts(ApplyActivityToProductsInput $input): ApplyActivityToProductsResult
    {
        $activity = $this->activityRepository->find($input->activityId);
        if (null === $activity) {
            throw ActivityException::invalidProductIds('活动不存在');
        }

        $addedProductIds = [];
        $failedProductIds = [];

        foreach ($input->products as $productInput) {
            try {
                $this->applyProductToActivity($activity, $productInput);
                $addedProductIds[] = $productInput->productId;
            } catch (\Exception $e) {
                $failedProductIds[] = $productInput->productId;
            }
        }

        $totalCount = count($input->products);
        $addedCount = count($addedProductIds);

        if ($addedCount === $totalCount) {
            return ApplyActivityToProductsResult::success($addedCount, $totalCount);
        }

        if ($addedCount > 0) {
            return ApplyActivityToProductsResult::partial($addedProductIds, $failedProductIds);
        }

        return ApplyActivityToProductsResult::failure('所有商品添加失败');
    }

    private function applyProductToActivity(TimeLimitActivity $activity, ApplyActivityProductInput $productInput): void
    {
        $existingActivityProduct = $this->activityProductRepository->findByActivityAndProduct(
            $activity->getId() ?? '',
            $productInput->productId
        );

        if (null !== $existingActivityProduct) {
            $this->updateActivityProduct($existingActivityProduct, $productInput);
        } else {
            $this->createActivityProduct($activity, $productInput);
        }
    }

    private function createActivityProduct(TimeLimitActivity $activity, ApplyActivityProductInput $productInput): void
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId($productInput->productId);
        $activityProduct->setActivityPrice($productInput->activityPrice);
        $activityProduct->setLimitPerUser($productInput->limitPerUser);
        $activityProduct->setActivityStock($productInput->activityStock);
        $activityProduct->setValid(true);

        $this->activityProductRepository->save($activityProduct, false);

        $activity->addProductId($productInput->productId);
        $this->activityRepository->save($activity, false);
    }

    private function updateActivityProduct(ActivityProduct $activityProduct, ApplyActivityProductInput $productInput): void
    {
        $activityProduct->setActivityPrice($productInput->activityPrice);
        $activityProduct->setLimitPerUser($productInput->limitPerUser);
        $activityProduct->setActivityStock($productInput->activityStock);
        $activityProduct->setValid(true);

        $this->activityProductRepository->save($activityProduct, false);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getMockResult(): ?array
    {
        return ApplyActivityToProductsResult::success(3, 3)->toArray();
    }
}

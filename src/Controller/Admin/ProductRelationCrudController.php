<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\ProductRelation;

/**
 * @extends AbstractCrudController<ProductRelation>
 */
#[AdminCrud(routePath: '/promotion-engine/product-relation', routeName: 'promotion_engine_product_relation')]
final class ProductRelationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductRelation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('产品关系')
            ->setEntityLabelInPlural('产品关系')
            ->setPageTitle('index', '优惠产品关系列表')
            ->setPageTitle('detail', '优惠产品关系详情')
            ->setPageTitle('new', '新建产品关系')
            ->setPageTitle('edit', '编辑产品关系')
            ->setHelp('index', '管理促销优惠与商品的关联关系，包括SPU、SKU、赠品数量等信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'spuId', 'skuId'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('discount', '关联优惠')
            ->setRequired(true)
            ->formatValue(function ($value) {
                return $value ? sprintf('%s (#%s)', $value->getType()->getLabel(), $value->getId()) : '';
            })
        ;

        yield TextField::new('spuId', 'SPU ID')
            ->setRequired(true)
            ->setMaxLength(20)
            ->setColumns(6)
            ->setHelp('商品标准化单元ID，必须为数字')
        ;

        yield TextField::new('skuId', 'SKU ID')
            ->setMaxLength(20)
            ->setColumns(6)
            ->setHelp('商品库存单元ID，必须为数字，可为空')
        ;

        yield IntegerField::new('total', '总库存')
            ->setColumns(6)
            ->setHelp('商品总库存数量，默认为0表示不限制')
        ;

        yield IntegerField::new('giftQuantity', '赠送数量')
            ->setColumns(6)
            ->setHelp('赠品活动中的赠送数量，默认为1')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('discount', '关联优惠'))
            ->add(TextFilter::new('spuId', 'SPU ID'))
            ->add(TextFilter::new('skuId', 'SKU ID'))
            ->add(NumericFilter::new('total', '总库存'))
            ->add(NumericFilter::new('giftQuantity', '赠送数量'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}

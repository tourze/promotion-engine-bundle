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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use PromotionEngineBundle\Entity\DiscountFreeCondition;

/**
 * @extends AbstractCrudController<DiscountFreeCondition>
 */
#[AdminCrud(routePath: '/promotion-engine/discount-free-condition', routeName: 'promotion_engine_discount_free_condition')]
final class DiscountFreeConditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DiscountFreeCondition::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('赠品条件')
            ->setEntityLabelInPlural('赠品条件')
            ->setPageTitle('index', '赠品条件列表')
            ->setPageTitle('detail', '赠品条件详情')
            ->setPageTitle('new', '新建赠品条件')
            ->setPageTitle('edit', '编辑赠品条件')
            ->setHelp('index', '管理赠品条件，设置购买数量和免费数量的优惠规则')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'purchaseQuantity', 'freeQuantity'])
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
            ->setColumns(6)
            ->formatValue(function ($value) {
                return $value ? sprintf('优惠ID: %s', $value->getId()) : '无关联优惠';
            })
        ;

        yield TextField::new('purchaseQuantity', '购买数量')
            ->setRequired(true)
            ->setMaxLength(10)
            ->setColumns(6)
            ->setHelp('用户需要购买的商品数量')
        ;

        yield TextField::new('freeQuantity', '免费数量')
            ->setRequired(true)
            ->setMaxLength(10)
            ->setColumns(6)
            ->setHelp('用户可以免费获得的商品数量')
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
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('discount', '关联优惠'))
            ->add(NumericFilter::new('purchaseQuantity', '购买数量'))
            ->add(NumericFilter::new('freeQuantity', '免费数量'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}

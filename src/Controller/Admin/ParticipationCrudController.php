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
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use PromotionEngineBundle\Entity\Participation;

/**
 * @extends AbstractCrudController<Participation>
 */
#[AdminCrud(routePath: '/promotion-engine/participation', routeName: 'promotion_engine_participation')]
final class ParticipationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Participation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('参与记录')
            ->setEntityLabelInPlural('参与记录')
            ->setPageTitle('index', '促销参与记录列表')
            ->setPageTitle('detail', '促销参与记录详情')
            ->setPageTitle('new', '新建参与记录')
            ->setPageTitle('edit', '编辑参与记录')
            ->setHelp('index', '查看用户参与促销活动的记录，包括订单金额和优惠信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'totalPrice', 'discountPrice'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('user', '参与用户')
            ->formatValue(function ($value) {
                return $value ? sprintf('%s (%s)', $value->getUsername(), $value->getId()) : '匿名用户';
            })
        ;

        yield CollectionField::new('campaigns', '参与的活动')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if (!$value || $value->isEmpty()) {
                    return '无活动';
                }
                $titles = [];
                foreach ($value as $campaign) {
                    $titles[] = $campaign->getTitle();
                }

                return implode(', ', $titles);
            })
        ;

        yield MoneyField::new('totalPrice', '订单总价')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setColumns(6)
        ;

        yield MoneyField::new('discountPrice', '优惠扣减')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setColumns(6)
        ;

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm()
            ->onlyOnDetail()
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
            ->add(EntityFilter::new('user', '参与用户'))
            ->add(NumericFilter::new('totalPrice', '订单总价'))
            ->add(NumericFilter::new('discountPrice', '优惠扣减'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}

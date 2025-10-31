# 促销引擎包

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/tourze/promotion-engine-bundle)
[![Coverage](https://img.shields.io/badge/coverage-80%25-yellow)](https://github.com/tourze/promotion-engine-bundle)

[English](README.md) | [中文](README.zh-CN.md)

一个用于管理电商应用中促销、活动和折扣的综合性 Symfony 包。

## 安装

```bash
composer require tourze/promotion-engine-bundle
```

## 快速开始

### 1. 注册包

将包添加到您的 `config/bundles.php` 文件中：

```php
return [
    // ...
    PromotionEngineBundle\PromotionEngineBundle::class => ['all' => true],
];
```

### 2. 基本用法

```php
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\Participation;

// 创建促销活动
$campaign = new Campaign();
$campaign->setTitle('夏季促销');
$campaign->setDescription('全场商品8折优惠');
$campaign->setStartTime(new \DateTime('2024-06-01'));
$campaign->setEndTime(new \DateTime('2024-08-31'));

// 创建折扣
$discount = new Discount();
$discount->setType(DiscountType::PERCENTAGE);
$discount->setValue('20.00');
$discount->setCampaign($campaign);

// 跟踪参与记录
$participation = new Participation();
$participation->setUserId('123');
$participation->addCampaign($campaign);
```

### 3. 后台菜单集成

该包自动提供以下后台菜单项：
- 营销中心
- 促销活动
- 参与记录

## 配置说明

### 数据库迁移

为促销引擎表生成并运行数据库迁移：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 包配置

在 `config/packages/promotion_engine.yaml` 中配置包：

```yaml
promotion_engine:
    admin_menu:
        enabled: true
        parent_menu: 'marketing'
    validation:
        enabled: true
        strict_mode: false
    tracking:
        enabled: true
        log_participation: true
```

## 功能特性

- **活动管理**：创建和管理基于时间的促销活动
- **折扣引擎**：支持百分比、固定金额和免费商品折扣
- **参与跟踪**：追踪用户参与情况和活动分析
- **商品关联**：支持商品（SPU/SKU）特定促销
- **约束系统**：定义复杂的促销规则和条件
- **后台界面**：EasyAdmin 集成的活动管理界面
- **多语言支持**：内置中英文界面支持

## 核心实体

- `Campaign`：促销活动管理
- `Discount`：折扣规则和计算
- `Participation`：用户参与跟踪
- `ProductRelation`：商品特定促销
- `Constraint`：促销约束和规则
- `DiscountCondition`：条件折扣逻辑
- `DiscountFreeCondition`：免费商品条件

## 高级用法

### 自定义折扣类型

通过扩展 DiscountType 枚举创建自定义折扣类型：

```php
use PromotionEngineBundle\Enum\DiscountType;

// 自定义折扣计算
$customDiscount = new class extends DiscountType {
    case TIERED = 'tiered';
    case BUY_ONE_GET_ONE = 'bogo';
};
```

### 仓库使用

使用提供的仓库访问促销数据：

```php
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountRepository;

// 在服务中注入仓库
public function __construct(
    private CampaignRepository $campaignRepository,
    private DiscountRepository $discountRepository
) {}

// 查找活跃的活动
$activeCampaigns = $this->campaignRepository->findActiveCampaigns();

// 根据活动查找折扣
$discounts = $this->discountRepository->findByCampaign($campaign);
```

### 约束验证

为复杂的促销规则创建自定义约束：

```php
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;

// 示例：最低订单金额约束
$constraint = new Constraint();
$constraint->setCompareType(CompareType::GREATER_THAN);
$constraint->setLimitType(LimitType::ORDER_VALUE);
$constraint->setRangeValue('100.00');
```

## 系统要求

- PHP 8.1 或更高版本
- Symfony 7.3 或更高版本
- Doctrine ORM 3.0 或更高版本
- Doctrine Bundle 2.13 或更高版本
- MySQL 8.0 或更高版本（推荐以获得最佳性能）

## 许可证

MIT 许可证。详情请参阅 [LICENSE](LICENSE) 文件。
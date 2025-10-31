# Promotion Engine Bundle

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/tourze/promotion-engine-bundle)
[![Coverage](https://img.shields.io/badge/coverage-80%25-yellow)](https://github.com/tourze/promotion-engine-bundle)

[English](README.md) | [中文](README.zh-CN.md)

A comprehensive Symfony bundle for managing promotions, campaigns, and discounts in e-commerce applications.

## Installation

```bash
composer require tourze/promotion-engine-bundle
```

## Quick Start

### 1. Register the Bundle

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    PromotionEngineBundle\PromotionEngineBundle::class => ['all' => true],
];
```

### 2. Basic Usage

```php
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\Participation;

// Create a promotion campaign
$campaign = new Campaign();
$campaign->setTitle('Summer Sale');
$campaign->setDescription('Get 20% off on all items');
$campaign->setStartTime(new \DateTime('2024-06-01'));
$campaign->setEndTime(new \DateTime('2024-08-31'));

// Create a discount
$discount = new Discount();
$discount->setType(DiscountType::PERCENTAGE);
$discount->setValue('20.00');
$discount->setCampaign($campaign);

// Track participation
$participation = new Participation();
$participation->setUserId('123');
$participation->addCampaign($campaign);
```

### 3. Admin Menu Integration

The bundle automatically provides admin menu items for:
- 营销中心 (Marketing Center)
- 促销活动 (Promotion Campaigns)
- 参与记录 (Participation Records)

## Configuration

### Database Migration

Generate and run database migrations for the promotion engine tables:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Bundle Configuration

Configure the bundle in your `config/packages/promotion_engine.yaml`:

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

## Features

- **Campaign Management**: Create and manage promotion campaigns with time-based scheduling
- **Discount Engine**: Support for percentage, fixed amount, and free product discounts
- **Participation Tracking**: Track user participation and campaign analytics
- **Product Relations**: Link specific products (SPU/SKU) to promotions
- **Constraint System**: Define complex rules and conditions for promotions
- **Admin Interface**: EasyAdmin integration for campaign management
- **Multi-language Support**: Built-in support for Chinese and English interfaces

## Commands

### promotion:update-activity-status

Automatically update the status of time-limited activities based on their start and end times.

```bash
php bin/console promotion:update-activity-status
```

This command:
- Updates activity status from `PENDING` to `ACTIVE` when start time is reached
- Updates activity status from `ACTIVE` to `EXPIRED` when end time is passed
- Runs automatically every 5 minutes via cron job
- Logs all status changes for audit purposes

**Usage Options:**
- Manual execution: `php bin/console promotion:update-activity-status`
- Automatic execution: Configured with `@AsCronTask` to run every 5 minutes
- Verbose output: Use `-v` flag for detailed status change information

## Core Entities

- `Campaign`: Promotion campaign management
- `Discount`: Discount rules and calculations
- `Participation`: User participation tracking
- `ProductRelation`: Product-specific promotions
- `Constraint`: Promotion constraints and rules
- `DiscountCondition`: Conditional discount logic
- `DiscountFreeCondition`: Free product conditions

## Advanced Usage

### Custom Discount Types

Create custom discount types by extending the DiscountType enum:

```php
use PromotionEngineBundle\Enum\DiscountType;

// Custom discount calculation
$customDiscount = new class extends DiscountType {
    case TIERED = 'tiered';
    case BUY_ONE_GET_ONE = 'bogo';
};
```

### Repository Usage

Access promotion data using the provided repositories:

```php
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountRepository;

// Inject repositories in your services
public function __construct(
    private CampaignRepository $campaignRepository,
    private DiscountRepository $discountRepository
) {}

// Find active campaigns
$activeCampaigns = $this->campaignRepository->findActiveCampaigns();

// Find discounts by campaign
$discounts = $this->discountRepository->findByCampaign($campaign);
```

### Constraint Validation

Create custom constraints for complex promotion rules:

```php
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;

// Example: Minimum order value constraint
$constraint = new Constraint();
$constraint->setCompareType(CompareType::GREATER_THAN);
$constraint->setLimitType(LimitType::ORDER_VALUE);
$constraint->setRangeValue('100.00');
```

## Requirements

- PHP 8.1 or higher
- Symfony 7.3 or higher
- Doctrine ORM 3.0 or higher
- Doctrine Bundle 2.13 or higher
- MySQL 8.0 or higher (for optimized performance)

## License

MIT License. See [LICENSE](LICENSE) for details.
<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * @internal
 */
#[CoversClass(TestCase::class)]
abstract class TestCase extends BaseTestCase
{
}

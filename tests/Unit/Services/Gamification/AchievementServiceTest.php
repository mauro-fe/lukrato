<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Gamification;

use Application\Services\Gamification\AchievementService;
use Closure;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AchievementServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCountCustomCategoriesCountsOnlyManualRootCategories(): void
    {
        $categoriaModel = Mockery::mock('alias:Application\Models\Categoria');
        $query = Mockery::mock();
        $nestedQuery = Mockery::mock();

        $categoriaModel
            ->shouldReceive('where')
            ->once()
            ->with('user_id', 55)
            ->andReturn($query);

        $query
            ->shouldReceive('whereNull')
            ->once()
            ->with('parent_id')
            ->andReturnSelf();

        $query
            ->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) use ($nestedQuery): bool {
                if (!$callback instanceof Closure) {
                    return false;
                }

                $nestedQuery
                    ->shouldReceive('where')
                    ->once()
                    ->with('is_seeded', false)
                    ->andReturnSelf();

                $nestedQuery
                    ->shouldReceive('orWhereNull')
                    ->once()
                    ->with('is_seeded')
                    ->andReturnSelf();

                $callback($nestedQuery);

                return true;
            }))
            ->andReturnSelf();

        $query
            ->shouldReceive('count')
            ->once()
            ->andReturn(4);

        $service = new AchievementService();
        $method = new \ReflectionMethod($service, 'countCustomCategories');
        $method->setAccessible(true);

        $this->assertSame(4, $method->invoke($service, 55));
    }
}

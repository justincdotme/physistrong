<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ExerciseProgressService;
use PHPUnit\Framework\TestCase;

class ExerciseProgressServiceTest extends TestCase
{
    public function test_metric_config_throws_for_unknown_metric(): void
    {
        $service = new class () extends ExerciseProgressService {
            /** @return array{table: string, column: string, cast: string} */
            public function exposedMetricConfig(string $metric): array
            {
                return $this->metricConfig($metric);
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $service->exposedMetricConfig('nonexistent');
    }
}

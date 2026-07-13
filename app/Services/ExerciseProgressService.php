<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExerciseProgressService
{
    /** @return array<string, mixed> */
    public function getProgressData(Exercise $exercise, User $user, string $range): array
    {
        $primaryMetric = $this->resolvePrimaryMetric($exercise);
        $startDate = $this->resolveStartDate($range);

        $allEntries = $this->queryDataPoints($exercise, $user, $primaryMetric);
        $prEntryIds = $this->detectPRs($allEntries);

        $rangeEntries = $startDate
            ? $allEntries->filter(fn ($row) => $row->date >= $startDate->toDateString())
            : $allEntries;

        $dataPoints = $rangeEntries->map(fn ($row) => [
            'entry_id' => (int) $row->entry_id,
            'date' => $row->date,
            'value' => $this->castMetricValue($row->value, $primaryMetric),
            'is_pr' => in_array((int) $row->entry_id, $prEntryIds, true),
        ])->values()->all();

        $result = [
            'exercise_id' => $exercise->id,
            'exercise_type' => $exercise->type->value,
            'range' => $range,
            'primary_metric' => $primaryMetric,
            'data_points' => $dataPoints,
        ];

        if ($exercise->type === ExerciseType::Resistance) {
            $result['volume'] = $this->queryVolumeData($exercise, $user, $startDate);
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function getPersonalRecords(Exercise $exercise, User $user): array
    {
        $records = [];

        if ($exercise->type === ExerciseType::Resistance) {
            $primaryMetric = $this->resolvePrimaryMetric($exercise);

            if ($primaryMetric === 'weight') {
                $best = $this->findBest($exercise, $user, 'log_load_metrics', 'actual_weight');
                if ($best) {
                    $records['weight'] = $best;
                }
            }

            $best = $this->findBest($exercise, $user, 'log_rep_metrics', 'actual_reps');
            if ($best) {
                $records['reps'] = $best;
            }

            $best = $this->findBestSetVolume($exercise, $user);
            if ($best) {
                $records['volume'] = $best;
            }
        }

        if ($exercise->type === ExerciseType::TimedHold) {
            $best = $this->findBest($exercise, $user, 'log_duration_metrics', 'actual_duration_seconds');
            if ($best) {
                $records['duration'] = $best;
            }
        }

        if ($exercise->type === ExerciseType::Distance) {
            $best = $this->findBest($exercise, $user, 'log_distance_metrics', 'actual_distance');
            if ($best) {
                $records['distance'] = $best;
            }
        }

        if ($exercise->type === ExerciseType::Interval) {
            $best = $this->findBest($exercise, $user, 'log_interval_headers', 'completed_rounds');
            if ($best) {
                $records['completed_rounds'] = $best;
            }
        }

        return [
            'exercise_id' => $exercise->id,
            'exercise_type' => $exercise->type->value,
            'records' => $records,
        ];
    }

    public function resolvePrimaryMetric(Exercise $exercise): string
    {
        if ($exercise->type === ExerciseType::Resistance) {
            $resistance = $exercise->resistance;
            if ($resistance && $resistance->bodyweight_base && ! $resistance->allows_added_weight) {
                return 'reps';
            }

            return 'weight';
        }

        return match ($exercise->type) {
            ExerciseType::TimedHold => 'duration',
            ExerciseType::Distance => 'distance',
            ExerciseType::Interval => 'completed_rounds',
        };
    }

    private function resolveStartDate(string $range): ?Carbon
    {
        return match ($range) {
            '1m' => Carbon::now()->subMonth(),
            '3m' => Carbon::now()->subMonths(3),
            '6m' => Carbon::now()->subMonths(6),
            '1y' => Carbon::now()->subYear(),
            default => null,
        };
    }

    private function queryDataPoints(
        Exercise $exercise,
        User $user,
        string $primaryMetric,
    ): Collection {
        $config = $this->metricConfig($primaryMetric);

        return DB::table('workout_entries')
            ->join('workouts', 'workouts.id', '=', 'workout_entries.workout_id')
            ->join($config['table'], "{$config['table']}.entry_id", '=', 'workout_entries.id')
            ->where('workout_entries.exercise_id', $exercise->id)
            ->where('workouts.user_id', $user->id)
            ->whereNotNull("{$config['table']}.{$config['column']}")
            ->select([
                'workout_entries.id as entry_id',
                'workouts.date',
                "{$config['table']}.{$config['column']} as value",
            ])
            ->orderBy('workouts.date')
            ->orderBy('workout_entries.set_order')
            ->get()->map(fn ($row) => (object) [
                'entry_id' => $row->entry_id,
                'date' => Carbon::parse($row->date)->toDateString(),
                'value' => $row->value,
            ]);
    }

    /** @return list<int> */
    private function detectPRs(Collection $entries): array
    {
        $prEntryIds = [];
        $runningMax = null;

        foreach ($entries as $entry) {
            $value = (float) $entry->value;
            if ($runningMax === null || $value > $runningMax) {
                $runningMax = $value;
                $prEntryIds[] = (int) $entry->entry_id;
            }
        }

        return $prEntryIds;
    }

    /** @return list<array{workout_id: int, date: string, total_volume: float}> */
    private function queryVolumeData(Exercise $exercise, User $user, ?Carbon $startDate): array
    {
        $query = DB::table('workout_entries')
            ->join('workouts', 'workouts.id', '=', 'workout_entries.workout_id')
            ->join('log_load_metrics', 'log_load_metrics.entry_id', '=', 'workout_entries.id')
            ->join('log_rep_metrics', 'log_rep_metrics.entry_id', '=', 'workout_entries.id')
            ->where('workout_entries.exercise_id', $exercise->id)
            ->where('workouts.user_id', $user->id)
            ->whereNotNull('log_load_metrics.actual_weight')
            ->whereNotNull('log_rep_metrics.actual_reps')
            ->selectRaw('workouts.id as workout_id, workouts.date, SUM(log_load_metrics.actual_weight * log_rep_metrics.actual_reps) as total_volume')
            ->groupBy('workouts.id', 'workouts.date')
            ->orderBy('workouts.date');

        if ($startDate) {
            $query->where('workouts.date', '>=', $startDate->toDateString());
        }

        return $query->get()->map(fn ($row) => [
            'workout_id' => (int) $row->workout_id,
            'date' => Carbon::parse($row->date)->toDateString(),
            'total_volume' => round((float) $row->total_volume, 2),
        ])->all();
    }

    /** @return array{value: mixed, entry_id: int, date: string}|null */
    private function findBest(Exercise $exercise, User $user, string $table, string $column): ?array
    {
        $result = DB::table('workout_entries')
            ->join('workouts', 'workouts.id', '=', 'workout_entries.workout_id')
            ->join($table, "{$table}.entry_id", '=', 'workout_entries.id')
            ->where('workout_entries.exercise_id', $exercise->id)
            ->where('workouts.user_id', $user->id)
            ->whereNotNull("{$table}.{$column}")
            ->select([
                'workout_entries.id as entry_id',
                'workouts.date',
                "{$table}.{$column} as value",
            ])
            ->orderByDesc("{$table}.{$column}")
            ->orderBy('workouts.date')
            ->first();

        if (! $result) {
            return null;
        }

        return [
            'value' => $this->castRecordValue($result->value, $column),
            'entry_id' => (int) $result->entry_id,
            'date' => Carbon::parse($result->date)->toDateString(),
        ];
    }

    /** @return array{value: float, entry_id: int, date: string}|null */
    private function findBestSetVolume(Exercise $exercise, User $user): ?array
    {
        $result = DB::table('workout_entries')
            ->join('workouts', 'workouts.id', '=', 'workout_entries.workout_id')
            ->join('log_load_metrics', 'log_load_metrics.entry_id', '=', 'workout_entries.id')
            ->join('log_rep_metrics', 'log_rep_metrics.entry_id', '=', 'workout_entries.id')
            ->where('workout_entries.exercise_id', $exercise->id)
            ->where('workouts.user_id', $user->id)
            ->whereNotNull('log_load_metrics.actual_weight')
            ->whereNotNull('log_rep_metrics.actual_reps')
            ->selectRaw('workout_entries.id as entry_id, workouts.date, (log_load_metrics.actual_weight * log_rep_metrics.actual_reps) as volume')
            ->orderByDesc('volume')
            ->orderBy('workouts.date')
            ->first();

        if (! $result) {
            return null;
        }

        return [
            'value' => round((float) $result->volume, 2),
            'entry_id' => (int) $result->entry_id,
            'date' => Carbon::parse($result->date)->toDateString(),
        ];
    }

    /** @return array{table: string, column: string} */
    private function metricConfig(string $primaryMetric): array
    {
        return match ($primaryMetric) {
            'weight' => ['table' => 'log_load_metrics', 'column' => 'actual_weight'],
            'reps' => ['table' => 'log_rep_metrics', 'column' => 'actual_reps'],
            'duration' => ['table' => 'log_duration_metrics', 'column' => 'actual_duration_seconds'],
            'distance' => ['table' => 'log_distance_metrics', 'column' => 'actual_distance'],
            default => ['table' => 'log_interval_headers', 'column' => 'completed_rounds'],
        };
    }

    private function castMetricValue(mixed $value, string $primaryMetric): float|int
    {
        return match ($primaryMetric) {
            'weight', 'distance' => (float) $value,
            default => (int) $value,
        };
    }

    private function castRecordValue(mixed $value, string $column): float|int
    {
        return match ($column) {
            'actual_weight', 'actual_distance' => (float) $value,
            default => (int) $value,
        };
    }
}

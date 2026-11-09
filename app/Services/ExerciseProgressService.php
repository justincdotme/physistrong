<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExerciseType;
use App\Enums\ProgressMetric;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class ExerciseProgressService
{
    /**
     * @param Exercise $exercise
     * @param User     $user
     * @param string   $range
     *
     * @return array<string, mixed>
     */
    public function getProgressData(Exercise $exercise, User $user, string $range): array
    {
        $startDate = $this->resolveStartDate($range);

        $result = [
            'exercise_id'    => $exercise->id,
            'exercise_type'  => $exercise->type->value,
            'range'          => $range,
            'primary_metric' => ProgressMetric::primaryFor($exercise)->value,
            'metrics'        => array_map(
                fn (ProgressMetric $metric): array => $this->buildSeries($exercise, $user, $metric, $startDate),
                ProgressMetric::forExercise($exercise),
            ),
        ];

        if ($exercise->type === ExerciseType::Resistance) {
            $result['volume'] = $this->queryVolumeData($exercise, $user, $startDate);
        }

        return $result;
    }

    /**
     * @param Exercise $exercise
     * @param User     $user
     *
     * @return array<string, mixed>
     */
    public function getPersonalRecords(Exercise $exercise, User $user): array
    {
        $records = [];

        foreach (ProgressMetric::forExercise($exercise) as $metric) {
            $best = $this->findBest($exercise, $user, $metric);

            if ($best) {
                $records[$metric->value] = $best;
            }
        }

        if ($exercise->type === ExerciseType::Resistance) {
            $best = $this->findBestSetVolume($exercise, $user);

            if ($best) {
                $records['volume'] = $best;
            }
        }

        return [
            'exercise_id'   => $exercise->id,
            'exercise_type' => $exercise->type->value,
            'records'       => $records,
        ];
    }

    /**
     * @param string $range
     *
     * @return Carbon|null
     */
    private function resolveStartDate(string $range): ?Carbon
    {
        return match ($range) {
            '1m'    => Carbon::now()->subMonth(),
            '3m'    => Carbon::now()->subMonths(3),
            '6m'    => Carbon::now()->subMonths(6),
            '1y'    => Carbon::now()->subYear(),
            default => null,
        };
    }

    /**
     * PRs are detected across all-time history before the range filter, so a
     * short window still marks the points that were records when they landed.
     *
     * @param Exercise       $exercise
     * @param User           $user
     * @param ProgressMetric $metric
     * @param Carbon|null    $startDate
     *
     * @return array<string, mixed>
     */
    private function buildSeries(
        Exercise $exercise,
        User $user,
        ProgressMetric $metric,
        ?Carbon $startDate,
    ): array {
        $allEntries = $this->queryDataPoints($exercise, $user, $metric);
        $prEntryIds = $this->detectPRs($allEntries);

        $rangeEntries = $startDate
            ? $allEntries->filter(fn ($row) => $row->date >= $startDate->toDateString())
            : $allEntries;

        return [
            'metric'      => $metric->value,
            'has_data'    => $allEntries->isNotEmpty(),
            'data_points' => $rangeEntries->map(fn ($row) => [
                'entry_id' => (int) $row->entry_id,
                'date'     => $row->date,
                'value'    => $metric->castValue($row->value),
                'is_pr'    => in_array((int) $row->entry_id, $prEntryIds, true),
            ])->values()->all(),
        ];
    }

    /**
     * @param Exercise       $exercise
     * @param User           $user
     * @param ProgressMetric $metric
     *
     * @return Collection<int, stdClass>
     */
    private function queryDataPoints(
        Exercise $exercise,
        User $user,
        ProgressMetric $metric,
    ): Collection {
        $table  = $metric->table();
        $column = $metric->column();

        return DB::table('workout_entries')
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
            ->orderBy('workouts.date')
            ->orderBy('workout_entries.set_order')
            ->get()->map(function (stdClass $row): stdClass {
                $row->date = Carbon::parse($row->date)->toDateString();

                return $row;
            });
    }

    /**
     * @param Collection<int, stdClass> $entries
     *
     * @return list<int>
     */
    private function detectPRs(Collection $entries): array
    {
        $prEntryIds = [];
        $runningMax = null;

        foreach ($entries as $entry) {
            $value = (float) $entry->value;

            if ($runningMax === null || $value > $runningMax) {
                $runningMax   = $value;
                $prEntryIds[] = (int) $entry->entry_id;
            }
        }

        return $prEntryIds;
    }

    /**
     * @param Exercise    $exercise
     * @param User        $user
     * @param Carbon|null $startDate
     *
     * @return list<array{workout_id: int, date: string, total_volume: float}>
     */
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
            'workout_id'   => (int) $row->workout_id,
            'date'         => Carbon::parse($row->date)->toDateString(),
            'total_volume' => round((float) $row->total_volume, 2),
        ])->all();
    }

    /**
     * @param Exercise       $exercise
     * @param User           $user
     * @param ProgressMetric $metric
     *
     * @return array{value: float|integer, entry_id: integer, date: string}|null
     */
    private function findBest(Exercise $exercise, User $user, ProgressMetric $metric): ?array
    {
        $table  = $metric->table();
        $column = $metric->column();

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
            'value'    => $metric->castValue($result->value),
            'entry_id' => (int) $result->entry_id,
            'date'     => Carbon::parse($result->date)->toDateString(),
        ];
    }

    /**
     * @param Exercise $exercise
     * @param User     $user
     *
     * @return array{value: float, entry_id: integer, date: string}|null
     */
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
            'value'    => round((float) $result->volume, 2),
            'entry_id' => (int) $result->entry_id,
            'date'     => Carbon::parse($result->date)->toDateString(),
        ];
    }
}

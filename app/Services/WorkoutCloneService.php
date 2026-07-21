<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricDimension;
use App\Models\EntryGroup;
use App\Models\Exercise;
use App\Models\TemplateEntryGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutEntry;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkoutCloneService
{
    /**
     * @param WorkoutTemplate      $template
     * @param User                 $user
     * @param array<string, mixed> $attributes
     *
     * @return Workout
     */
    public function fromTemplate(WorkoutTemplate $template, User $user, array $attributes): Workout
    {
        $template->load(['exercises', 'groups']);

        return DB::transaction(function () use ($template, $user, $attributes) {
            $workout      = $this->createWorkout($user, $attributes, $template->name);
            $groupMapping = $this->cloneGroups($template->groups, $workout);
            $this->attachExercises($template->exercises, $workout);
            $this->generateTemplateEntries($template, $workout, $groupMapping);

            return $workout;
        });
    }

    /**
     * @param Workout              $source
     * @param User                 $user
     * @param array<string, mixed> $attributes
     *
     * @return Workout
     */
    public function fromWorkout(Workout $source, User $user, array $attributes): Workout
    {
        // Copy carries targets only: intensityMetric and round actuals are
        // intentionally not loaded because they are never replicated.
        $source->load([
            'exercises',
            'groups',
            'entries.loadMetric',
            'entries.repMetric',
            'entries.durationMetric',
            'entries.distanceMetric',
            'entries.cardioSetting',
            'entries.intervalHeader',
        ]);

        return DB::transaction(function () use ($source, $user, $attributes) {
            $workout      = $this->createWorkout($user, $attributes, $source->name);
            $groupMapping = $this->cloneGroups($source->groups, $workout);
            $this->attachExercises($source->exercises, $workout);
            $this->replicateEntries($source, $workout, $groupMapping);

            return $workout;
        });
    }

    /**
     * @param WorkoutEntry $source
     * @param WorkoutEntry $target
     *
     * @return void
     */
    public function cloneMetrics(WorkoutEntry $source, WorkoutEntry $target): void
    {
        foreach (MetricDimension::cases() as $dimension) {
            // Intensity records how a past performance felt; a fresh copy
            // never inherits it. Pinned by feature test.
            if ($dimension === MetricDimension::Intensity) {
                continue;
            }

            $relation = $dimension->relation();
            $metric   = $source->$relation;

            if ($metric === null) {
                continue;
            }

            $target->$relation()->create($metric->only($dimension->cloneableColumns()));
        }
    }

    /**
     * @param User                 $user
     * @param array<string, mixed> $attributes
     * @param string               $fallbackName
     *
     * @return Workout
     */
    private function createWorkout(User $user, array $attributes, string $fallbackName): Workout
    {
        return Workout::create([
            'name'    => $attributes['name'] ?? $fallbackName,
            'user_id' => $user->id,
            'date'    => $attributes['date'],
        ]);
    }

    /**
     * Clone groups onto the workout, mapping source group id to clone id.
     *
     * @param Collection<int, EntryGroup>|Collection<int, TemplateEntryGroup> $groups
     * @param Workout                                                         $workout
     *
     * @return array<int, int>
     */
    private function cloneGroups(Collection $groups, Workout $workout): array
    {
        $mapping = [];

        foreach ($groups as $group) {
            $clone = $workout->groups()->create([
                'name'                           => $group->name,
                'planned_rounds'                 => $group->planned_rounds,
                'rest_between_exercises_seconds' => $group->rest_between_exercises_seconds,
                'rest_between_rounds_seconds'    => $group->rest_between_rounds_seconds,
            ]);
            $mapping[$group->id] = $clone->id;
        }

        return $mapping;
    }

    /**
     * @param Collection<int, Exercise> $exercises
     * @param Workout                   $workout
     *
     * @return void
     */
    private function attachExercises(Collection $exercises, Workout $workout): void
    {
        foreach ($exercises as $exercise) {
            $workout->exercises()->attach($exercise->id, [
                'exercise_order' => $exercise->pivot->exercise_order,
            ]);
        }
    }

    /**
     * @param WorkoutTemplate $template
     * @param Workout         $workout
     * @param array<int, int> $groupMapping
     *
     * @return void
     */
    private function generateTemplateEntries(WorkoutTemplate $template, Workout $workout, array $groupMapping): void
    {
        /** @var Collection<int, Exercise> $exercises */
        $exercises = $template->exercises;

        $processedGroups = [];
        $setOrder        = 0;

        foreach ($exercises as $exercise) {
            $templateGroupId = $exercise->pivot->template_entry_group_id;

            if ($templateGroupId === null) {
                $workout->entries()->create([
                    'exercise_id' => $exercise->id,
                    'set_order'   => $setOrder++,
                ]);
            } elseif (! in_array($templateGroupId, $processedGroups, true)) {
                $processedGroups[] = $templateGroupId;
                $templateGroup     = $template->groups->firstWhere('id', $templateGroupId);
                $workoutGroupId    = $groupMapping[$templateGroupId];

                $groupExercises = $exercises
                    ->filter(fn (Exercise $e) => $e->pivot->template_entry_group_id === $templateGroupId)
                    ->sortBy(fn (Exercise $e) => $e->pivot->exercise_order);

                for ($round = 1; $round <= $templateGroup->planned_rounds; $round++) {
                    foreach ($groupExercises as $groupExercise) {
                        $workout->entries()->create([
                            'exercise_id'    => $groupExercise->id,
                            'set_order'      => $setOrder++,
                            'entry_group_id' => $workoutGroupId,
                            'group_round'    => $round,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param Workout         $source
     * @param Workout         $workout
     * @param array<int, int> $groupMapping
     *
     * @return void
     */
    private function replicateEntries(Workout $source, Workout $workout, array $groupMapping): void
    {
        foreach ($source->entries as $entry) {
            $clone = $workout->entries()->create([
                'exercise_id'    => $entry->exercise_id,
                'set_order'      => $entry->set_order,
                'entry_group_id' => $entry->entry_group_id
                    ? ($groupMapping[$entry->entry_group_id] ?? null)
                    : null,
                'group_round' => $entry->group_round,
                'notes'       => $entry->notes,
            ]);

            $this->cloneMetrics($entry, $clone);
        }
    }
}

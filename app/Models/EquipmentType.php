<?php

declare(strict_types=1);

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'user_id', 'is_system'];

    /**
     * System rows are visible to all users; user rows only to their owner.
     * Query closure and instance predicate must change together.
     *
     * @param User $user
     *
     * @return Closure
     */
    public static function visibilityConstraint(User $user): Closure
    {
        return fn ($query) => $query->where('is_system', true)->orWhere('user_id', $user->id);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Exercise, $this> */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class, 'equipment_type_id');
    }

    /**
     * @param Builder<EquipmentType> $query
     * @param User                   $user
     *
     * @return Builder<EquipmentType>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(static::visibilityConstraint($user));
    }

    /**
     * @param User $user
     *
     * @return boolean
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->is_system || $this->user_id === $user->id;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}

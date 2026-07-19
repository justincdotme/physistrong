<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait LocksForReorder
{
    /**
     * Serializes concurrent reorders so two requests cannot compute the same max order.
     *
     * @param Builder<static> $query
     * @param integer|string  $id
     *
     * @return Builder<static>
     */
    public function scopeWhereKeyLocked(Builder $query, int|string $id): Builder
    {
        return $query->whereKey($id)->lockForUpdate();
    }
}

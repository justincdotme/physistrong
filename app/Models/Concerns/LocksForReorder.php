<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait LocksForReorder
{
    /**
     * Lock the parent row for reordering child collections.
     *
     * Discarded read: holding the parent row serializes concurrent
     * attach/reorder so two attaches cannot compute the same max order.
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

<?php

namespace App\Services\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function apply(Builder $query, string $term, array $columns = ['title', 'description']): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereFullText($columns, $term);
        }

        return $query->where(function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}

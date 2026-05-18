<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSort
{
    /**
     * Применяет сортировку к запросу.
     */
    protected function applySort(Builder $q, ?string $sort, string $defaultColumn = 'created_at'): void
    {
        if (!$sort) {
            $q->orderBy($defaultColumn, 'desc');
            return;
        }

        // Если в строке есть подчеркивание (напр. name_desc)
        if (str_contains($sort, '_')) {
            $parts = explode('_', $sort);
            $direction = array_pop($parts); // берет последний элемент (asc или desc)
            $column = implode('_', $parts); // склеивает остальное (на случай если колонка user_id)
        } else {
            $column = $sort;
            $direction = 'asc';
        }

        // Валидация направления
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $q->orderBy($column, $direction);
    }

}
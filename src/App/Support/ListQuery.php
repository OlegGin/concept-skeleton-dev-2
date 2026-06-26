<?php declare(strict_types=1);

namespace Concept\App\Support;

use Concept\App\Models\BaseModel;

final class ListQuery
{
    /**
     * @param array<string, mixed> $params
     * @param list<string> $keys
     *
     * @return array<string, string>
     */
    public function filters(array $params, array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $value = $params[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $allowedFields
     *
     * @return array{0: string, 1: string}
     */
    public function sort(
        array $params,
        array $allowedFields,
        string $defaultField,
        string $defaultDirection = BaseModel::SORT_ASC,
    ): array {
        $sortBy = $params['sort_by'] ?? $defaultField;
        if (!is_string($sortBy) || !in_array($sortBy, $allowedFields, true)) {
            $sortBy = $defaultField;
        }

        $sortDirection = $params['sort_direction'] ?? $defaultDirection;
        if (!is_string($sortDirection)) {
            $sortDirection = $defaultDirection;
        }

        $sortDirection = strtolower($sortDirection);
        if (!in_array($sortDirection, [BaseModel::SORT_ASC, BaseModel::SORT_DESC], true)) {
            $sortDirection = $defaultDirection;
        }

        return [$sortBy, $sortDirection];
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array<string, string>
     */
    public function context(array $filters, string $sortBy, string $sortDirection): array
    {
        return array_merge($filters, [
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ]);
    }

    /**
     * @return 'asc'|'desc'
     */
    public function direction(string $sortDirection): string
    {
        return $sortDirection === BaseModel::SORT_DESC ? BaseModel::SORT_DESC : BaseModel::SORT_ASC;
    }

    public function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}

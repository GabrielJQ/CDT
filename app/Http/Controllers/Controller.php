<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected const DEFAULT_PAGE_SIZE = 50;

    protected function applyRegionFilter(): array
    {
        return [
            'region' => request()->cookie('region_filter', ''),
            'uo' => request()->cookie('uo_filter', ''),
        ];
    }

    protected function paginateArray(array $items): array
    {
        $page = max(1, (int) request()->query('page', 1));
        $perPage = max(10, min(100, (int) request()->query('per_page', self::DEFAULT_PAGE_SIZE)));
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        return [
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    protected function paginationMeta(int $page, int $perPage, int $total): array
    {
        return [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    protected function paginationInput(): array
    {
        return [
            max(1, (int) request()->query('page', 1)),
            max(10, min(100, (int) request()->query('per_page', self::DEFAULT_PAGE_SIZE))),
        ];
    }
}

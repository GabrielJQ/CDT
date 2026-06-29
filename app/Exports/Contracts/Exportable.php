<?php

namespace App\Exports\Contracts;

interface Exportable
{
    public function filename(): string;

    public function headings(): array;

    public function data(array $filters): iterable;

    public function map(array $row): array;
}

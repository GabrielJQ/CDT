<?php

namespace App\Presenters;

class RenderTiendaPresentador
{
    public static function renderStoreName(string $name, bool $esTiendaSalud): string
    {
        $name = e($name ?: '—');
        if ($esTiendaSalud) {
            $dot = '<span class="inline-block w-3 h-3 rounded-full bg-purple-500 flex-shrink-0 ring-2 ring-purple-300 dark:ring-purple-700" title="Tienda de Salud"></span>';
            $badge = '<span class="text-[10px] font-semibold text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/50 px-1.5 py-0.5 rounded leading-tight">Tienda de Salud</span>';

            return '<span class="inline-flex items-center gap-1.5 flex-wrap">'.$dot.'<strong class="text-gray-900 dark:text-gray-100">'.$name.'</strong>'.$badge.'</span>';
        }

        return '<strong class="text-gray-900 dark:text-gray-100">'.$name.'</strong>';
    }

    public static function formatDate(?string $date): string
    {
        if (! $date) {
            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        $parts = explode('-', substr($date, 0, 10));
        if (count($parts) !== 3) {
            return e($date);
        }

        return '<span class="font-mono text-gray-700 dark:text-gray-300">'.$parts[2].'/'.$parts[1].'/'.$parts[0].'</span>';
    }

    public static function formatMoney(string|float $val): string
    {
        if (is_string($val)) {
            $num = (float) str_replace([',', '$', ' '], '', $val);
        } else {
            $num = $val;
        }

        return '$'.number_format($num, 2);
    }

    public static function isEmpty(?string $val): bool
    {
        return $val === '' || $val === null || $val === '0' || trim($val) === '';
    }

    public static function yesNoBadge(?string $value): string
    {
        $normalized = strtoupper(trim($value ?? ''));

        return match ($normalized) {
            'S' => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Sí</span>',
            'N' => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">No</span>',
            default => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">—</span>',
        };
    }
}

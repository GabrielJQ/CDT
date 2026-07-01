<?php

namespace App\Presenters;

class IndicadorPresenter
{
    private const FACTOR_KEYS = [
        'capital_bajo',
        'capital_dictaminado_bajo',
        'comite_vencido',
        'auditoria_elevada',
        'pagare_vencido',
        'rotacion_baja',
        'asamblea_pendiente',
    ];

    private const FACTOR_LABELS = [
        'capital_bajo' => '💰 Capital total bajo',
        'capital_dictaminado_bajo' => '🏛️ Capital Bienestar bajo',
        'comite_vencido' => '📅 Comité vencido',
        'auditoria_elevada' => '🔍 Auditoría > $500k',
        'pagare_vencido' => '📄 Pagaré vencido',
        'rotacion_baja' => '📉 Rotación baja',
        'asamblea_pendiente' => '🗳️ Asamblea pendiente',
    ];

    private const FACTOR_STYLES = [
        'capital_bajo' => ['bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700', '💰'],
        'capital_dictaminado_bajo' => ['bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-700', '🏛️'],
        'comite_vencido' => ['bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700', '📅'],
        'auditoria_elevada' => ['bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-700', '🔍'],
        'pagare_vencido' => ['bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700', '📄'],
        'rotacion_baja' => ['bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700', '📉'],
        'asamblea_pendiente' => ['bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-700', '🗳️'],
    ];

    private const LEVEL_BADGES = [
        'rojo' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', '🔴 %d — Crítico'],
        'amarillo' => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', '🟡 %d — Monitoreo'],
        'verde' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 %d — Normal'],
    ];

    private const LEVEL_DISPLAY_LABELS = [
        'rojo' => 'Crítico',
        'amarillo' => 'Monitoreo',
        'verde' => 'Normal',
    ];

    public static function factorKeys(): array
    {
        return self::FACTOR_KEYS;
    }

    public static function factorLabels(): array
    {
        return self::FACTOR_LABELS;
    }

    public static function factorStyles(): array
    {
        return self::FACTOR_STYLES;
    }

    public static function factorLabel(string $key): string
    {
        return self::FACTOR_LABELS[$key] ?? $key;
    }

    public static function factorStyle(string $key): array
    {
        return self::FACTOR_STYLES[$key] ?? ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600', '▪'];
    }

    /** @return array{classes: string, label: string} */
    public static function levelBadge(string $level, int $count = 0): array
    {
        $cfg = self::LEVEL_BADGES[$level] ?? self::LEVEL_BADGES['verde'];

        return [
            'classes' => $cfg[0],
            'label' => sprintf($cfg[1], $count),
        ];
    }

    public static function cleanLabel(string $label): string
    {
        return preg_replace('/^[^\p{L}\p{N}]+/u', '', $label);
    }

    public static function levelDisplayLabel(string $level): string
    {
        return self::LEVEL_DISPLAY_LABELS[$level] ?? $level;
    }
}

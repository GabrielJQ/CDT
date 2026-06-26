<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Admin = 'admin';
    case Nacional = 'nacional';
    case Regional = 'regional';
    case Unidad = 'unidad';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Nacional => 'Nacional',
            self::Regional => 'Regional',
            self::Unidad => 'Unidad Operativa',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acceso total a todos los módulos, importación de datos y administración de usuarios.',
            self::Nacional => 'Acceso a todos los módulos operativos e importación de datos. No administra usuarios.',
            self::Regional => 'Acceso limitado a su región asignada. Puede filtrar por Unidad Operativa dentro de su región.',
            self::Unidad => 'Acceso limitado únicamente a su Unidad Operativa asignada.',
        };
    }

    public function isGlobal(): bool
    {
        return $this === self::Admin || $this === self::Nacional;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function canImportGlobal(): bool
    {
        return $this === self::Admin || $this === self::Nacional;
    }
}

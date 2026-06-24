<?php

namespace App\Console\Commands;

use App\Servicios\ServicioJerarquiaOperativa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jerarquia:sincronizar')]
#[Description('Sincroniza regiones y unidades operativas desde tiendas activas')]
class SincronizarJerarquiaOperativa extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ServicioJerarquiaOperativa $jerarquia): int
    {
        $resultado = $jerarquia->sincronizar();

        $this->info("Regiones sincronizadas: {$resultado['regiones']}");
        $this->info("Unidades operativas sincronizadas: {$resultado['unidades']}");

        return self::SUCCESS;
    }
}

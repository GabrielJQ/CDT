<?php

namespace App\Servicios;

class ServicioDerivadosTienda
{
    public function __construct(
        private ServicioAuditoria $auditoria,
        private ServicioTiendaCritica $critica,
        private ServicioGeo $geo,
    ) {}

    /**
     * @return array{nivel_critico: string|null, factores_criticos_count: int|null, estado_geo: string|null, estado_comite: string|null, rango_rotacion: string|null, auditoria_pendiente: bool|null}
     */
    public function calcular(array $store, ?string $only = null): array
    {
        $derivados = [];

        if ($only === null || in_array($only, ['criticidad', 'fecha'], true)) {
            $critico = $this->critica->evaluarTienda($store);
            $derivados['nivel_critico'] = $critico['level'] ?? null;
            $derivados['factores_criticos_count'] = $critico['count'] ?? null;
        }

        if ($only === null || in_array($only, ['auditoria', 'fecha'], true)) {
            $audit = $this->auditoria->evaluarTienda($store);
            $derivados['estado_comite'] = $audit['estadoComite'] ?? null;
            $derivados['rango_rotacion'] = $audit['rangoRotacion'] ?? null;
            $derivados['auditoria_pendiente'] = $audit['auditoriaPendiente'] ?? null;
        }

        if ($only === null || $only === 'geo') {
            $geoStatus = $this->geo->evaluarGeo($store);
            $derivados['estado_geo'] = $geoStatus['status'] ?? null;
        }

        return $derivados;
    }

    public function agregar(array $store, ?string $only = null): array
    {
        return array_merge($this->normalizar($store), $this->calcular($store, $only));
    }

    public function normalizar(array $store): array
    {
        if (isset($store['Nombre_UniOpe']) && is_string($store['Nombre_UniOpe'])) {
            $store['Nombre_UniOpe'] = preg_replace('/^U\.O\.\s+/i', '', $store['Nombre_UniOpe']);
        }

        return $store;
    }
}

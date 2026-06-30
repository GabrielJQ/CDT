<?php

namespace App\Livewire;

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioCasaPorCasa;
use Livewire\Component;

class CasaXCasaMapa extends Component
{
    protected ServicioAlcanceUsuario $alcanceUsuario;

    protected ServicioCasaPorCasa $cxc;

    public string $almacen = '';

    public string $estado = '';

    public string $uo = '';

    public string $estatus = '';

    public string $anaquelStatus = '';

    public string $buscar = '';

    protected $queryString = [
        'almacen' => ['except' => ''],
        'estado' => ['except' => ''],
        'uo' => ['except' => ''],
        'estatus' => ['except' => ''],
        'anaquelStatus' => ['except' => ''],
        'buscar' => ['except' => ''],
    ];

    public function boot(ServicioAlcanceUsuario $alcanceUsuario, ServicioCasaPorCasa $cxc): void
    {
        $this->alcanceUsuario = $alcanceUsuario;
        $this->cxc = $cxc;
    }

    public function updated($property): void
    {
        if (in_array($property, ['almacen', 'estado', 'uo', 'estatus', 'anaquelStatus', 'buscar'], true)) {
            $this->dispatch('cxc-mapa-filters-updated',
                almacen: $this->almacen,
                estado: $this->estado,
                uo: $this->uo,
                estatus: $this->estatus,
                anaquelStatus: $this->anaquelStatus,
                buscar: $this->buscar,
            );
        }
    }

    public function clearFilters(): void
    {
        $this->almacen = '';
        $this->estado = '';
        $this->uo = '';
        $this->estatus = '';
        $this->anaquelStatus = '';
        $this->buscar = '';
    }

    private function uoFilter(): array
    {
        return $this->cxc->resolveUoFilter();
    }

    private function filterOptions(): array
    {
        return $this->cxc->directorioFilterOptions($this->uoFilter());
    }

    public function mapaData(): array
    {
        $uoFilter = $this->uoFilter();
        $q = $this->cxc->mapaQuery($uoFilter);

        $this->applyFilters($q);

        $totalCount = (clone $q)->count();

        $conCoordenadas = (clone $q)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0)
            ->count();

        $anaqueles = [
            'instalados' => (clone $q)->where('anaqueles_instalados', true)->count(),
            'pendientes' => (clone $q)->where('anaqueles_instalados', false)->count(),
        ];

        return compact('totalCount', 'conCoordenadas', 'anaqueles');
    }

    private function applyFilters($query): void
    {
        if ($this->almacen !== '') {
            $query->where('almacen', 'ILIKE', "%{$this->almacen}%");
        }
        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }
        if ($this->uo !== '') {
            $query->where('unidad_operativa', $this->uo);
        }
        if ($this->estatus !== '') {
            $query->where('estatus', $this->estatus);
        }
        if ($this->anaquelStatus === 'instalados') {
            $query->where('anaqueles_instalados', true);
        } elseif ($this->anaquelStatus === 'pendientes') {
            $query->where('anaqueles_instalados', false);
        }
        if ($this->buscar !== '') {
            $term = "%{$this->buscar}%";
            $query->where(function ($q) use ($term) {
                $q->where('almacen', 'ILIKE', $term)
                    ->orWhere('no_tienda', 'ILIKE', $term)
                    ->orWhere('municipio', 'ILIKE', $term);
            });
        }
    }

    public function render()
    {
        return view('livewire.casa-x-casa-mapa', [
            'filterOptions' => $this->filterOptions(),
        ]);
    }
}

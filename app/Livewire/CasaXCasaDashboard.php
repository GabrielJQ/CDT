<?php

namespace App\Livewire;

use App\Servicios\ServicioCasaPorCasa;
use Livewire\Component;

class CasaXCasaDashboard extends Component
{
    protected ServicioCasaPorCasa $casaPorCasa;

    public function boot(ServicioCasaPorCasa $casaPorCasa): void
    {
        $this->casaPorCasa = $casaPorCasa;
    }

    public function dashboardData(): array
    {
        return $this->casaPorCasa->dashboardData();
    }

    public function render()
    {
        return view('livewire.casa-x-casa-dashboard');
    }
}

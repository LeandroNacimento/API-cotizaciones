<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;

class DashboardCotizaciones extends Component
{
    public string $tipo = 'blue';

    /** Tipos visibles en el dashboard (lo que pediste) */
    public array $tiposPermitidos = [
        'oficial', 'blue', 'bolsa', 'ccl', 'tarjeta', 'mayorista', 'cripto',
    ];

    /** Etiquetas para mostrar en UI */
    public array $labels = [
        'oficial'   => 'Oficial',
        'blue'      => 'Blue',
        'bolsa'     => 'Bolsa',
        'ccl'       => 'CCL (contado con liquidación)',
        'tarjeta'   => 'Tarjeta',
        'mayorista' => 'Mayorista',
        'cripto'    => 'Cripto',
    ];

    /**
     * Mapeo opcional por si en tu DB el slug es distinto.
     * Ajustá estos valores si hace falta (ejemplo: 'ccl' => 'contado_con_liqui').
     */
    public array $aliasDb = [
        'oficial'   => 'oficial',
        'blue'      => 'blue',
        'bolsa'     => 'bolsa',          // si en tu DB es 'mep', cambiá aquí a 'mep'
        'ccl'       => 'ccl',            // si es 'contado_con_liqui' o similar, ponelo aquí
        'tarjeta'   => 'tarjeta',        // antes lo llamabas 'turista'? cambia a 'turista' si corresponde
        'mayorista' => 'mayorista',
        'cripto'    => 'cripto',
    ];

    // Datos para tarjetas y gráfico
    public array $cards = [];
    public array $chart = [
        'labels' => [],
        'series' => [
            ['name' => 'Compra', 'data' => []],
            ['name' => 'Venta',  'data' => []],
        ],
    ];

    public function mount()
    {
        $this->tipo = request('tipo', 'blue');
        if (!in_array($this->tipo, $this->tiposPermitidos, true)) {
            $this->tipo = 'blue';
        }

        $this->loadCards();
        $this->loadChart();
    }

    public function updatedTipo()
    {
        if (!in_array($this->tipo, $this->tiposPermitidos, true)) {
            $this->tipo = 'blue';
        }
        $this->loadChart();
    }

    private function loadCards(): void
    {
        $cards = [];

        foreach ($this->tiposPermitidos as $t) {
            $lookup = $this->aliasDb[$t] ?? $t;

            $row = Cotizacion::query()
                ->where('tipo', $lookup)
                ->select('compra', 'venta', 'fecha', 'created_at')
                // Toma la más reciente por fecha y, si es null, por created_at
                ->orderByDesc(DB::raw('COALESCE(fecha, created_at)'))
                ->first();

            $fecha = null;
            if ($row?->fecha) {
                $fecha = Carbon::parse($row->fecha)->format('d/m/Y');
            } elseif ($row?->created_at) {
                $fecha = Carbon::parse($row->created_at)->format('d/m/Y');
            }

            $cards[$t] = [
                'compra' => is_null($row?->compra) ? null : (float) $row->compra,
                'venta'  => is_null($row?->venta)  ? null : (float) $row->venta,
                'fecha'  => $fecha,
            ];
        }

        $this->cards = $cards;
    }

    private function loadChart(): void
    {
        $lookup = $this->aliasDb[$this->tipo] ?? $this->tipo;

        // Últimos 30 días (incluye hoy)
        $hasta = Carbon::today();
        $desde = (clone $hasta)->subDays(29);

        // Promedios por día
        $rows = Cotizacion::query()
            ->where('tipo', $lookup)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('DATE(fecha) as dia, AVG(compra) as avg_compra, AVG(venta) as avg_venta')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $labels = [];
        $serieCompra = [];
        $serieVenta  = [];

        $map = $rows->keyBy('dia');

        $cursor = $desde->copy();
        while ($cursor->lte($hasta)) {
            $key = $cursor->toDateString();
            $labels[]      = $cursor->format('d/m');
            $serieCompra[] = isset($map[$key]) && $map[$key]->avg_compra !== null
                ? round((float)$map[$key]->avg_compra, 2)
                : null;
            $serieVenta[]  = isset($map[$key]) && $map[$key]->avg_venta !== null
                ? round((float)$map[$key]->avg_venta, 2)
                : null;

            $cursor->addDay();
        }

        $this->chart = [
            'labels' => $labels,
            'series' => [
                ['name' => 'Compra', 'data' => $serieCompra],
                ['name' => 'Venta',  'data' => $serieVenta],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard-cotizaciones')
            ->layout('layouts.app') // si preferís evitar el warning del IDE, podés envolver desde un Blade
            ->title('Cotizaciones • Dashboard');
    }
}

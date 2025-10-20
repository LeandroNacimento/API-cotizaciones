<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CotizacionService;

class Cotizar extends Component
{
    public string $tipo = 'blue';
    public ?string $monto = null;
    public ?float $compra = null;
    public ?float $venta = null;
    public ?float $resultado = null;
    public ?string $usada = null;
    public ?string $fecha = null;
    public ?string $error = null;

    public array $tiposPermitidos = ['oficial','blue','bolsa','ccl','tarjeta','mayorista','cripto'];

    public array $labels = [
        'oficial'   => 'Oficial',
        'blue'      => 'Blue',
        'bolsa'     => 'Bolsa',
        'ccl'       => 'CCL (contado con liquidación)',
        'tarjeta'   => 'Tarjeta',
        'mayorista' => 'Mayorista',
        'cripto'    => 'Cripto',
    ];

    public function updatedTipo(): void
    {
        if (!in_array($this->tipo, $this->tiposPermitidos, true)) {
            $this->tipo = 'blue';
        }
        $this->resetResultado();
    }

    public function updatedMonto(): void
    {
        $this->resetResultado();
    }

    private function resetResultado(): void
    {
        $this->compra = null;
        $this->venta = null;
        $this->resultado = null;
        $this->usada = null;
        $this->fecha = null;
        $this->error = null;
    }

   public function cotizarViaApi(CotizacionService $service): void
{
    $this->resetResultado();
    $this->error = null;

    // Validaciones claras
    $raw = (string)($this->monto ?? '');
    $raw = trim($raw);

    if ($raw === '') {
        $this->error = 'Ingresá un monto en USD para cotizar.';
        return;
    }

    // Acepta coma o punto
    $monto = str_replace(',', '.', $raw);
    if (!is_numeric($monto)) {
        $this->error = 'El monto debe ser numérico. Ej: 150.50';
        return;
    }

    if (!in_array($this->tipo, $this->tiposPermitidos, true)) {
        $this->error = 'Seleccioná un tipo de cambio válido.';
        return;
    }

    // Lógica original
    $res = $service->convertir((float)$monto, $this->tipo);

    if (!($res['ok'] ?? false)) {
        $this->error = $res['error'] ?? 'Error inesperado.';
        return;
    }

    $this->compra    = $res['compra'];
    $this->venta     = $res['venta'];
    $this->resultado = $res['resultado_en_pesos'];
    $this->usada     = $res['cotizacion_usada'];
    $this->fecha     = now()->format('d/m/Y');
}


    public function render()
    {
        return view('livewire.cotizar')
            ->layout('layouts.app')
            ->title('Cotizar • USD → ARS');
    }
}

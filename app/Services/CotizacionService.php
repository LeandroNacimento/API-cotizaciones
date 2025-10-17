<?php

namespace App\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class CotizacionService
{
    private array $tiposPermitidos = ['oficial', 'blue', 'bolsa', 'ccl', 'tarjeta', 'mayorista', 'cripto'];

    public function convertir(float $valorUSD, string $tipo): array
    {
        $tipo = strtolower($tipo);

        if (!in_array($tipo, $this->tiposPermitidos, true)) {
            return [
                'ok'    => false,
                'error' => "Tipo inválido. Use: " . implode(', ', $this->tiposPermitidos),
                'http'  => 422,
            ];
        }

        $baseUrl = rtrim(config('services.dolarapi.url'), '/');
        $timeout = (int) config('services.dolarapi.timeout', 5);
        $endpoint = "{$baseUrl}/{$tipo}";

        // Traer cotización (cache 60s)
        $data = Cache::remember("cotizacion_{$tipo}", 60, function () use ($endpoint, $timeout) {
            $response = Http::timeout($timeout)->retry(2, 200)->get($endpoint);
            if ($response->failed()) {
                return null;
            }
            return $response->json();
        });

        if (!$data) {
            return [
                'ok'    => false,
                'error' => 'No se pudo obtener la cotización externa.',
                'http'  => 502,
            ];
        }

        $compra = $data['compra'] ?? $data['buy'] ?? null;
        $venta  = $data['venta']  ?? $data['sell'] ?? null;

        if (!is_numeric($compra) && !is_numeric($venta)) {
            return [
                'ok'    => false,
                'error' => 'Cotización no disponible.',
                'http'  => 502,
            ];
        }

        // Calcular en pesos usando "venta" por defecto
        $cotizacion = is_numeric($venta) ? (float) $venta : (float) $compra;
        $resultado  = $valorUSD * $cotizacion;

        // Guardar histórico
        $ahora = Carbon::now();
        Cotizacion::create([
            'tipo'    => $tipo,
            'momento' => $ahora,
            'fecha'   => $ahora->toDateString(),
            'compra'  => is_numeric($compra) ? (float) $compra : null,
            'venta'   => is_numeric($venta)  ? (float) $venta  : null,
        ]);

        return [
            'ok'                 => true,
            'tipo'               => $tipo,
            'valor_dolar'        => $valorUSD,
            'cotizacion_usada'   => is_numeric($venta) ? 'venta' : 'compra',
            'compra'             => is_numeric($compra) ? (float) $compra : null,
            'venta'              => is_numeric($venta)  ? (float) $venta  : null,
            'resultado_en_pesos' => round($resultado, 2),
            'fuente'             => $endpoint,
        ];
    }
}

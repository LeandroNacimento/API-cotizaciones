<?php

namespace App\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CotizacionService
{
    private array $tiposPermitidos = ['oficial','blue','bolsa','ccl','tarjeta','mayorista','cripto'];

    /**
     * Candidatos de slugs por tipo interno.
     * Se prueban en orden hasta obtener 200.
     */
    private array $slugCandidates = [
        'oficial'   => ['oficial'],
        'blue'      => ['blue'],
        'bolsa'     => ['bolsa','mep'],
        'ccl'       => ['contadoconliqui','ccl'],
        'tarjeta'   => ['tarjeta','qatar'], // por si la fuente lo mueve
        'mayorista' => ['mayorista'],
        'cripto'    => ['cripto'],
    ];

    public function convertir(float $valorUSD, string $tipo): array
    {
        $tipo = strtolower($tipo);

        if (!in_array($tipo, $this->tiposPermitidos, true)) {
            return ['ok'=>false,'error'=>"Tipo inválido. Use: ".implode(', ', $this->tiposPermitidos),'http'=>422];
        }

        $baseUrl = rtrim(config('services.dolarapi.url'), '/');
        $timeout = (int) config('services.dolarapi.timeout', 5);

        // Intento: probar slugs hasta que alguno responda 200
        $slugOk = null;
        $data = null;

        foreach ($this->slugCandidates[$tipo] ?? [$tipo] as $slug) {
            $endpoint = "{$baseUrl}/{$slug}";

            $data = Cache::remember("cotizacion_{$slug}", 60, function () use ($endpoint, $timeout) {
                $resp = Http::timeout($timeout)->retry(2, 200)->get($endpoint);
                if ($resp->successful()) {
                    return $resp->json();
                }
                // Guardá un pequeño “marcador” para evitar martillar el 404 durante 60s
                return ['__failed' => true, '__status' => $resp->status()];
            });

            if (!empty($data) && empty($data['__failed'])) {
                $slugOk = $slug;
                break;
            }
        }

        if (!$slugOk) {
            return [
                'ok'    => false,
                'error' => 'No se pudo obtener la cotización externa para este tipo (404/indisponible en la fuente).',
                'http'  => 502,
            ];
        }

        $compra = $data['compra'] ?? $data['buy'] ?? null;
        $venta  = $data['venta']  ?? $data['sell'] ?? null;

        if (!is_numeric($compra) && !is_numeric($venta)) {
            return ['ok'=>false,'error'=>'Cotización no disponible.','http'=>502];
        }

        $cotizacion = is_numeric($venta) ? (float) $venta : (float) $compra;
        $resultado  = $valorUSD * $cotizacion;

        // Guardar histórico con tu “tipo” interno (no el slug)
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
            'fuente'             => "{$baseUrl}/{$slugOk}",
        ];
    }
}

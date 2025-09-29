<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class CotizacionController extends Controller
{
public function convertir(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'valor' => ['required', 'numeric'],
            'tipo'  => ['nullable', 'string'],
        ], [
            'valor.required' => 'Debe enviar un valor numérico en dólares.',
            'valor.numeric'  => 'El valor debe ser numérico.',
        ]);

        $valorUSD = (float) $validated['valor'];
        $tipo     = strtolower($validated['tipo'] ?? 'oficial');

        $tiposPermitidos = ['oficial','blue','bolsa','contadoconliqui','turista','mayorista'];
        if (!in_array($tipo, $tiposPermitidos, true)) {
            return response()->json([
                'error' => "Tipo inválido. Use: " . implode(', ', $tiposPermitidos)
            ], 422);
        }

        $baseUrl = rtrim(config('services.dolarapi.url'), '/');
        $timeout = (int) config('services.dolarapi.timeout', 5);
        $endpoint = "{$baseUrl}/{$tipo}";

        // Traer cotización (cache 60s)
        $data = Cache::remember("cotizacion_{$tipo}", 60, function () use ($endpoint, $timeout) {
            $response = Http::timeout($timeout)->retry(2, 200)->get($endpoint);
            if ($response->failed()) abort(502, 'No se pudo obtener la cotización externa.');
            return $response->json();
        });

        $compra = $data['compra'] ?? $data['buy']   ?? null;
        $venta  = $data['venta']  ?? $data['sell']  ?? null;

        if ((!$compra && !$venta) || (!is_null($venta) && !is_numeric($venta) && !is_null($compra) && !is_numeric($compra))) {
            return response()->json(['error' => 'Cotización no disponible.'], 502);
        }

        // Calcular resultado en pesos usando "venta" por defecto
        $cotizacion = is_numeric($venta) ? (float) $venta : (float) $compra;
        $resultado  = $valorUSD * $cotizacion;

        // === NUEVO: Guardar registro histórico ===
        $ahora = Carbon::now(); // si querés UTC: now('UTC')
        Cotizacion::create([
            'tipo'    => $tipo,
            'momento' => $ahora,
            'fecha'   => $ahora->toDateString(),
            'compra'  => is_numeric($compra) ? (float) $compra : null,
            'venta'   => is_numeric($venta)  ? (float) $venta  : null,
        ]);

        return response()->json([
            'tipo'                => $tipo,
            'valor_dolar'         => $valorUSD,
            'cotizacion_usada'    => is_numeric($venta) ? 'venta' : 'compra',
            'compra'              => $compra ? (float) $compra : null,
            'venta'               => $venta  ? (float) $venta  : null,
            'resultado_en_pesos'  => round($resultado, 2),
            'fuente'              => $endpoint,
            'guardado'            => true,
        ]);
    }

    // === NUEVO: Promedio mensual ===
    // GET /api/promedio-mensual?tipo=blue&valor=venta&anio=2025&mes=9
    public function promedioMensual(Request $request)
    {
        $validated = $request->validate([
            'tipo'  => ['required','string'],
            'valor' => ['required','in:compra,venta'],
            'anio'  => ['required','integer','min:2000','max:2100'],
            'mes'   => ['required','integer','min:1','max:12'],
        ]);

        $tipo  = strtolower($validated['tipo']);
        $campo = $validated['valor']; // compra | venta
        $anio  = (int) $validated['anio'];
        $mes   = (int) $validated['mes'];

        $promedio = Cotizacion::query()
            ->tipo($tipo)
            ->periodo($anio, $mes)
            ->whereNotNull($campo)
            ->avg($campo);

        $count = Cotizacion::query()
            ->tipo($tipo)
            ->periodo($anio, $mes)
            ->whereNotNull($campo)
            ->count();

        return response()->json([
            'tipo'     => $tipo,
            'valor'    => $campo,
            'anio'     => $anio,
            'mes'      => $mes,
            'muestras' => $count,
            'promedio' => $promedio ? round((float)$promedio, 2) : null,
            'rango'    => [
                'desde' => Carbon::create($anio, $mes, 1)->toDateString(),
                'hasta' => Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString(),
            ],
        ]);
    }

    // === NUEVO: Historial por mes y tipo (lista de puntos del mes) ===
    // GET /api/historial?tipo=blue&anio=2025&mes=9
    public function historialMensual(Request $request)
    {
        $validated = $request->validate([
            'tipo' => ['required','string'],
            'anio' => ['required','integer','min:2000','max:2100'],
            'mes'  => ['required','integer','min:1','max:12'],
        ]);

        $tipo = strtolower($validated['tipo']);
        $anio = (int) $validated['anio'];
        $mes  = (int) $validated['mes'];

        $items = Cotizacion::query()
            ->tipo($tipo)
            ->periodo($anio, $mes)
            ->orderBy('momento')
            ->get(['momento','compra','venta'])
            ->map(fn($r) => [
                'momento' => $r->momento->toIso8601String(),
                'compra'  => is_null($r->compra) ? null : (float)$r->compra,
                'venta'   => is_null($r->venta)  ? null : (float)$r->venta,
            ]);

        return response()->json([
            'tipo'  => $tipo,
            'anio'  => $anio,
            'mes'   => $mes,
            'total' => $items->count(),
            'datos' => $items,
        ]);
    }
}
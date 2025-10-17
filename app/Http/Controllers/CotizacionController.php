<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\CotizacionService;

class CotizacionController extends Controller
{
  public function __construct(private CotizacionService $service) {}

    public function convertir(Request $request)
    {
        $validated = $request->validate([
            'valor' => ['required','numeric'],
            'tipo'  => ['nullable','string'],
        ], [
            'valor.required' => 'Debe enviar un valor numérico en dólares.',
            'valor.numeric'  => 'El valor debe ser numérico.',
        ]);

        $valorUSD = (float) $validated['valor'];
        $tipo     = strtolower($validated['tipo'] ?? 'oficial');

        $res = $this->service->convertir($valorUSD, $tipo);

        if (!$res['ok']) {
            return response()->json(['error' => $res['error']], $res['http'] ?? 400);
        }

        return response()->json($res);
    }

    // GET /api/promedio-mensual?tipo=blue&valor=venta&anio=2025&mes=9
    // GET /api/promedio-mensual?tipo=blue&anio=2025&mes=9 (muestra compra y venta)
    public function promedioMensual(Request $request)
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string'], // ideal: Rule::in([...])
            'valor' => ['nullable', 'in:compra,venta'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $tipo = strtolower($validated['tipo']);
        $anio = (int) $validated['anio'];
        $mes = (int) $validated['mes'];

        $base = Cotizacion::query()->tipo($tipo)->periodo($anio, $mes);

        $desde = Carbon::create($anio, $mes, 1)->toDateString();
        $hasta = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        if (isset($validated['valor'])) {
            $campo = $validated['valor']; // 'compra' | 'venta'
            $agregado = (clone $base)->selectRaw(
                "AVG($campo) as avg_val, COUNT($campo) as cnt_val"
            )->first();

            return response()->json([
                'tipo' => $tipo,
                'valor' => $campo,
                'anio' => $anio,
                'mes' => $mes,
                'muestras' => (int) ($agregado->cnt_val ?? 0),
                'promedio' => isset($agregado->avg_val) ? round((float) $agregado->avg_val, 2) : null,
                'rango' => ['desde' => $desde, 'hasta' => $hasta],
            ]);
        }

        $agregados = (clone $base)->selectRaw(
            'AVG(compra) as avg_compra, COUNT(compra) as cnt_compra,
         AVG(venta)  as avg_venta,  COUNT(venta)  as cnt_venta'
        )->first();

        return response()->json([
            'tipo' => $tipo,
            'anio' => $anio,
            'mes' => $mes,
            'rango' => ['desde' => $desde, 'hasta' => $hasta],
            'compra' => [
                'muestras' => (int) ($agregados->cnt_compra ?? 0),
                'promedio' => isset($agregados->avg_compra) ? round((float) $agregados->avg_compra, 2) : null,
            ],
            'venta' => [
                'muestras' => (int) ($agregados->cnt_venta ?? 0),
                'promedio' => isset($agregados->avg_venta) ? round((float) $agregados->avg_venta, 2) : null,
            ],
        ]);
    }


    // === NUEVO: Historial por mes y tipo (lista de puntos del mes) ===
    // GET /api/historial?tipo=blue&anio=2025&mes=9
    public function historialMensual(Request $request)
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $tipo = strtolower($validated['tipo']);
        $anio = (int) $validated['anio'];
        $mes = (int) $validated['mes'];

        $items = Cotizacion::query()
            ->tipo($tipo)
            ->periodo($anio, $mes)
            ->orderBy('momento')
            ->get(['momento', 'compra', 'venta'])
            ->map(fn($r) => [
                'momento' => $r->momento->toIso8601String(),
                'compra' => is_null($r->compra) ? null : (float) $r->compra,
                'venta' => is_null($r->venta) ? null : (float) $r->venta,
            ]);

        return response()->json([
            'tipo' => $tipo,
            'anio' => $anio,
            'mes' => $mes,
            'total' => $items->count(),
            'datos' => $items,
        ]);
    }
}
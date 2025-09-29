<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        $ahora = Carbon::now();
        $inicio = Carbon::create(2024, 1, 1, 12, 0, 0); // 1 Ene 2024 12:00

        // Días del mes que vamos a registrar (3 puntos por mes)
        $puntos = [1, 10, 15, 20, 25];

        // Recorremos mes a mes hasta el mes actual
        for ($fecha = $inicio->copy(); $fecha->lessThanOrEqualTo($ahora); $fecha->addMonth()) {
            foreach (['oficial', 'blue'] as $tipo) {
                foreach ($puntos as $dia) {
                    // Momento (asegurate que el día exista en meses cortos)
                    $momento = $fecha->copy()->day(min($dia, $fecha->daysInMonth))->setTime(12, 0, 0);

                    // === Valores simulados ===
                    // Base evolutiva por mes (para que "crezca" algo en el tiempo)
                    $mesIndex = ($momento->year - 2024) * 12 + ($momento->month - 1);

                    if ($tipo === 'oficial') {
                        // Oficial más estable, variación suave
                        $baseCompra = 820 + ($mesIndex * 8);   // tendencia
                        $ruido      = rand(-10, 10);           // ruido pequeño
                    } else { // blue
                        // Blue más volátil
                        $baseCompra = 900 + ($mesIndex * 15);  // tendencia más fuerte
                        $ruido      = rand(-25, 25);           // ruido mayor
                    }

                    $compra = max(1, $baseCompra + $ruido);
                    // venta ligeramente mayor que compra
                    $venta  = $compra + rand(8, 25);

                    $rows[] = [
                        'tipo'       => $tipo,
                        'momento'    => $momento->toDateTimeString(),
                        'fecha'      => $momento->toDateString(),
                        'compra'     => round($compra, 2),
                        'venta'      => round($venta, 2),
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
            }
        }

        // Insertar evitando duplicados según (tipo, momento)
        // Requiere MySQL 8+ / MariaDB 10.3+ con soporte a ON DUPLICATE KEY
        DB::table('cotizaciones')->upsert(
            $rows,
            ['tipo', 'momento'],            // columnas únicas
            ['fecha', 'compra', 'venta', 'updated_at'] // columnas a actualizar si existe
        );
    }
}
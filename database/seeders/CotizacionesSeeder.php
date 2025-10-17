<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CotizacionesSeeder extends Seeder
{
    public function run(): void
    {
        $rows   = [];
        $ahora  = Carbon::now();
        $inicio = Carbon::create(2024, 1, 1, 12, 0, 0); // 1 Ene 2024 12:00

        // 5 puntos por mes
        $puntos = [1, 10, 15, 20, 25];

        // Tipos a simular
        $tipos = ['oficial', 'blue', 'bolsa', 'ccl', 'tarjeta', 'mayorista', 'cripto'];

        // Recorremos mes a mes hasta el mes actual
        for ($fecha = $inicio->copy(); $fecha->lessThanOrEqualTo($ahora); $fecha->addMonth()) {

            foreach ($puntos as $dia) {
                $momentoBase = $fecha->copy()->day(min($dia, $fecha->daysInMonth))->setTime(12, 0, 0);

                // Índice temporal (0 para 01/2024, 1 para 02/2024, …)
                $mesIndex = ($momentoBase->year - 2024) * 12 + ($momentoBase->month - 1);

                // Curva base "oficial": leve tendencia creciente
                $baseOficial = 800 + ($mesIndex * 8);

                foreach ($tipos as $tipo) {
                    // Escalas/volatilidades por tipo (compra)
                    switch ($tipo) {
                        case 'oficial':
                            $media  = $baseOficial * 1.00;
                            $ruido  = mt_rand(-10, 10);                 // ±10
                            $vol    = 0.00;                              // sin multiplicador adicional
                            break;

                        case 'mayorista':
                            $media  = ($baseOficial - 20);               // un poco por debajo del oficial
                            $ruido  = mt_rand(-12, 12);
                            $vol    = 0.00;
                            break;

                        case 'tarjeta': // si usabas 'turista', cámbialo aquí y en $tipos
                            $media  = $baseOficial * 1.60;               // impuestos sobre oficial
                            $ruido  = mt_rand(-25, 25);
                            $vol    = 0.02;
                            break;

                        case 'bolsa':   // MEP aproximado
                            $media  = $baseOficial * 1.38;
                            $ruido  = mt_rand(-35, 35);
                            $vol    = 0.03;
                            break;

                        case 'ccl':     // Contado con liquidación
                            $media  = $baseOficial * 1.45;
                            $ruido  = mt_rand(-40, 40);
                            $vol    = 0.035;
                            break;

                        case 'blue':
                            $media  = $baseOficial * 1.40;
                            $ruido  = mt_rand(-45, 45);
                            $vol    = 0.04;
                            break;

                        case 'cripto':
                            $media  = $baseOficial * 1.42;
                            $ruido  = mt_rand(-60, 60);                  // más volátil
                            $vol    = 0.05;
                            break;

                        default:
                            $media  = $baseOficial;
                            $ruido  = 0;
                            $vol    = 0.0;
                    }

                    // Compra simulada = media + ruido + pequeña variación proporcional
                    $compra = max(1, $media + $ruido + ($media * $vol * (mt_rand(-100, 100) / 100.0)));

                    // Spread relativo para venta (entre 1.0% y 2.2%)
                    $spreadPct = mt_rand(10, 22) / 1000; // 0.010–0.022
                    $venta     = $compra * (1 + $spreadPct);

                    $rows[] = [
                        'tipo'       => $tipo,
                        'momento'    => $momentoBase->toDateTimeString(),
                        'fecha'      => $momentoBase->toDateString(),
                        'compra'     => round($compra, 2),
                        'venta'      => round($venta, 2),
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
            }
        }

        // Upsert por (tipo, momento)
        // Asegúrate de tener índice/unique en (tipo, momento)
        DB::table('cotizaciones')->upsert(
            $rows,
            ['tipo', 'momento'],                        // columnas únicas
            ['fecha', 'compra', 'venta', 'updated_at'] // columnas a actualizar si existe
        );
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);                 // blue, oficial, etc.
            $table->dateTime('momento');               // cuándo se registró
            $table->date('fecha');                     // útil para agrupaciones diarias
            $table->decimal('compra', 12, 2)->nullable();
            $table->decimal('venta', 12, 2)->nullable();
            $table->timestamps();

            // Índices para consultas por tipo y mes
            $table->index(['tipo', 'fecha']);         // filtra rápido por tipo + mes (usando BETWEEN)
            $table->index('momento');                 // orden cronológico

            // Evita duplicar el mismo ping en el mismo segundo para un tipo
            $table->unique(['tipo', 'momento']);
        });

        // columnas generadas para acelerar agrupaciones mensuales
        DB::statement("ALTER TABLE cotizaciones
            ADD COLUMN anio SMALLINT GENERATED ALWAYS AS (YEAR(momento)) STORED,
            ADD COLUMN mes TINYINT GENERATED ALWAYS AS (MONTH(momento)) STORED;");
        DB::statement("CREATE INDEX cotizaciones_tipo_anio_mes_idx ON cotizaciones (tipo, anio, mes);");
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
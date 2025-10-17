<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $res = DB::select("SHOW INDEX FROM `{$table}` WHERE `Key_name` = ?", [$index]);
        return !empty($res);
    }

    public function up(): void
    {
        // unique(tipo, momento)
        if (!$this->indexExists('cotizaciones', 'cotizaciones_tipo_momento_unique')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->unique(['tipo', 'momento'], 'cotizaciones_tipo_momento_unique');
            });
        }

        // index(tipo, fecha)
        if (!$this->indexExists('cotizaciones', 'cotizaciones_tipo_fecha_idx')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->index(['tipo', 'fecha'], 'cotizaciones_tipo_fecha_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('cotizaciones', 'cotizaciones_tipo_momento_unique')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dropUnique('cotizaciones_tipo_momento_unique');
            });
        }

        if ($this->indexExists('cotizaciones', 'cotizaciones_tipo_fecha_idx')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dropIndex('cotizaciones_tipo_fecha_idx');
            });
        }
    }
};
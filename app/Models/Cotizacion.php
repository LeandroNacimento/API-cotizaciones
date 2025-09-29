<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    
    protected $fillable = [
        'tipo', 'momento', 'fecha', 'compra', 'venta'
    ];

    protected $casts = [
        'momento' => 'datetime',
        'fecha'   => 'date',
        'compra'  => 'decimal:2',
        'venta'   => 'decimal:2',
    ];

    // Scopes útiles
    public function scopeTipo($q, string $tipo)    { return $q->where('tipo', $tipo); }
    public function scopePeriodo($q, int $anio, int $mes)
    {
        return $q->whereYear('momento', $anio)->whereMonth('momento', $mes);
    }
}

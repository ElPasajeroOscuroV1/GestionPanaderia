<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DetalleVenta;

class Venta extends Model
{
    protected $fillable = [
        'user_id',
        'total'
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
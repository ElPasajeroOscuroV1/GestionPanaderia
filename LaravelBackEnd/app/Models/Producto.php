<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Receta;
use App\Models\DetalleVenta;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'receta_id'
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
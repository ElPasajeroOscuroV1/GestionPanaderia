<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Receta;

class Ingrediente extends Model
{
    use HasFactory;

    public const UNIDADES_MEDIDA = [
        'unidad',
        'gramo',
        'kilo',
        'libra',
        'mililitro',
        'litro',
        'docena',
        'paquete',
    ];

    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'stock_libras',
        'stock_minimo',
    ];

    protected $casts = [
        'stock_libras' => 'float',
        'stock_minimo' => 'float',
    ];

    //
    public function recetas()
    {
        return $this->belongsToMany(Receta::class)
                    ->withPivot('cantidad_libras')
                    ->withTimestamps();
    }
}

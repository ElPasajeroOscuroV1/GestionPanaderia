<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Ingrediente;
use App\Models\Producto;

class Receta extends Model
{
    /*
    public function ingredientes()
    {
        return $this->belongsToMany(Ingrediente::class)
                    ->withPivot('cantidad_libras')
                    ->withTimestamps();
    }
    */

    protected $table = 'recetas';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public function ingredientes()
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'receta_ingrediente',
            'receta_id',
            'ingrediente_id'
        )->withPivot('cantidad_libras');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

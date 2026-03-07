<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'receta_ingrediente'
        )->withPivot('cantidad_libras');
    }
}

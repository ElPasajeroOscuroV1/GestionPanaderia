<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Receta;

class Ingrediente extends Model
{
    use HasFactory;

    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'stock_libras'
    ];

    //
    public function recetas()
    {
        return $this->belongsToMany(Receta::class)
                    ->withPivot('cantidad_libras')
                    ->withTimestamps();
    }
}

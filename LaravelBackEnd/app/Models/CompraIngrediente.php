<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraIngrediente extends Model
{
    use HasFactory;

    protected $table = 'compra_ingredientes';

    protected $fillable = [
        'ingrediente_id',
        'cantidad',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'fecha' => 'date',
    ];

    public function ingrediente()
    {
        return $this->belongsTo(Ingrediente::class);
    }
}

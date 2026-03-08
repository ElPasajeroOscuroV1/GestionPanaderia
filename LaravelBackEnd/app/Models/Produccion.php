<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Receta;

class Produccion extends Model
{
    //
    protected $table = 'producciones';

    protected $fillable = [
        'receta_id',
        'cantidad',
        'fecha'
    ];
    
    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }
}

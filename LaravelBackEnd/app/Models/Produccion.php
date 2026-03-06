<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    //
    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }
}

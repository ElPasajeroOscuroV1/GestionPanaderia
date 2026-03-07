<?php

namespace App\Observers;

use App\Models\Produccion;

class ProduccionObserver
{
    /**
     * Handle the Produccion "created" event.
     */
    public function created(Produccion $produccion)
    {
        $receta = $produccion->receta;

        foreach ($receta->ingredientes as $ingrediente) {

            $cantidadNecesaria = $ingrediente->pivot->cantidad_libras * $produccion->cantidad;

            $ingrediente->stock_libras -= $cantidadNecesaria;
            $ingrediente->save();
        }
    }

    /**
     * Handle the Produccion "updated" event.
     */
    public function updated(Produccion $produccion): void
    {
        //
    }

    /**
     * Handle the Produccion "deleted" event.
     */
    public function deleted(Produccion $produccion): void
    {
        //
    }

    /**
     * Handle the Produccion "restored" event.
     */
    public function restored(Produccion $produccion): void
    {
        //
    }

    /**
     * Handle the Produccion "force deleted" event.
     */
    public function forceDeleted(Produccion $produccion): void
    {
        //
    }
}

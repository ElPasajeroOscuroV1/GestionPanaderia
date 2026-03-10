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
        // El descuento de ingredientes se gestiona en ProduccionController@store
        // dentro de una transaccion para evitar duplicidad de movimientos.
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

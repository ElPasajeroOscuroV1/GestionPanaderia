import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { ApiService, CompraIngrediente, Ingrediente } from '../../services/api.service';

@Component({
  selector: 'app-compras',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './compras.html',
  styleUrl: './compras.css',
})
export class ComprasComponent implements OnInit {
  ingredientes: Ingrediente[] = [];
  compras: CompraIngrediente[] = [];

  ingredienteId: number | null = null;
  cantidad = 0;
  observacion = '';

  loading = false;
  saving = false;
  message = '';
  error = '';

  constructor(private readonly api: ApiService) {}

  ngOnInit(): void {
    this.loadData();
  }

  registrarCompra(): void {
    this.message = '';
    this.error = '';

    if (!this.ingredienteId) {
      this.error = 'Selecciona un ingrediente.';
      return;
    }

    if (this.cantidad <= 0) {
      this.error = 'La cantidad debe ser mayor a cero.';
      return;
    }

    this.saving = true;
    this.api
      .registrarCompraIngrediente({
        ingrediente_id: this.ingredienteId,
        cantidad: Number(this.cantidad),
        observacion: this.observacion.trim() || undefined,
      })
      .pipe(finalize(() => (this.saving = false)))
      .subscribe({
        next: () => {
          this.message = 'Compra registrada correctamente.';
          this.cantidad = 0;
          this.observacion = '';
          this.loadData();
        },
        error: (err) => {
          this.error = this.extractError(err, 'No se pudo registrar compra.');
        },
      });
  }

  getIngredienteLabel(ingrediente: Ingrediente): string {
    return `${ingrediente.nombre} (${ingrediente.unidad_medida})`;
  }

  private loadData(): void {
    this.loading = true;

    this.api
      .getIngredientes()
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (ingredientes) => {
          this.ingredientes = ingredientes;
          if (!this.ingredienteId && ingredientes.length > 0) {
            this.ingredienteId = ingredientes[0].id;
          }
          this.loadCompras();
        },
        error: () => {
          this.error = 'No se pudo cargar ingredientes.';
        },
      });
  }

  private loadCompras(): void {
    this.api.getComprasIngredientes().subscribe({
      next: (compras) => {
        this.compras = compras;
      },
      error: () => {
        this.error = 'No se pudo cargar historial de compras.';
      },
    });
  }

  private extractError(error: any, fallback: string): string {
    if (error?.error?.message) {
      return error.error.message;
    }

    if (error?.error?.error) {
      return error.error.error;
    }

    return fallback;
  }
}

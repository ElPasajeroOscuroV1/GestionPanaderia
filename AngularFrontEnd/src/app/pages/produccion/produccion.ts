import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { ApiService, Produccion, ProduccionPreview, Receta } from '../../services/api.service';

@Component({
  selector: 'app-produccion',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './produccion.html',
  styleUrl: './produccion.css',
})
export class ProduccionComponent implements OnInit {
  recetas: Receta[] = [];
  producciones: Produccion[] = [];

  receta_id: number | null = null;
  cantidad = 1;

  preview: ProduccionPreview | null = null;

  loading = false;
  previewLoading = false;
  saving = false;

  message = '';
  error = '';

  constructor(private readonly api: ApiService) {}

  ngOnInit(): void {
    this.loadInitialData();
  }

  onRecetaChange(): void {
    this.message = '';
    this.error = '';

    if (!this.receta_id) {
      this.preview = null;
      return;
    }

    this.loadPreview();
  }

  onCantidadChange(): void {
    if (this.cantidad < 1) {
      this.cantidad = 1;
    }

    if (!this.receta_id) {
      return;
    }

    this.loadPreview();
  }

  producir(): void {
    this.message = '';
    this.error = '';

    if (!this.receta_id) {
      this.error = 'Selecciona una receta.';
      return;
    }

    if (this.cantidad < 1) {
      this.error = 'La cantidad debe ser mayor a cero.';
      return;
    }

    if (!this.preview?.can_produce) {
      this.error = 'No hay stock suficiente para producir esa cantidad.';
      return;
    }

    this.saving = true;
    this.api
      .registrarProduccion({
        receta_id: this.receta_id,
        cantidad: this.cantidad,
      })
      .pipe(finalize(() => (this.saving = false)))
      .subscribe({
        next: () => {
          this.message = 'Produccion registrada correctamente.';
          this.loadPreview();
          this.loadProducciones();
        },
        error: (err) => {
          this.error = this.extractError(err, 'No se pudo registrar produccion.');
        },
      });
  }

  private loadInitialData(): void {
    this.loading = true;

    this.api
      .getRecetas()
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (recetas) => {
          this.recetas = recetas;
          if (recetas.length > 0 && !this.receta_id) {
            this.receta_id = recetas[0].id;
            this.loadPreview();
          }
        },
        error: () => {
          this.error = 'No se pudieron cargar las recetas.';
        },
      });

    this.loadProducciones();
  }

  private loadProducciones(): void {
    this.api.getProducciones().subscribe({
      next: (producciones) => {
        this.producciones = producciones;
      },
      error: () => {
        this.error = 'No se pudo cargar historial de produccion.';
      },
    });
  }

  private loadPreview(): void {
    if (!this.receta_id) {
      return;
    }

    this.previewLoading = true;
    this.api
      .previewProduccion(this.receta_id, this.cantidad)
      .pipe(finalize(() => (this.previewLoading = false)))
      .subscribe({
        next: (preview) => {
          this.preview = preview;
        },
        error: (err) => {
          this.preview = {
            receta_id: this.receta_id as number,
            receta: 'Receta',
            cantidad: this.cantidad,
            can_produce: false,
            consumo: err?.error?.consumo ?? [],
            message: err?.error?.message ?? err?.error?.error ?? 'No se pudo calcular consumo.',
          };
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

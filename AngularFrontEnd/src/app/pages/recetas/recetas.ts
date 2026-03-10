import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { ApiService, Ingrediente, Receta, RecetaPayload } from '../../services/api.service';

interface RecetaIngredienteForm {
  ingrediente_id: number | null;
  cantidad: number | null;
}

@Component({
  selector: 'app-recetas',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './recetas.html',
  styleUrl: './recetas.css',
})
export class RecetasComponent implements OnInit {
  ingredientesDisponibles: Ingrediente[] = [];
  recetas: Receta[] = [];

  nombre = '';
  descripcion = '';
  ingredientesForm: RecetaIngredienteForm[] = [this.createIngredienteForm()];

  loading = false;
  saving = false;
  message = '';
  error = '';

  constructor(private readonly api: ApiService) {}

  ngOnInit(): void {
    this.loadData();
  }

  addIngredienteForm(): void {
    this.ingredientesForm.push(this.createIngredienteForm());
  }

  removeIngredienteForm(index: number): void {
    if (this.ingredientesForm.length === 1) {
      return;
    }

    this.ingredientesForm.splice(index, 1);
  }

  createReceta(): void {
    this.message = '';
    this.error = '';

    const payload = this.buildPayload();
    if (!payload) {
      return;
    }

    this.saving = true;
    this.api
      .createReceta(payload)
      .pipe(finalize(() => (this.saving = false)))
      .subscribe({
        next: () => {
          this.message = 'Receta creada correctamente.';
          this.resetForm();
          this.loadData();
        },
        error: (err) => {
          this.error = this.extractError(err, 'No se pudo crear la receta.');
        },
      });
  }

  deleteReceta(receta: Receta): void {
    this.message = '';
    this.error = '';

    const confirmDelete = confirm(`Eliminar receta ${receta.nombre}?`);
    if (!confirmDelete) {
      return;
    }

    this.api.deleteReceta(receta.id).subscribe({
      next: () => {
        this.message = 'Receta eliminada.';
        this.loadData();
      },
      error: (err) => {
        this.error = this.extractError(err, 'No se pudo eliminar la receta.');
      },
    });
  }

  private loadData(): void {
    this.loading = true;

    this.api
      .getIngredientes()
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (ingredientes) => {
          this.ingredientesDisponibles = ingredientes;
          this.loadRecetas();
        },
        error: () => {
          this.error = 'No se pudo cargar ingredientes.';
        },
      });
  }

  private loadRecetas(): void {
    this.api.getRecetas().subscribe({
      next: (recetas) => {
        this.recetas = recetas;
      },
      error: () => {
        this.error = 'No se pudo cargar recetas.';
      },
    });
  }

  private buildPayload(): RecetaPayload | null {
    const nombre = this.nombre.trim();
    if (!nombre) {
      this.error = 'El nombre de la receta es obligatorio.';
      return null;
    }

    const ingredientes = this.ingredientesForm
      .filter((item) => item.ingrediente_id !== null && (item.cantidad ?? 0) > 0)
      .map((item) => ({
        ingrediente_id: item.ingrediente_id as number,
        cantidad: Number(item.cantidad),
      }));

    if (ingredientes.length === 0) {
      this.error = 'Agrega al menos un ingrediente con cantidad valida.';
      return null;
    }

    const duplicated = new Set<number>();
    for (const item of ingredientes) {
      if (duplicated.has(item.ingrediente_id)) {
        this.error = 'No repitas ingredientes en la misma receta.';
        return null;
      }
      duplicated.add(item.ingrediente_id);
    }

    return {
      nombre,
      descripcion: this.descripcion.trim() || undefined,
      ingredientes,
    };
  }

  private resetForm(): void {
    this.nombre = '';
    this.descripcion = '';
    this.ingredientesForm = [this.createIngredienteForm()];
  }

  private createIngredienteForm(): RecetaIngredienteForm {
    return {
      ingrediente_id: null,
      cantidad: null,
    };
  }

  getIngredienteById(ingredienteId: number | null): Ingrediente | undefined {
    if (!ingredienteId) {
      return undefined;
    }

    return this.ingredientesDisponibles.find((item) => item.id === ingredienteId);
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

import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import { ApiService, Ingrediente, UnidadMedida } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';

interface EditableIngrediente {
  nombre: string;
  unidad_medida: UnidadMedida;
  stock_libras: number;
  stock_minimo: number;
}

@Component({
  selector: 'app-inventario',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './inventario.html',
  styleUrl: './inventario.css',
})
export class InventarioComponent implements OnInit {
  readonly unidadesMedida: UnidadMedida[] = [
    'unidad',
    'gramo',
    'kilo',
    'libra',
    'mililitro',
    'litro',
    'docena',
    'paquete',
  ];

  ingredientes: Ingrediente[] = [];
  search = '';

  nuevoNombre = '';
  nuevaUnidadMedida: UnidadMedida = 'unidad';
  nuevoStock = 0;
  nuevoStockMinimo = 10;

  editable: Record<number, EditableIngrediente> = {};
  editingRows: Record<number, boolean> = {};

  loading = false;
  saving = false;
  message = '';
  error = '';

  constructor(
    private readonly api: ApiService,
    private readonly authService: AuthService
  ) {}

  get canManageInventario(): boolean {
    return this.authService.hasRole('admin');
  }

  ngOnInit(): void {
    this.loadIngredientes();
  }

  get ingredientesFiltrados(): Ingrediente[] {
    const query = this.search.trim().toLowerCase();
    if (!query) {
      return this.ingredientes;
    }

    return this.ingredientes.filter((item) => item.nombre.toLowerCase().includes(query));
  }

  get ingredientesAgotados(): Ingrediente[] {
    return this.ingredientes.filter((item) => item.stock_libras <= 0);
  }

  get ingredientesBajoStock(): Ingrediente[] {
    return this.ingredientes.filter((item) => item.stock_libras > 0 && item.stock_libras <= item.stock_minimo);
  }

  get nombresIngredientesAgotados(): string {
    return this.ingredientesAgotados.map((item) => item.nombre).join(', ');
  }

  get nombresIngredientesBajoStock(): string {
    return this.ingredientesBajoStock.map((item) => item.nombre).join(', ');
  }

  createIngrediente(): void {
    this.message = '';
    this.error = '';

    if (!this.canManageInventario) {
      this.error = 'No tienes permisos para crear ingredientes.';
      return;
    }

    const nombre = this.nuevoNombre.trim();
    if (!nombre) {
      this.error = 'Ingresa un nombre de ingrediente.';
      return;
    }

    if (this.nuevoStock <= 0) {
      this.error = 'El stock inicial debe ser mayor a cero.';
      return;
    }

    if (this.nuevoStockMinimo < 0) {
      this.error = 'El stock minimo no puede ser negativo.';
      return;
    }

    this.saving = true;
    this.api
      .createIngrediente({
        nombre,
        unidad_medida: this.nuevaUnidadMedida,
        stock_libras: Number(this.nuevoStock),
        stock_minimo: Number(this.nuevoStockMinimo),
      })
      .pipe(finalize(() => (this.saving = false)))
      .subscribe({
        next: () => {
          this.message = 'Ingrediente creado.';
          this.nuevoNombre = '';
          this.nuevaUnidadMedida = 'unidad';
          this.nuevoStock = 0;
          this.nuevoStockMinimo = 10;
          this.loadIngredientes();
        },
        error: (err) => {
          this.error = this.extractError(err, 'No se pudo crear el ingrediente.');
        },
      });
  }

  updateIngrediente(ingredienteId: number): void {
    this.message = '';
    this.error = '';

    if (!this.canManageInventario) {
      this.error = 'No tienes permisos para editar ingredientes.';
      return;
    }

    const item = this.editable[ingredienteId];
    if (!item) {
      return;
    }

    const nombre = item.nombre.trim();
    if (!nombre) {
      this.error = 'El nombre no puede estar vacio.';
      return;
    }

    if (item.stock_libras <= 0) {
      this.error = 'El stock debe ser mayor a cero.';
      return;
    }

    if (item.stock_minimo < 0) {
      this.error = 'El stock minimo no puede ser negativo.';
      return;
    }

    this.api
      .updateIngrediente(ingredienteId, {
        nombre,
        unidad_medida: item.unidad_medida,
        stock_libras: Number(item.stock_libras),
        stock_minimo: Number(item.stock_minimo),
      })
      .subscribe({
        next: () => {
          this.message = 'Ingrediente actualizado.';
          delete this.editingRows[ingredienteId];
          this.loadIngredientes();
        },
        error: (err) => {
          this.error = this.extractError(err, 'No se pudo actualizar el ingrediente.');
        },
      });
  }

  startEdit(ingrediente: Ingrediente): void {
    if (!this.canManageInventario) {
      return;
    }

    this.message = '';
    this.error = '';
    this.editable[ingrediente.id] = this.toEditable(ingrediente);
    this.editingRows[ingrediente.id] = true;
  }

  cancelEdit(ingrediente: Ingrediente): void {
    this.message = '';
    this.error = '';
    this.editable[ingrediente.id] = this.toEditable(ingrediente);
    delete this.editingRows[ingrediente.id];
  }

  isEditing(ingredienteId: number): boolean {
    return !!this.editingRows[ingredienteId];
  }

  deleteIngrediente(ingrediente: Ingrediente): void {
    this.message = '';
    this.error = '';

    if (!this.canManageInventario) {
      this.error = 'No tienes permisos para eliminar ingredientes.';
      return;
    }

    const confirmDelete = confirm(`Eliminar ingrediente ${ingrediente.nombre}?`);
    if (!confirmDelete) {
      return;
    }

    this.api.deleteIngrediente(ingrediente.id).subscribe({
      next: () => {
        this.message = 'Ingrediente eliminado.';
        this.loadIngredientes();
      },
      error: (err) => {
        this.error = this.extractError(err, 'No se pudo eliminar el ingrediente.');
      },
    });
  }

  private loadIngredientes(): void {
    this.loading = true;
    this.error = '';

    this.api
      .getIngredientes()
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (ingredientes) => {
          this.ingredientes = ingredientes;
          this.editable = {};
          this.editingRows = {};
          for (const ingrediente of ingredientes) {
            this.editable[ingrediente.id] = this.toEditable(ingrediente);
          }
        },
        error: () => {
          this.error = 'No se pudo cargar el inventario.';
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

  private toEditable(ingrediente: Ingrediente): EditableIngrediente {
    return {
      nombre: ingrediente.nombre,
      unidad_medida: ingrediente.unidad_medida,
      stock_libras: ingrediente.stock_libras,
      stock_minimo: ingrediente.stock_minimo,
    };
  }

  unidadLabel(unidad: UnidadMedida): string {
    const labels: Record<UnidadMedida, string> = {
      unidad: 'Unidad',
      gramo: 'Gramo',
      kilo: 'Kilo',
      libra: 'Libra',
      mililitro: 'Mililitro',
      litro: 'Litro',
      docena: 'Docena',
      paquete: 'Paquete',
    };

    return labels[unidad];
  }

  isAgotado(ingredienteId: number): boolean {
    return (this.editable[ingredienteId]?.stock_libras ?? 0) <= 0;
  }

  isBajoStock(ingredienteId: number): boolean {
    const item = this.editable[ingredienteId];
    if (!item) {
      return false;
    }

    return item.stock_libras > 0 && item.stock_libras <= item.stock_minimo;
  }

  isStockNormal(ingredienteId: number): boolean {
    const item = this.editable[ingredienteId];
    if (!item) {
      return false;
    }

    return item.stock_libras > item.stock_minimo;
  }

  equivalenteLibras(ingredienteId: number): string {
    const item = this.editable[ingredienteId];
    if (!item) {
      return '-';
    }

    const valor = this.convertirALibras(item.stock_libras, item.unidad_medida);
    if (valor === null) {
      return '-';
    }

    return `${valor.toFixed(2)} lb`;
  }

  private convertirALibras(cantidad: number, unidad: UnidadMedida): number | null {
    switch (unidad) {
      case 'libra':
        return cantidad;
      case 'kilo':
        return cantidad * 2.20462;
      case 'gramo':
        return cantidad / 453.592;
      default:
        return null;
    }
  }
}

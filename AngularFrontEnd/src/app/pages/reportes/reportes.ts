import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { finalize } from 'rxjs';
import {
  ApiService,
  ReporteGestionResponse,
  ReporteInventarioItem,
  ReporteProduccionIngredienteResumenItem,
  ReporteProduccionItem,
  ReporteRecetaItem,
  ReporteTipo,
} from '../../services/api.service';

interface ReporteTipoOption {
  value: ReporteTipo;
  label: string;
}

interface ResumenEntry {
  key: string;
  label: string;
  value: number;
}

@Component({
  selector: 'app-reportes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reportes.html',
  styleUrl: './reportes.css',
})
export class ReportesComponent implements OnInit {
  readonly tiposReporte: ReporteTipoOption[] = [
    { value: 'inventario', label: 'Inventario' },
    { value: 'recetas', label: 'Recetas' },
    { value: 'produccion', label: 'Produccion' },
  ];

  tipo: ReporteTipo = 'inventario';
  fechaInicio = '';
  fechaFin = '';

  reporte: ReporteGestionResponse | null = null;
  loading = false;
  error = '';

  constructor(private readonly api: ApiService) {
    const hoy = new Date();
    this.fechaInicio = this.formatDateInput(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
    this.fechaFin = this.formatDateInput(hoy);
  }

  ngOnInit(): void {
    this.generarReporte();
  }

  get isProduccion(): boolean {
    return this.tipo === 'produccion';
  }

  get inventarioItems(): ReporteInventarioItem[] {
    if (this.reporte?.tipo !== 'inventario') {
      return [];
    }

    return this.reporte.items as ReporteInventarioItem[];
  }

  get recetaItems(): ReporteRecetaItem[] {
    if (this.reporte?.tipo !== 'recetas') {
      return [];
    }

    return this.reporte.items as ReporteRecetaItem[];
  }

  get produccionItems(): ReporteProduccionItem[] {
    if (this.reporte?.tipo !== 'produccion') {
      return [];
    }

    return this.reporte.items as ReporteProduccionItem[];
  }

  get produccionResumenIngredientes(): ReporteProduccionIngredienteResumenItem[] {
    if (this.reporte?.tipo !== 'produccion') {
      return [];
    }

    return this.reporte.resumen_ingredientes ?? [];
  }

  get resumenEntries(): ResumenEntry[] {
    if (!this.reporte) {
      return [];
    }

    return Object.entries(this.reporte.resumen).map(([key, value]) => ({
      key,
      value,
      label: this.resumenLabel(key),
    }));
  }

  onTipoChange(): void {
    this.reporte = null;
    this.error = '';
  }

  generarReporte(): void {
    this.error = '';

    if (this.tipo === 'produccion') {
      if (!this.fechaInicio || !this.fechaFin) {
        this.error = 'Debes seleccionar fecha inicio y fecha fin para el reporte de produccion.';
        return;
      }

      if (this.fechaFin < this.fechaInicio) {
        this.error = 'La fecha fin no puede ser menor a la fecha inicio.';
        return;
      }
    }

    this.loading = true;
    this.api
      .getReporteGestion({
        tipo: this.tipo,
        fecha_inicio: this.tipo === 'produccion' ? this.fechaInicio : undefined,
        fecha_fin: this.tipo === 'produccion' ? this.fechaFin : undefined,
      })
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: (reporte) => {
          this.reporte = reporte;
        },
        error: (err) => {
          this.reporte = null;
          this.error = this.extractError(err, 'No se pudo generar el reporte.');
        },
      });
  }

  exportarCsv(): void {
    this.error = '';

    if (!this.reporte) {
      this.error = 'Primero genera un reporte para exportarlo.';
      return;
    }

    const csv = this.buildCsv(this.reporte);
    if (!csv) {
      this.error = 'No hay datos para exportar.';
      return;
    }

    this.downloadCsv(csv, `reporte-${this.reporte.tipo}-${this.fileTimestamp()}.csv`);
  }

  estadoLabel(estado: 'agotado' | 'bajo_stock' | 'normal'): string {
    const labels: Record<'agotado' | 'bajo_stock' | 'normal', string> = {
      agotado: 'Agotado',
      bajo_stock: 'Bajo stock',
      normal: 'Normal',
    };

    return labels[estado];
  }

  private buildCsv(reporte: ReporteGestionResponse): string | null {
    switch (reporte.tipo) {
      case 'inventario':
        return this.buildInventarioCsv(this.inventarioItems);
      case 'recetas':
        return this.buildRecetasCsv(this.recetaItems);
      case 'produccion':
        return this.buildProduccionCsv(this.produccionItems, this.produccionResumenIngredientes);
      default:
        return null;
    }
  }

  private buildInventarioCsv(items: ReporteInventarioItem[]): string | null {
    if (items.length === 0) {
      return null;
    }

    const rows: string[][] = [['Ingrediente', 'Stock actual (lb)', 'Stock minimo (lb)', 'Estado']];
    for (const item of items) {
      rows.push([
        item.nombre_ingrediente,
        item.stock_actual_libras.toFixed(2),
        item.stock_minimo_libras.toFixed(2),
        this.estadoLabel(item.estado),
      ]);
    }

    return this.toCsv(rows);
  }

  private buildRecetasCsv(items: ReporteRecetaItem[]): string | null {
    if (items.length === 0) {
      return null;
    }

    const rows: string[][] = [['Receta', 'Ingrediente', 'Cantidad utilizada (lb por lote)']];
    for (const item of items) {
      rows.push([item.nombre_receta, item.nombre_ingrediente, item.cantidad_utilizada_libras.toFixed(2)]);
    }

    return this.toCsv(rows);
  }

  private buildProduccionCsv(
    items: ReporteProduccionItem[],
    resumenIngredientes: ReporteProduccionIngredienteResumenItem[]
  ): string | null {
    if (items.length === 0) {
      return null;
    }

    const rows: string[][] = [
      ['Fecha', 'Receta', 'Cantidad producida', 'Ingrediente', 'Cantidad utilizada (lb)'],
      ...items.map((item) => [
        item.fecha,
        item.nombre_receta ?? '-',
        item.cantidad_producida.toString(),
        item.nombre_ingrediente,
        item.cantidad_utilizada_libras.toFixed(2),
      ]),
    ];

    if (resumenIngredientes.length > 0) {
      rows.push([]);
      rows.push(['Resumen por ingrediente']);
      rows.push(['Ingrediente', 'Cantidad total utilizada (lb)']);
      for (const item of resumenIngredientes) {
        rows.push([item.nombre_ingrediente, item.cantidad_total_utilizada_libras.toFixed(2)]);
      }
    }

    return this.toCsv(rows);
  }

  private toCsv(rows: string[][]): string {
    return rows
      .map((row) => row.map((cell) => this.escapeCsvCell(cell)).join(','))
      .join('\n');
  }

  private escapeCsvCell(value: string): string {
    const escaped = value.replace(/"/g, '""');

    if (escaped.includes(',') || escaped.includes('"') || escaped.includes('\n')) {
      return `"${escaped}"`;
    }

    return escaped;
  }

  private downloadCsv(csvContent: string, filename: string): void {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = filename;
    anchor.style.display = 'none';

    document.body.appendChild(anchor);
    anchor.click();
    document.body.removeChild(anchor);
    URL.revokeObjectURL(url);
  }

  private resumenLabel(key: string): string {
    const labels: Record<string, string> = {
      total_ingredientes: 'Total ingredientes',
      stock_total_libras: 'Stock total (lb)',
      total_recetas: 'Total recetas',
      total_detalles_ingredientes: 'Total detalles ingredientes',
      total_lotes: 'Total lotes',
      total_unidades_producidas: 'Total unidades producidas',
      consumo_total_libras: 'Consumo total (lb)',
    };

    return labels[key] ?? key;
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

  private formatDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  private fileTimestamp(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const day = now.getDate().toString().padStart(2, '0');
    const hour = now.getHours().toString().padStart(2, '0');
    const minute = now.getMinutes().toString().padStart(2, '0');
    const second = now.getSeconds().toString().padStart(2, '0');

    return `${year}${month}${day}-${hour}${minute}${second}`;
  }
}

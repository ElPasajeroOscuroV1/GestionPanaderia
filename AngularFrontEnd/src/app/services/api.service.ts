import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface Ingrediente {
  id: number;
  nombre: string;
  unidad_medida: UnidadMedida;
  stock_libras: number;
  stock_minimo: number;
  created_at?: string;
  updated_at?: string;
}

export type UnidadMedida =
  | 'unidad'
  | 'gramo'
  | 'kilo'
  | 'libra'
  | 'mililitro'
  | 'litro'
  | 'docena'
  | 'paquete';

export interface RecetaIngrediente {
  id: number;
  nombre: string;
  unidad_medida: UnidadMedida;
  stock_libras: number;
  pivot: {
    cantidad_libras: number;
  };
}

export interface Receta {
  id: number;
  nombre: string;
  descripcion: string | null;
  ingredientes: RecetaIngrediente[];
  created_at?: string;
  updated_at?: string;
}

export interface Produccion {
  id: number;
  receta_id: number;
  cantidad: number;
  fecha: string;
  receta?: {
    id: number;
    nombre: string;
  };
  created_at?: string;
  updated_at?: string;
}

export interface DashboardData {
  ingredientes: number;
  recetas: number;
  produccion_hoy: number;
  productos_en_stock: number;
  ingredientes_bajo_stock_total: number;
  ingredientes_bajo_stock: Ingrediente[];
  producciones_recientes: Produccion[];
}

export interface CompraIngrediente {
  id: number;
  ingrediente_id: number;
  cantidad: number;
  fecha: string;
  observacion: string | null;
  ingrediente?: {
    id: number;
    nombre: string;
    unidad_medida: UnidadMedida;
  };
  created_at?: string;
  updated_at?: string;
}

export interface ProduccionPreviewItem {
  ingrediente_id: number;
  ingrediente: string;
  unidad_medida: UnidadMedida;
  stock_actual: number;
  cantidad_necesaria: number;
  stock_restante: number;
  insuficiente: boolean;
}

export interface ProduccionPreview {
  receta_id: number;
  receta: string;
  cantidad: number;
  can_produce: boolean;
  consumo: ProduccionPreviewItem[];
  message?: string;
}

export interface RecetaPayload {
  nombre: string;
  descripcion?: string;
  ingredientes: Array<{
    ingrediente_id: number;
    cantidad: number;
  }>;
}

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private readonly apiUrl = 'http://localhost:8000/api';

  constructor(private readonly http: HttpClient) {}

  getDashboard(): Observable<DashboardData> {
    return this.http.get<DashboardData>(`${this.apiUrl}/dashboard`);
  }

  getIngredientes(): Observable<Ingrediente[]> {
    return this.http.get<Ingrediente[]>(`${this.apiUrl}/ingredientes`);
  }

  createIngrediente(payload: {
    nombre: string;
    unidad_medida: UnidadMedida;
    stock_libras: number;
    stock_minimo: number;
  }): Observable<Ingrediente> {
    return this.http.post<Ingrediente>(`${this.apiUrl}/ingredientes`, payload);
  }

  updateIngrediente(
    ingredienteId: number,
    payload: { nombre: string; unidad_medida: UnidadMedida; stock_libras: number; stock_minimo: number }
  ): Observable<Ingrediente> {
    return this.http.put<Ingrediente>(`${this.apiUrl}/ingredientes/${ingredienteId}`, payload);
  }

  deleteIngrediente(ingredienteId: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.apiUrl}/ingredientes/${ingredienteId}`);
  }

  getRecetas(): Observable<Receta[]> {
    return this.http.get<Receta[]>(`${this.apiUrl}/recetas`);
  }

  createReceta(payload: RecetaPayload): Observable<{ message: string; receta: Receta }> {
    return this.http.post<{ message: string; receta: Receta }>(`${this.apiUrl}/recetas`, payload);
  }

  updateReceta(recetaId: number, payload: RecetaPayload): Observable<{ message: string; receta: Receta }> {
    return this.http.put<{ message: string; receta: Receta }>(`${this.apiUrl}/recetas/${recetaId}`, payload);
  }

  deleteReceta(recetaId: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.apiUrl}/recetas/${recetaId}`);
  }

  getProducciones(): Observable<Produccion[]> {
    return this.http.get<Produccion[]>(`${this.apiUrl}/producciones`);
  }

  previewProduccion(recetaId: number, cantidad: number): Observable<ProduccionPreview> {
    const params = new HttpParams()
      .set('receta_id', recetaId.toString())
      .set('cantidad', cantidad.toString());

    return this.http.get<ProduccionPreview>(`${this.apiUrl}/producciones/preview`, { params });
  }

  registrarProduccion(data: { receta_id: number; cantidad: number; fecha?: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}/producciones`, data);
  }

  getComprasIngredientes(): Observable<CompraIngrediente[]> {
    return this.http.get<CompraIngrediente[]>(`${this.apiUrl}/compras-ingredientes`);
  }

  registrarCompraIngrediente(payload: {
    ingrediente_id: number;
    cantidad: number;
    fecha?: string;
    observacion?: string;
  }): Observable<{ message: string; compra: CompraIngrediente; ingrediente: Ingrediente }> {
    return this.http.post<{ message: string; compra: CompraIngrediente; ingrediente: Ingrediente }>(
      `${this.apiUrl}/compras-ingredientes`,
      payload
    );
  }
}

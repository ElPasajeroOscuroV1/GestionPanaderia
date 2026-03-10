import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ApiService, DashboardData } from '../../services/api.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard implements OnInit {
  data: DashboardData = {
    ingredientes: 0,
    recetas: 0,
    produccion_hoy: 0,
    productos_en_stock: 0,
    ingredientes_bajo_stock_total: 0,
    ingredientes_bajo_stock: [],
    producciones_recientes: [],
  };

  loading = false;
  error = '';

  constructor(private readonly api: ApiService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.loading = true;
    this.error = '';

    this.api.getDashboard().subscribe({
      next: (res) => {
        this.data = res;
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.error = 'No se pudo cargar dashboard.';
      },
    });
  }
}

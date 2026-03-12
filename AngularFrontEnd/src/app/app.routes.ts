import { Routes } from '@angular/router';
import { Layout } from './layout/layout';

import { Dashboard } from './pages/dashboard/dashboard';
import { InventarioComponent } from './pages/inventario/inventario';
import { ProduccionComponent } from './pages/produccion/produccion';
import { RecetasComponent } from './pages/recetas/recetas';
import { ComprasComponent } from './pages/compras/compras';
import { ReportesComponent } from './pages/reportes/reportes';
import { LoginComponent } from './pages/login/login';
import { adminGuard, authGuard, guestGuard } from './guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    component: LoginComponent,
    canActivate: [guestGuard],
  },

  {
    path: '',
    component: Layout,
    canActivate: [authGuard],
    children: [
      { path: 'dashboard', component: Dashboard },
      { path: 'inventario', component: InventarioComponent },
      { path: 'compras', component: ComprasComponent, canActivate: [adminGuard] },
      { path: 'recetas', component: RecetasComponent },
      { path: 'produccion', component: ProduccionComponent },
      { path: 'reportes', component: ReportesComponent, canActivate: [adminGuard] },

      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
    ]
  },

  {
    path: '**',
    redirectTo: '',
  }
];

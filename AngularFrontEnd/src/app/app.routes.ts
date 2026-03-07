import { Routes } from '@angular/router';
import { Layout } from './layout/layout';

import { Dashboard } from './pages/dashboard/dashboard';
import { InventarioComponent } from './pages/inventario/inventario';
import { ProduccionComponent } from './pages/produccion/produccion';

export const routes: Routes = [

  {
    path:'',
    component: Layout,
    children:[
      { path:'dashboard', component: Dashboard },
      { path:'inventario', component: InventarioComponent },
      { path:'produccion', component: ProduccionComponent },

      { path:'', redirectTo:'dashboard', pathMatch:'full' }

    ]
  }

];
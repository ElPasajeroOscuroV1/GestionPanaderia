import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { AuthService, AuthUser } from '../services/auth.service';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class Layout {
  constructor(
    private readonly authService: AuthService,
    private readonly router: Router
  ) {}

  get currentUser(): AuthUser | null {
    return this.authService.getCurrentUser();
  }

  get isAdmin(): boolean {
    return this.authService.hasRole('admin');
  }

  get rolLabel(): string {
    if (!this.currentUser) {
      return 'Sin rol';
    }

    return this.currentUser.rol === 'admin' ? 'Administrador' : 'Panadero';
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: () => {
        this.authService.forceLogout();
        this.router.navigate(['/login']);
      },
    });
  }
}

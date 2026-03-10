import { Component } from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { AuthService, AuthUser } from '../services/auth.service';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [RouterModule],
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

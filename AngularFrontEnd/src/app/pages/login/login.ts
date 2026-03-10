import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class LoginComponent {
  usuario = '';
  password = '';

  loading = false;
  error = '';

  constructor(private readonly authService: AuthService, private readonly router: Router) {}

  submit(): void {
    this.error = '';

    const usuario = this.usuario.trim();
    if (!usuario || !this.password) {
      this.error = 'Ingresa usuario y contraseña.';
      return;
    }

    this.loading = true;
    this.authService.login(usuario, this.password).subscribe({
      next: () => {
        this.loading = false;
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.loading = false;
        this.error = err?.error?.error || 'Credenciales incorrectas.';
      },
    });
  }
}

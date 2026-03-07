import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getDashboard(){
    return this.http.get(`${this.apiUrl}/dashboard`);
  }

  getIngredientes() {
    return this.http.get(`${this.apiUrl}/ingredientes`);
  }

  getRecetas() {
    return this.http.get(`${this.apiUrl}/recetas`);
  }

  registrarProduccion(data:any) {
    return this.http.post(`${this.apiUrl}/producciones`, data);
  }

}
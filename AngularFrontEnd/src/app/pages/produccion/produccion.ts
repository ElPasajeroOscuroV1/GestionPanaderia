import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-produccion',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './produccion.html',
  styleUrls: ['./produccion.css']
})

export class ProduccionComponent implements OnInit {
  produciendo = false;

  recetas:any[] = [];

  receta_id:number = 0;
  cantidad:number = 0;

  constructor(private api:ApiService){}

  ngOnInit(){

    this.api.getRecetas().subscribe((data:any)=>{
      this.recetas = data;
    });

  }

  
  producir(){

    if(this.produciendo) return;

    this.produciendo = true;

    const data = {
      receta_id: this.receta_id,
      cantidad: this.cantidad,
      fecha: new Date().toISOString().split('T')[0]
    };

    this.api.registrarProduccion(data).subscribe({
      next: ()=>{
        alert("Producción registrada");
        this.produciendo = false;
      },
      error: ()=>{
        this.produciendo = false;
      }
    });

  }
  /*
  producir(){

    const data = {
      receta_id: this.receta_id,
      cantidad: this.cantidad,
      fecha: new Date().toISOString().split('T')[0]
    };

    this.api.registrarProduccion(data).subscribe({
      next: (res)=>{
        alert("Producción registrada");
      },
      error: (err)=>{
        alert(err.error.error);
      }
    });

  }
  */

}
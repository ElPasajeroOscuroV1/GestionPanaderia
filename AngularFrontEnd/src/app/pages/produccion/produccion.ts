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

    const data = {
      receta_id: this.receta_id,
      cantidad: this.cantidad
    };

    this.api.registrarProduccion(data).subscribe(res=>{
      alert("Producción registrada");
    },
    err=>{
      alert(err.error.error);
    });

  }

}
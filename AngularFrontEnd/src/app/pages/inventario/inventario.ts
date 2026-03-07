import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-inventario',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './inventario.html',
  styleUrls: ['./inventario.css']
})

export class InventarioComponent implements OnInit {

  ingredientes:any[] = [];

  constructor(private api:ApiService){}

  ngOnInit(){

    this.api.getIngredientes().subscribe((data:any)=>{
      this.ingredientes = data;
      console.log(data);
    });

  }

}
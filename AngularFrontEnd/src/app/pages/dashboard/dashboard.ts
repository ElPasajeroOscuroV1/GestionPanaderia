import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard implements OnInit {

  data:any = {};

  constructor(private api:ApiService){}

  ngOnInit(){

    this.api.getDashboard().subscribe((res:any)=>{
      this.data = res;
    });

  }

}
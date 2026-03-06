import { Injectable } from '@angular/core';
import { createClient } from '@supabase/supabase-js';
import { environment } from '../../environments/environment';
/*
@Injectable({
  providedIn: 'root',
})

export class Supabase {
  
}
*/
export const supabase = createClient(
  environment.supabaseUrl,
  environment.supabaseKey
);

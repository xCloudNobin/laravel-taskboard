<?php
use Illuminate\Support\Facades\Route;
Route::get('/', function () { Illuminate\Support\Facades\DB::select('select 1'); return 'qa-v1'; });

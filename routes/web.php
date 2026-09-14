<?php
use Illuminate\Support\Facades\Route;
Route::get('/', function () { Illuminate\Support\Facades\DB::select('select 1'); if (!env('QA_SECRET')) { throw new RuntimeException('QA_SECRET missing'); } return 'qa-v1'; });

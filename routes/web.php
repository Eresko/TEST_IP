<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/openapi', function () {
    Artisan::call('swagger:generate');
    Artisan::call('view:clear');
    return view('swagger');
});

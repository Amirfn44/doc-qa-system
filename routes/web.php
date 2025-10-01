<?php

use App\Http\Controllers\QaController;
use Illuminate\Support\Facades\Route;


Route::get('/qa', function () {
    return view('qa');
});


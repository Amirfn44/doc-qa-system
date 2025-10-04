<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('qa');
});

Route::get('/qa', function () {
    return view('qa');
});

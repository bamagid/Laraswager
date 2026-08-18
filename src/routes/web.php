<?php

use Illuminate\Support\Facades\Route;

Route::get(config('laraswagger.route', 'api/documentation'), function () {
    return view('laraswagger::index');
});

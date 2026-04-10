<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/user', function () {
    return response()->json(User::find(1));
});

<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/user', function () {
    return response()->json(['user' => User::find(1)], 200);
});

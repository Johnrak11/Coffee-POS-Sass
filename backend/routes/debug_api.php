<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('debug/headers', function (Request $request) {
    return response()->json([
        'ip' => $request->ip(),
        'ips' => $request->ips(),
        'headers' => $request->header(),
        'server' => $request->server(),
    ]);
});

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class RecomendacionController extends Controller
{
    public function recomendar($producto_id)
    {
        $response = Http::get("http://127.0.0.1:8001/recomendar/$producto_id");

        return response()->json($response->json());
    }
}

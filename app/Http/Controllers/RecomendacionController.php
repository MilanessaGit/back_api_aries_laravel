<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Facades\Http;

class RecomendacionController extends Controller
{
    public function recomendar($producto_id)
    {
        $productoBase = Producto::find($producto_id);

        if (!$productoBase) {
            return response()->json([
                'mensaje' => 'Producto no encontrado',
                'producto_base' => (int) $producto_id,
                'k' => 5,
                'resultados' => [],
            ], 404);
        }

        try {
            $response = Http::timeout(5)
                ->get("http://127.0.0.1:8001/recomendar/{$producto_id}");

            if (!$response->successful()) {
                return response()->json([
                    'mensaje' => 'El servicio de recomendaciones respondió con un error.',
                    'producto_base' => (int) $producto_id,
                    'k' => 5,
                    'resultados' => [],
                ], 502);
            }

            $datosPython = $response->json();

            // FastAPI devuelve un objeto con "error"
            // cuando no puede generar recomendaciones.
            if (
                is_array($datosPython)
                && isset($datosPython['error'])
            ) {
                return response()->json([
                    'mensaje' => $datosPython['error'],
                    'producto_base' => (int) $producto_id,
                    'k' => 5,
                    'resultados' => [],
                ], 200);
            }

            if (!is_array($datosPython)) {
                return response()->json([
                    'mensaje' => 'La respuesta del servicio de recomendaciones no es válida.',
                    'producto_base' => (int) $producto_id,
                    'k' => 5,
                    'resultados' => [],
                ], 502);
            }

            $ids = collect($datosPython)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return response()->json([
                    'mensaje' => 'No se encontraron productos similares disponibles.',
                    'producto_base' => (int) $producto_id,
                    'k' => 5,
                    'resultados' => [],
                ], 200);
            }

            $productos = Producto::with('categoria:id,nombre')
                ->withSum('lotes', 'cantidad_actual')
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            /*
             * Conservamos exactamente el orden calculado
             * por KNN en Python.
             */
            $resultados = collect($datosPython)
                ->map(function ($recomendacion) use ($productos) {

                    $id = (int) ($recomendacion['id'] ?? 0);

                    $producto = $productos->get($id);

                    if (!$producto) {
                        return null;
                    }

                    return [
                        'id' => $producto->id,

                        'codigo_producto' =>
                            $producto->codigo_producto,

                        'nombre' =>
                            $producto->nombre,

                        'precio_sugerido' =>
                            (float) $producto->precio_sugerido,

                        'imagen' =>
                            $producto->imagen,

                        'stock' =>
                            (int) (
                                $producto
                                    ->lotes_sum_cantidad_actual
                                ?? 0
                            ),

                        'categoria' =>
                            $producto->categoria
                                ? [
                                    'id' =>
                                        $producto
                                            ->categoria
                                            ->id,

                                    'nombre' =>
                                        $producto
                                            ->categoria
                                            ->nombre,
                                ]
                                : null,

                        'distancia_precio' =>
                            (float) (
                                $recomendacion[
                                    'distancia_precio'
                                ]
                                ?? 0
                            ),
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'mensaje' =>
                    $resultados->isEmpty()
                        ? 'No se encontraron productos similares disponibles.'
                        : 'Recomendaciones obtenidas correctamente.',

                'producto_base' =>
                    (int) $producto_id,

                'k' => 5,

                'resultados' =>
                    $resultados,

            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'mensaje' =>
                    'El servicio de recomendaciones no está disponible. Verifique que FastAPI esté ejecutándose en el puerto 8001.',

                'producto_base' =>
                    (int) $producto_id,

                'k' => 5,

                'resultados' => [],

            ], 503);
        }
    }
}
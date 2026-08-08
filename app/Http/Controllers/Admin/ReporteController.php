<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    /**
     * Reporte del stock actual agrupado por producto.
     */
    public function inventarioActual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'estado' => ['nullable', Rule::in(['todos', 'disponible', 'bajo', 'sin_stock'])],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 20, 50])],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $estado = $validated['estado'] ?? 'todos';

        /*
         * En el proyecto actual, estado = 1 representa un lote activo.
         * Se agrega primero por producto para evitar GROUP BY innecesarios
         * en la consulta principal.
         */
        $lotesPorProducto = DB::table('lotes')
            ->select('producto_id')
            ->selectRaw(
                'COUNT(CASE WHEN estado = 1 AND cantidad_actual > 0 THEN 1 END) AS lotes_con_stock'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN estado = 1 THEN cantidad_actual ELSE 0 END), 0) AS stock_actual'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN estado = 1 THEN cantidad_actual * costo_unitario ELSE 0 END), 0) AS valor_inventario'
            )
            ->groupBy('producto_id');

        $query = Producto::query()
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->leftJoinSub($lotesPorProducto, 'inventario_lotes', function ($join) {
                $join->on('productos.id', '=', 'inventario_lotes.producto_id');
            })
            ->select([
                'productos.id',
                'productos.codigo_producto',
                'productos.nombre',
                'productos.stock_minimo',
                'categorias.id as categoria_id',
                'categorias.nombre as categoria',
            ])
            ->selectRaw('COALESCE(inventario_lotes.lotes_con_stock, 0) AS lotes_con_stock')
            ->selectRaw('COALESCE(inventario_lotes.stock_actual, 0) AS stock_actual')
            ->selectRaw('COALESCE(inventario_lotes.valor_inventario, 0) AS valor_inventario')
            ->selectRaw(
                "CASE
                    WHEN COALESCE(inventario_lotes.stock_actual, 0) <= 0 THEN 'sin_stock'
                    WHEN COALESCE(inventario_lotes.stock_actual, 0) <= productos.stock_minimo THEN 'bajo'
                    ELSE 'disponible'
                END AS estado_stock"
            );

        if (!empty($validated['buscar'])) {
            $buscar = trim($validated['buscar']);

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('productos.codigo_producto', 'like', "%{$buscar}%")
                    ->orWhere('productos.nombre', 'like', "%{$buscar}%");
            });
        }

        if (!empty($validated['categoria_id'])) {
            $query->where('productos.categoria_id', $validated['categoria_id']);
        }

        if ($estado === 'disponible') {
            $query->whereRaw(
                'COALESCE(inventario_lotes.stock_actual, 0) > productos.stock_minimo'
            );
        } elseif ($estado === 'bajo') {
            $query
                ->whereRaw('COALESCE(inventario_lotes.stock_actual, 0) > 0')
                ->whereRaw(
                    'COALESCE(inventario_lotes.stock_actual, 0) <= productos.stock_minimo'
                );
        } elseif ($estado === 'sin_stock') {
            $query->whereRaw('COALESCE(inventario_lotes.stock_actual, 0) <= 0');
        }

        $resumen = DB::query()
            ->fromSub(clone $query, 'inventario')
            ->selectRaw('COUNT(*) AS productos_registrados')
            ->selectRaw(
                "SUM(CASE WHEN estado_stock = 'disponible' THEN 1 ELSE 0 END) AS productos_disponibles"
            )
            ->selectRaw(
                "SUM(CASE WHEN estado_stock = 'bajo' THEN 1 ELSE 0 END) AS productos_stock_bajo"
            )
            ->selectRaw(
                "SUM(CASE WHEN estado_stock = 'sin_stock' THEN 1 ELSE 0 END) AS productos_sin_stock"
            )
            ->selectRaw('COALESCE(SUM(stock_actual), 0) AS total_unidades')
            ->selectRaw('COALESCE(SUM(valor_inventario), 0) AS valor_inventario')
            ->first();

        $paginador = $query
            ->orderByRaw(
                "CASE estado_stock
                    WHEN 'sin_stock' THEN 1
                    WHEN 'bajo' THEN 2
                    ELSE 3
                END"
            )
            ->orderBy('productos.nombre')
            ->paginate($perPage);

        $paginador->getCollection()->transform(function ($item) {
            return [
                'id' => (int) $item->id,
                'codigo_producto' => $item->codigo_producto,
                'nombre' => $item->nombre,
                'categoria_id' => (int) $item->categoria_id,
                'categoria' => $item->categoria,
                'lotes_con_stock' => (int) $item->lotes_con_stock,
                'stock_actual' => (int) $item->stock_actual,
                'stock_minimo' => (int) $item->stock_minimo,
                'estado_stock' => $item->estado_stock,
                'valor_inventario' => round((float) $item->valor_inventario, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Reporte de inventario obtenido correctamente.',
            'data' => $paginador->items(),
            'resumen' => [
                'productos_registrados' => (int) ($resumen->productos_registrados ?? 0),
                'productos_disponibles' => (int) ($resumen->productos_disponibles ?? 0),
                'productos_stock_bajo' => (int) ($resumen->productos_stock_bajo ?? 0),
                'productos_sin_stock' => (int) ($resumen->productos_sin_stock ?? 0),
                'total_unidades' => (int) ($resumen->total_unidades ?? 0),
                'valor_inventario' => round((float) ($resumen->valor_inventario ?? 0), 2),
            ],
            'meta' => $this->metaPaginacion($paginador),
            'filtros' => [
                'buscar' => $validated['buscar'] ?? null,
                'categoria_id' => isset($validated['categoria_id'])
                    ? (int) $validated['categoria_id']
                    : null,
                'estado' => $estado,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Reporte de compras agrupadas por proveedor.
     */
    public function comprasPorProveedor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:fecha_fin'],
            'fecha_fin' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:fecha_inicio',
                'after_or_equal:fecha_inicio',
            ],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedors,id'],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 20, 50])],
        ]);

        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($validated);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $unidadesPorEntrada = DB::table('entrada_lote')
            ->select('entrada_id')
            ->selectRaw('SUM(cantidad) AS unidades_compradas')
            ->groupBy('entrada_id');

        $pagosPorEntrada = DB::table('pagos')
            ->select('entrada_id')
            ->selectRaw('SUM(monto) AS total_pagado')
            ->groupBy('entrada_id');

        $query = Entrada::query()
            ->join('proveedors', 'entradas.proveedor_id', '=', 'proveedors.id')
            ->leftJoinSub($unidadesPorEntrada, 'detalle_entrada', function ($join) {
                $join->on('entradas.id', '=', 'detalle_entrada.entrada_id');
            })
            ->leftJoinSub($pagosPorEntrada, 'pagos_entrada', function ($join) {
                $join->on('entradas.id', '=', 'pagos_entrada.entrada_id');
            })
            ->whereRaw('LOWER(TRIM(entradas.tipo_entrada)) = ?', ['compra'])
            ->whereBetween('entradas.fecha', [$fechaInicio, $fechaFin])
            ->when(
                !empty($validated['proveedor_id']),
                function ($subQuery) use ($validated) {
                    $subQuery->where('entradas.proveedor_id', $validated['proveedor_id']);
                }
            )
            ->groupBy([
                'proveedors.id',
                'proveedors.codigo_proveedor',
                'proveedors.nombre',
                'proveedors.apellido',
            ])
            ->select([
                'proveedors.id as proveedor_id',
                'proveedors.codigo_proveedor',
            ])
            ->selectRaw("TRIM(CONCAT_WS(' ', proveedors.nombre, proveedors.apellido)) AS proveedor")
            ->selectRaw('COUNT(DISTINCT entradas.id) AS cantidad_compras')
            ->selectRaw('COALESCE(SUM(detalle_entrada.unidades_compradas), 0) AS unidades_compradas')
            ->selectRaw('COALESCE(SUM(entradas.total), 0) AS total_comprado')
            ->selectRaw('COALESCE(SUM(pagos_entrada.total_pagado), 0) AS total_pagado')
            ->selectRaw(
                'COALESCE(SUM(entradas.total), 0) - COALESCE(SUM(pagos_entrada.total_pagado), 0) AS saldo_pendiente'
            );

        $resumen = DB::query()
            ->fromSub(clone $query, 'compras')
            ->selectRaw('COUNT(*) AS proveedores_involucrados')
            ->selectRaw('COALESCE(SUM(cantidad_compras), 0) AS cantidad_compras')
            ->selectRaw('COALESCE(SUM(unidades_compradas), 0) AS unidades_compradas')
            ->selectRaw('COALESCE(SUM(total_comprado), 0) AS total_comprado')
            ->selectRaw('COALESCE(SUM(total_pagado), 0) AS total_pagado')
            ->selectRaw('COALESCE(SUM(saldo_pendiente), 0) AS saldo_pendiente')
            ->first();

        $paginador = $query
            ->orderByDesc('total_comprado')
            ->orderBy('proveedors.nombre')
            ->paginate($perPage);

        $paginador->getCollection()->transform(function ($item) {
            return [
                'proveedor_id' => (int) $item->proveedor_id,
                'codigo_proveedor' => $item->codigo_proveedor,
                'proveedor' => $item->proveedor,
                'cantidad_compras' => (int) $item->cantidad_compras,
                'unidades_compradas' => (int) $item->unidades_compradas,
                'total_comprado' => round((float) $item->total_comprado, 2),
                'total_pagado' => round((float) $item->total_pagado, 2),
                'saldo_pendiente' => round((float) $item->saldo_pendiente, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Reporte de compras por proveedor obtenido correctamente.',
            'data' => $paginador->items(),
            'resumen' => [
                'proveedores_involucrados' => (int) ($resumen->proveedores_involucrados ?? 0),
                'cantidad_compras' => (int) ($resumen->cantidad_compras ?? 0),
                'unidades_compradas' => (int) ($resumen->unidades_compradas ?? 0),
                'total_comprado' => round((float) ($resumen->total_comprado ?? 0), 2),
                'total_pagado' => round((float) ($resumen->total_pagado ?? 0), 2),
                'saldo_pendiente' => round((float) ($resumen->saldo_pendiente ?? 0), 2),
            ],
            'meta' => $this->metaPaginacion($paginador),
            'filtros' => [
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'proveedor_id' => isset($validated['proveedor_id'])
                    ? (int) $validated['proveedor_id']
                    : null,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Reporte detallado de ventas dentro de un periodo.
     */
    public function ventasPorPeriodo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:fecha_fin'],
            'fecha_fin' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:fecha_inicio',
                'after_or_equal:fecha_inicio',
            ],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 20, 50])],
        ]);

        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($validated);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $unidadesPorVenta = DB::table('lote_venta')
            ->select('venta_id')
            ->selectRaw('SUM(cantidad) AS unidades_vendidas')
            ->groupBy('venta_id');

        $query = Venta::query()
            ->leftJoin('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->join('empleados', 'ventas.empleado_id', '=', 'empleados.id')
            ->leftJoinSub($unidadesPorVenta, 'detalle_venta', function ($join) {
                $join->on('ventas.id', '=', 'detalle_venta.venta_id');
            })
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', '<>', 3)
            ->when(
                !empty($validated['cliente_id']),
                function ($subQuery) use ($validated) {
                    $subQuery->where('ventas.cliente_id', $validated['cliente_id']);
                }
            )
            ->select([
                'ventas.id',
                'ventas.codigo_venta',
                'ventas.fecha_venta',
                'ventas.tipo_venta',
                'ventas.total',
                'ventas.estado',
                'clientes.id as cliente_id',
            ])
            ->selectRaw(
                "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', clientes.nombre, clientes.apellido)), ''), 'Sin cliente') AS cliente"
            )
            ->selectRaw(
                "TRIM(CONCAT_WS(' ', empleados.nombre, empleados.apellido)) AS responsable"
            )
            ->selectRaw('COALESCE(detalle_venta.unidades_vendidas, 0) AS unidades_vendidas');

        $resumen = DB::query()
            ->fromSub(clone $query, 'ventas_periodo')
            ->selectRaw('COUNT(*) AS cantidad_ventas')
            ->selectRaw('COALESCE(SUM(unidades_vendidas), 0) AS unidades_vendidas')
            ->selectRaw('COALESCE(SUM(total), 0) AS total_vendido')
            ->selectRaw('COALESCE(AVG(total), 0) AS promedio_venta')
            ->first();

        $paginador = $query
            ->orderByDesc('ventas.fecha_venta')
            ->orderByDesc('ventas.id')
            ->paginate($perPage);

        $paginador->getCollection()->transform(function ($item) {
            return [
                'id' => (int) $item->id,
                'codigo_venta' => $item->codigo_venta,
                'fecha_venta' => $item->fecha_venta,
                'tipo_venta' => $item->tipo_venta,
                'cliente_id' => $item->cliente_id !== null ? (int) $item->cliente_id : null,
                'cliente' => $item->cliente,
                'responsable' => $item->responsable,
                'unidades_vendidas' => (int) $item->unidades_vendidas,
                'total' => round((float) $item->total, 2),
                'estado' => (int) $item->estado,
                'estado_texto' => $this->textoEstadoVenta((int) $item->estado),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Reporte de ventas por periodo obtenido correctamente.',
            'data' => $paginador->items(),
            'resumen' => [
                'cantidad_ventas' => (int) ($resumen->cantidad_ventas ?? 0),
                'unidades_vendidas' => (int) ($resumen->unidades_vendidas ?? 0),
                'total_vendido' => round((float) ($resumen->total_vendido ?? 0), 2),
                'promedio_venta' => round((float) ($resumen->promedio_venta ?? 0), 2),
            ],
            'meta' => $this->metaPaginacion($paginador),
            'filtros' => [
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'cliente_id' => isset($validated['cliente_id'])
                    ? (int) $validated['cliente_id']
                    : null,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Ranking de productos por cantidad de unidades vendidas.
     */
    public function productosMasVendidos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:fecha_fin'],
            'fecha_fin' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:fecha_inicio',
                'after_or_equal:fecha_inicio',
            ],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'limite' => ['nullable', 'integer', Rule::in([5, 10, 20])],
        ]);

        [$fechaInicio, $fechaFin] = $this->resolverPeriodo($validated);
        $limite = (int) ($validated['limite'] ?? 10);

        $query = Producto::query()
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->join('lotes', 'productos.id', '=', 'lotes.producto_id')
            ->join('lote_venta', 'lotes.id', '=', 'lote_venta.lote_id')
            ->join('ventas', 'lote_venta.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', '<>', 3)
            ->when(
                !empty($validated['categoria_id']),
                function ($subQuery) use ($validated) {
                    $subQuery->where('productos.categoria_id', $validated['categoria_id']);
                }
            )
            ->groupBy([
                'productos.id',
                'productos.codigo_producto',
                'productos.nombre',
                'categorias.id',
                'categorias.nombre',
            ])
            ->select([
                'productos.id',
                'productos.codigo_producto',
                'productos.nombre',
                'categorias.id as categoria_id',
                'categorias.nombre as categoria',
            ])
            ->selectRaw('SUM(lote_venta.cantidad) AS unidades_vendidas')
            ->selectRaw('COUNT(DISTINCT ventas.id) AS cantidad_ventas')
            ->selectRaw(
                'SUM(lote_venta.cantidad * lote_venta.precio_unitario) AS total_generado'
            );

        $resumenGeneral = DB::query()
            ->fromSub(clone $query, 'productos_vendidos')
            ->selectRaw('COUNT(*) AS productos_con_ventas')
            ->selectRaw('COALESCE(SUM(unidades_vendidas), 0) AS total_unidades_vendidas')
            ->selectRaw('COALESCE(SUM(total_generado), 0) AS total_generado')
            ->first();

        $productos = $query
            ->orderByDesc('unidades_vendidas')
            ->orderByDesc('total_generado')
            ->limit($limite)
            ->get()
            ->values()
            ->map(function ($item, $indice) {
                return [
                    'posicion' => $indice + 1,
                    'id' => (int) $item->id,
                    'codigo_producto' => $item->codigo_producto,
                    'nombre' => $item->nombre,
                    'categoria_id' => (int) $item->categoria_id,
                    'categoria' => $item->categoria,
                    'unidades_vendidas' => (int) $item->unidades_vendidas,
                    'cantidad_ventas' => (int) $item->cantidad_ventas,
                    'total_generado' => round((float) $item->total_generado, 2),
                ];
            });

        $productoLider = $productos->first();

        return response()->json([
            'success' => true,
            'message' => 'Reporte de productos más vendidos obtenido correctamente.',
            'data' => $productos,
            'resumen' => [
                'producto_lider' => $productoLider['nombre'] ?? null,
                'unidades_producto_lider' => $productoLider['unidades_vendidas'] ?? 0,
                'productos_con_ventas' => (int) ($resumenGeneral->productos_con_ventas ?? 0),
                'total_unidades_vendidas' => (int) ($resumenGeneral->total_unidades_vendidas ?? 0),
                'total_generado' => round((float) ($resumenGeneral->total_generado ?? 0), 2),
            ],
            'meta' => [
                'limite' => $limite,
                'resultados' => $productos->count(),
            ],
            'filtros' => [
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'categoria_id' => isset($validated['categoria_id'])
                    ? (int) $validated['categoria_id']
                    : null,
                'limite' => $limite,
            ],
        ]);
    }

    /**
     * Si no se envía un periodo, usa el mes actual.
     */
    private function resolverPeriodo(array $validated): array
    {
        if (!empty($validated['fecha_inicio']) && !empty($validated['fecha_fin'])) {
            return [
                Carbon::createFromFormat('Y-m-d', $validated['fecha_inicio'])->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $validated['fecha_fin'])->endOfDay(),
            ];
        }

        return [
            Carbon::now()->startOfMonth()->startOfDay(),
            Carbon::now()->endOfMonth()->endOfDay(),
        ];
    }

    private function metaPaginacion($paginador): array
    {
        return [
            'pagina_actual' => $paginador->currentPage(),
            'ultima_pagina' => $paginador->lastPage(),
            'por_pagina' => $paginador->perPage(),
            'total' => $paginador->total(),
            'desde' => $paginador->firstItem(),
            'hasta' => $paginador->lastItem(),
        ];
    }

    private function textoEstadoVenta(int $estado): string
    {
        return match ($estado) {
            1 => 'Pendiente',
            2 => 'Completada',
            3 => 'Cancelada',
            default => 'Desconocido',
        };
    }
}

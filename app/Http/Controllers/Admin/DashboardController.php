<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\Venta;
use App\Models\Entrada;
use App\Models\LoteVenta;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();


        $productosStockBajo = $this->obtenerProductosStockBajo();

        $ventasSemana = $this->obtenerVentasSemana($inicioSemana, $finSemana);

        $ventasMes = $this->obtenerVentasMes();

        $productosMasVendidos = $this->obtenerProductosMasVendidos();

        $actividadReciente = $this->obtenerActividadReciente();

        $pagosPendientes = $this->obtenerPagosPendientes();



        $productosCategoria = Categoria::select(
                'categorias.nombre'
            )
            ->leftJoin('productos', 'categorias.id', '=', 'productos.categoria_id')
            ->groupBy(
                'categorias.id',
                'categorias.nombre'
            )
            ->selectRaw('COUNT(productos.id) as total')
            ->orderBy('categorias.nombre')
            ->get();
         
        $labelsCategorias = $productosCategoria
            ->pluck('nombre')
            ->toArray();

        $datosCategorias = $productosCategoria
            ->pluck('total')
            ->map(fn ($valor) => (int) $valor)
            ->toArray();
        
        $labelsMasVendidos = $productosMasVendidos
            ->pluck('nombre')
            ->toArray();

        $datosMasVendidos = $productosMasVendidos
            ->pluck('total_vendido')
            ->map(fn ($valor) => (int) $valor)
            ->toArray();    


        $labelsMes = [
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        ];

        $datosMes = [];
        for ($i = 1; $i <= 12; $i++) {
            $datosMes[] = (float) ($ventasMes[$i] ?? 0);
        }    


        $labelsSemana = [];
        $datosSemana = [];

        foreach (CarbonPeriod::create($inicioSemana, $finSemana) as $dia) {
            $fecha = $dia->format('Y-m-d');
            $labelsSemana[] = ucfirst($dia->locale('es')->translatedFormat('l'));
            $datosSemana[] = (float) ($ventasSemana[$fecha] ?? 0);
        }    

        $kpis = [

            // Registros generales
            'total_productos'   => Producto::count(),
            'total_categorias'  => Categoria::count(),
            'total_clientes'    => Cliente::count(),
            'total_proveedores' => Proveedor::count(),
            'total_empleados'   => Empleado::count(),

            // Inventario
            'stock_total' => (int) Lote::sum('cantidad_actual'),

            // Ventas
            'ventas_semana' => (float) Venta::whereBetween('fecha_venta', [
                $inicioSemana,
                $finSemana
            ])->sum('total'),

            'ventas_mes' => (float) Venta::whereBetween('fecha_venta', [
                $inicioMes,
                $finMes
            ])->sum('total'),

            // Compras (Entradas)
            'compras_semana' => (float) Entrada::whereBetween('fecha', [
                $inicioSemana,
                $finSemana
            ])->sum('total'),

            'compras_mes' => (float) Entrada::whereBetween('fecha', [
                $inicioMes,
                $finMes
            ])->sum('total'),

            'productos_stock_bajo' => (int) $productosStockBajo->count(),

            'saldo_pendiente_proveedores' => (float) $pagosPendientes->sum('pendiente'),

        ];

        
        return response()->json([
            'success' => true,
            'message' => 'Datos del dashboard obtenidos correctamente.',
            'data' => [
                'kpis' => $kpis,
                'charts' => [
                    'ventas_semana' => [
                        'labels' => $labelsSemana,
                        'datasets' => [
                            [
                                'label' => 'Ventas por Semana (Bs)',
                                'data' => $datosSemana
                            ]
                        ]
                    ],
                    'ventas_mes' => [
                        'labels' => $labelsMes,
                        'datasets' => [
                            [
                                'label' => 'Ventas por Mes (Bs)',
                                'data' => $datosMes
                            ]
                        ]
                    ],
                    'productos_categoria' => [
                        'labels' => $labelsCategorias,
                        'datasets' => [
                            [
                                'label' => 'Productos por Categoría',
                                'data' => $datosCategorias
                            ]
                        ]
                    ],
                    'productos_mas_vendidos' => [
                        'labels' => $labelsMasVendidos,
                        'datasets' => [
                            [
                                'label' => 'Unidades Vendidas',
                                'data' => $datosMasVendidos
                            ]
                        ]
                    ]
                ],
                'alerts' => [
                    'stock_bajo' => $productosStockBajo,
                    'pagos_pendientes' => $pagosPendientes
                ],
                'activity' => $actividadReciente
            ]
        ]);
    }

    private function obtenerVentasMes()
    {
        return Venta::selectRaw('MONTH(fecha_venta) as mes')
            ->selectRaw('SUM(total) as total')
            ->whereYear('fecha_venta', Carbon::now()->year)
            ->groupByRaw('MONTH(fecha_venta)')
            ->orderBy('mes')
            ->pluck('total', 'mes');
    }

    private function obtenerVentasSemana($inicioSemana, $finSemana)
    {
        return Venta::selectRaw('DATE(fecha_venta) as fecha')
            ->selectRaw('SUM(total) as total')
            ->whereBetween('fecha_venta', [$inicioSemana, $finSemana])
            ->groupByRaw('DATE(fecha_venta)')
            ->orderBy('fecha')
            ->pluck('total', 'fecha');
    }
    private function obtenerProductosStockBajo()
    {
        return Producto::select(
                'productos.id',
                'productos.nombre',
                'productos.stock_minimo'
            )
            ->leftJoin('lotes', 'productos.id', '=', 'lotes.producto_id')
            ->groupBy(
                'productos.id',
                'productos.nombre',
                'productos.stock_minimo'
            )
            ->selectRaw('COALESCE(SUM(lotes.cantidad_actual),0) as stock_actual')
            ->havingRaw('COALESCE(SUM(lotes.cantidad_actual),0) <= productos.stock_minimo')
            ->orderBy('stock_actual')
            ->get();
    }
    private function obtenerProductosMasVendidos()
    {
        return Producto::select(
                'productos.id',
                'productos.nombre'
            )
            ->leftJoin('lotes', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('lote_venta', 'lotes.id', '=', 'lote_venta.lote_id')
            ->groupBy(
                'productos.id',
                'productos.nombre'
            )
            ->selectRaw('COALESCE(SUM(lote_venta.cantidad),0) as total_vendido')
            ->havingRaw('COALESCE(SUM(lote_venta.cantidad),0) > 0')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get();
    }
    private function obtenerActividadReciente()
    {
        $ventas = Venta::select(
                'codigo_venta as codigo',
                'fecha_venta as fecha'
            )
            ->selectRaw("'Venta' as tipo")
            ->selectRaw("CONCAT('Venta ', codigo_venta, ' registrada.') as descripcion");

        $entradas = Entrada::select(
                'codigo_entrada as codigo',
                'fecha as fecha'
            )
            ->selectRaw("'Compra' as tipo")
            ->selectRaw("CONCAT('Entrada ', codigo_entrada, ' registrada.') as descripcion");

        return $ventas
            ->unionAll($entradas)
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();
    }
    private function obtenerPagosPendientes()
    {
        return Entrada::select(
                'entradas.id',
                'entradas.codigo_entrada',
                'proveedors.nombre'
            )
            ->leftJoin('proveedors', 'entradas.proveedor_id', '=', 'proveedors.id')
            ->leftJoin('pagos', 'entradas.id', '=', 'pagos.entrada_id')
            ->groupBy(
                'entradas.id',
                'entradas.codigo_entrada',
                'proveedors.nombre',
                'entradas.total'
            )
            ->selectRaw('entradas.total')
            ->selectRaw('COALESCE(SUM(pagos.monto),0) as pagado')
            ->selectRaw('(entradas.total - COALESCE(SUM(pagos.monto),0)) as pendiente')
            ->havingRaw('(entradas.total - COALESCE(SUM(pagos.monto),0)) > 0')
            ->orderByDesc('pendiente')
            ->limit(5)
            ->get();
    }
}


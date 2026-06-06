<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Lote;
use App\Models\Producto;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntradaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $entradas = Entrada::with('proveedor', 'lotes')->orderBy('id', 'desc')->paginate(5);

        //$entradas = Entrada::orderBy('id', 'desc')->paginate(10);
        return response()->json($entradas);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'empleado_id' => 'required|integer',

            'productos' => 'required|array|min:1',

            'productos.*.codigo_producto' => 'required|string',

            'productos.*.cantidad' => 'required|integer|min:1',

            'productos.*.costo_unitario' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try{
            $entrada = new Entrada();
            $entrada->empleado_id =  (int) $request->empleado_id;
            $entrada->proveedor_id = (int) $request->proveedor_id; // rev
            $entrada->codigo_entrada = Entrada::generarCodigoEntrada();
            $entrada->fecha = date('Y-m-d H:i:s');// now()
            $entrada->precio_total = 0; // se actualizará después de calcular
            //$entrada->observaciones = $request->observaciones ?? null;
            $entrada->save();

            $total = 0;
            foreach ($request->productos as $item) { // item = 1_producto
                // Observar que codigo_producto puede haber coincidencias en las letras del codigo y considerar buscar por ID del producto
                $producto = Producto::where(
                    'codigo_producto',
                    $item['codigo_producto']
                )
                ->lockForUpdate()
                ->firstOrFail();

                $lote = new Lote();

                $lote->codigo_lote = Lote::generarCodigoLote();
                $lote->producto_id = $producto->id;
                $lote->fecha_ingreso = now();
                $lote->cantidad = (int)$item['cantidad'];
                $lote->costo_unitario = (float)$item['costo_unitario'];
                $lote->estado = 1; // 1 = activo, 0 = inactivo
                $lote->save(); // con esto se crea el lote en BD y tiene su ID

                $entrada->lotes()->attach(
                    $lote->id,
                    [
                        'cantidad' => (int)$item['cantidad'],
                        'precio_unitario' => (float)$item['costo_unitario']
                    ]
                );

                $total += ((int)$item['cantidad']) * ((float)$item['costo_unitario']);
            }
            $entrada->precio_total = $total;
            $entrada->save();

            DB::commit();
                
            return response()->json(["mensaje" => "Entrada Registrada", "data" => $entrada], 200);
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return response()->json(["mensaje" => "Error al registrar la entrada", "error" => $e->getMessage()], 500);
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

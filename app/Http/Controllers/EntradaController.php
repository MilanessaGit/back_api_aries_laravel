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
        // Validación completa
        /*$request->validate([
            'proveedor_id' => 'required|numeric|exists:proveedors,id',
            //'empleado_id' => 'required|numeric|exists:empleados,id',
            
            'lot.cantidad' => 'required|numeric|min:1',
            //'observaciones' => 'nullable|string'
            'lot.precio' => 'required'
            //'lot.producto_id' => 'required'
        ]);*/

        DB::beginTransaction();
        try{
            //registrar nueva entrada (PENDIENTE)
            $entrada = new Entrada();
            // asignar proveedor a la entrada
            $entrada->empleado_id =  (int) $request->empleado_id;
            $entrada->proveedor_id = (int) $request->proveedor_id;
            
            $entrada->codigo_entrada = Entrada::generarCodigoEntrada();
            $entrada->fecha = date('Y-m-d H:i:s');// now()
            $entrada->precio_total = (float) $request->p_total; 

            //$entrada->observaciones = $request->observaciones ?? null;
            
            $entrada->save();

            // guardar
            $lote = new Lote();

            //$lote->codigo_lote = $request->codigo_lote;
            $lote->codigo_lote = Lote::generarCodigoLote(); // Generar código de lote automáticamente
            $lote->costo_unitario = (float) $request->lot['costo_unitario'];
            $lote->cantidad = (int) $request->lot['cantidad'];

            //$producto = Producto::find($request->lot.producto_id); // Obtener el producto por su ID
            

            $producto = Producto::where('codigo_producto', $request->lot['codigo_producto'])->lockForUpdate()->firstOrFail();

            
            if (!$producto) {
                throw new \Exception("Producto no encontrado con código: " . $request->lot['codigo_producto']);
            }
            // Sabado tarea: Para el id debemos 1ro relacionar el CodProd del frontend con el CodProd existente de la tabla productos para asi obtener el ID del producto al cual hacemos Referencia

            $lote->producto_id = $producto->id; // Asignar el ID del producto al lote
            $lote->save();

            //Attah nos ayuda a insertar en la tabla intermedia 'venta_lote' los datos de la venta, el lote y los atributos adicionales cantidad y precio_unitario

            
            $entrada->lotes()->attach($lote->id, [
            'cantidad' => (int) $request->lot['cantidad'],// La cantidad del frontend 
            'precio_unitario' => (float) $request->lot['costo_unitario'], 

            //'observaciones' => $lot['observaciones'] ?? null
            ]);
                
           // $lote->save(); // guardamos los cambios en el lote
             // $entrada->save();

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

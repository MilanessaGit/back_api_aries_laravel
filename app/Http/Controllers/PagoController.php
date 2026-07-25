<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Entrada;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Lista todos los pagos.
     */
    public function index(Request $request)
    {
        $query = Pago::with([
            'entrada.proveedor',
            'entrada.empleado'
            ]);

        if ($request->filled('entrada_id')) {
            $query->where('entrada_id',$request->entrada_id);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Mostrar un pago.
     */
    public function show($id)
    {
        $pago = Pago::with([
            'entrada.proveedor',
            'entrada.empleado'
        ])->find($id);

        if (!$pago) {
            return response()->json([
                "message" => "Pago no encontrado."
            ],404);
        }

        return response()->json($pago,200);
    }

    /**
     * Registrar nuevo pago.
     */
    public function store(Request $request)
    {
        $request->validate([
            'entrada_id'      => 'required|exists:entradas,id',
            'fecha_pago'      => 'required|date',
            'monto'           => 'required|numeric|min:0.01',
            'metodo_pago'     => 'required|in:Efectivo,Transferencia,QR,Cheque',
            'observaciones'   => 'nullable|string'
        ]);

        $entrada = Entrada::find($request->entrada_id);

        if (!$entrada) {
            return response()->json([
                "message" => "La entrada no existe."
            ],404);
        }

        // Solo compras pueden recibir pagos
        if (strtolower(trim($entrada->tipo_entrada)) != 'compra') {

            return response()->json([
                "message" => "Solo las compras pueden registrar pagos."
            ],422);

        }

        // Validar sobrepago
        $totalPagado = $entrada->pagos()->sum('monto');

        if (($totalPagado + $request->monto) > $entrada->total) {

            return response()->json([
                "message" => "El monto del pago excede el saldo pendiente."
            ],422);

        }

        $pago = Pago::create([
            'entrada_id' => $request->entrada_id,
            'fecha_pago' => $request->fecha_pago,
            'monto' => $request->monto,
            'metodo_pago' => $request->metodo_pago,
            'observaciones' => $request->observaciones
        ]);

        // Recalcular estado
        $this->actualizarEstadoPago($entrada);

        return response()->json([
            "message" => "Pago registrado correctamente.",
            "pago" => $pago,
            "resumen" => $this->obtenerResumenPago($entrada->fresh())
        ],201);
    }

    /**
     * Eliminar pago.
     */
    public function destroy($id)
    {
        $pago = Pago::find($id);

        if (!$pago) {

            return response()->json([
                "message"=>"Pago no encontrado."
            ],404);

        }

        $entrada = $pago->entrada;

        $pago->delete();

        $this->actualizarEstadoPago($entrada);

        return response()->json([
            "message"=>"Pago eliminado correctamente.",
            "resumen"=>$this->obtenerResumenPago($entrada->fresh())
        ],200);
    }

    /**
     * Obtiene total, pagado y saldo.
     */
    private function obtenerResumenPago(Entrada $entrada)
    {
        $pagado = $entrada->pagos()->sum('monto');

        return [

            "total"=>$entrada->total,

            "pagado"=>$pagado,

            "saldo"=>max(0,$entrada->total-$pagado),

            "estado_pago"=>$entrada->estado_pago

        ];
    }

    /**
     * Actualiza estado de pago.
     *
     * 1 = Pendiente
     * 2 = Pagado
     * 3 = Parcial
     */
    private function actualizarEstadoPago(Entrada $entrada)
    {
        $pagado = $entrada->pagos()->sum('monto');

        if($pagado <= 0){

            $entrada->estado_pago = 1;

        }
        elseif($pagado < $entrada->total){

            $entrada->estado_pago = 3;

        }
        else{

            $entrada->estado_pago = 2;

        }

        $entrada->save();
    }
}
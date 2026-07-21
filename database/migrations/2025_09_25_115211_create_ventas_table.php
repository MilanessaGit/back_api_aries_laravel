<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_venta', 20);
            $table->string('tipo_venta', 20); // Directa, Reserva, Contrato
            $table->dateTime('fecha_venta');
            $table->decimal('total', 12, 2);
            $table->decimal('adelanto', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            //$table->decimal('descuento', 12, 2)->default(0);
            $table->dateTime('fecha_entrega')->nullable();
            $table->integer('estado')->default(1); // 1:pendiente(), 2: completada(entregado), 3: cancelado // Obs: Al registrar es pendiente(1), y el sistema lo cambia a completado(2)
            $table->text('observaciones')->nullable();
            $table->bigInteger('cliente_id')->unsigned()->nullable();
            $table->bigInteger('empleado_id')->unsigned();

            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('empleado_id')->references('id')->on('empleados');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ventas');
    }
};

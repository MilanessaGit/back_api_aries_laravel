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
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_salida', 20);
            $table->dateTime('fecha');
            //$table->decimal('total', 12, 2);
            $table->integer('tipo'); // 1:venta, 2:robo, 3:daño, 4:ajuste_negativo
            $table->text('observaciones')->nullable();
            $table->bigInteger('aprobado_por')->unsigned();
            $table->bigInteger('venta_id')->unsigned()->nullable();
            

            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('aprobado_por')->references('id')->on('empleados');
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
        Schema::dropIfExists('salidas');
    }
};

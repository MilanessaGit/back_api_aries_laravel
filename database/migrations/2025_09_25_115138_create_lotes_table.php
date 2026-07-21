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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_lote', 100);
            $table->integer('cantidad_inicial')->default(0); //----cantidad
            $table->integer('cantidad_actual')->default(0);
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->date('fecha_ingreso')->nullable();
            $table->string('origen', 50)->nullable(); //compra, fabricacion, devolucion?
            $table->integer('estado')->default(1); //0 = ACTIVO 1 = DAÑADO 2 = BLOQUEADO
            //$table->text('trazabilidad')->nullable();
            $table->bigInteger('producto_id')->unsigned();
            $table->text('observaciones')->nullable();

            $table->foreign('producto_id')->references('id')->on('productos');
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
        Schema::dropIfExists('lotes');
    }
};

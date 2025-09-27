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
            $table->integer('cantidad')->default(0);
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->integer('estado')->default(1); // 0:agotado, 1: Disponible, 2:reservado, 3:devuelto, 4:danado
            $table->text('trazabilidad')->nullable();

            $table->bigInteger('producto_id')->unsigned();
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

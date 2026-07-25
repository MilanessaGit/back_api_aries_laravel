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
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_entrada', 20);
            $table->string('tipo_entrada', 50); // 1:compra, 2:produccion_propia, 3:devolucion_clientea 4:juste_positivo
            $table->decimal('total', 12, 2)->nullable(); // ------precio_total 
            $table->dateTime('fecha');
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->string('tipo_pago', 50)->nullable(); // 1:contado, 2:credito
            $table->integer('estado_pago')->default(1); // 1:pendiente, 2:pagado, 3:parcial
            $table->text('observaciones')->nullable();
            $table->bigInteger('empleado_id')->unsigned();
            $table->bigInteger('proveedor_id')->unsigned()->nullable();

            $table->foreign('empleado_id')->references('id')->on('empleados');
            $table->foreign('proveedor_id')->references('id')->on('proveedors');
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
        Schema::dropIfExists('entradas');
    }
};

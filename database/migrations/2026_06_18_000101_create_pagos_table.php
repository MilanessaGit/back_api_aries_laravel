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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago', 50);// efectivo, trasferencia, QR, cheque, tarjeta_credito, tarjeta_debito, etc.
            $table->text('observaciones')->nullable();
            $table->bigInteger('entrada_id')->unsigned();
            
            $table->foreign('entrada_id')->references('id')->on('entradas');
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
        Schema::dropIfExists('pagos');
    }
};

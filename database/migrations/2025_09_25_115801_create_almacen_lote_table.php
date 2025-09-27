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
        Schema::create('almacen_lote', function (Blueprint $table) {
            $table->id();
            $table->integer('cantidad')->default(1);

            $table->bigInteger('almacen_id')->unsigned();
            $table->bigInteger('lote_id')->unsigned();

             $table->foreign('almacen_id')->references('id')->on('almacens');
             $table->foreign('lote_id')->references('id')->on('lotes');
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
        Schema::dropIfExists('almacen_lote');
    }
};

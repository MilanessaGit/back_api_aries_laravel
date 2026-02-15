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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_producto', 50);
            $table->string('nombre', 200);
            $table->decimal('precio_sugerido', 12, 2)->default(0);
            $table->string('imagen')->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('material', 150)->nullable();
            $table->string('gama', 50)->nullable();
            $table->decimal('peso', 10, 2)->default(0);
            $table->string('dimensiones', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('caracteristicas_tecnicas')->nullable();
            //$table->integer('estado')->default(1); // 0: inactivo, 1: activo

            // N:1
            $table->bigInteger('categoria_id')->unsigned();
            $table->foreign('categoria_id')->references('id')->on('categorias');
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
        Schema::dropIfExists('productos');
    }
};

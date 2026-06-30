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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('ci_nit', 20)->nullable();
            $table->integer('edad')->nullable();
            $table->string('telefono', 20)->nullable(); //No es obligatorio
            $table->string('direccion', 255)->nullable();
            $table->string('imagen')->nullable();
            $table->decimal('salario', 12, 2)->default(0);
            $table->date('fecha_contratacion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('estado')->default(1); // 0:inactivo, 1: activo
            

            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('empleados');
    }
};

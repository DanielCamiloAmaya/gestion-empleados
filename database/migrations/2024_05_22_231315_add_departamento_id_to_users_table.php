<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar la columna 'departamento_id' después de la columna 'last_name'
            $table->unsignedBigInteger('departamento_id')->after('last_name');
            
            // Crear la clave foránea
            $table->foreign('departamento_id')
                  ->references('id')
                  ->on('departamentos')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar la clave foránea y luego la columna
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });
    }
};



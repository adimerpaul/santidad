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
        if (!Schema::hasColumn('web_movies', 'carrusel_tipo')) {
            Schema::table('web_movies', function (Blueprint $table) {
                $table->string('carrusel_tipo', 30)->default('ninguno')->after('tipo');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('web_movies', 'carrusel_tipo')) {
            Schema::table('web_movies', function (Blueprint $table) {
                $table->dropColumn('carrusel_tipo');
            });
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE web_movies MODIFY puntaje_web DECIMAL(6,3) NULL');
        DB::statement('ALTER TABLE web_movies MODIFY puntaje_ibm DECIMAL(6,3) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE web_movies MODIFY puntaje_web DECIMAL(5,2) NULL');
        DB::statement('ALTER TABLE web_movies MODIFY puntaje_ibm DECIMAL(5,2) NULL');
    }
};


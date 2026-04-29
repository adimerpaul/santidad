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
        if (!Schema::hasColumn('web_movies', 'tmdb_id')) {
            Schema::table('web_movies', function (Blueprint $table) {
                $table->unsignedBigInteger('tmdb_id')->nullable()->after('tipo')->index();
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
        if (Schema::hasColumn('web_movies', 'tmdb_id')) {
            Schema::table('web_movies', function (Blueprint $table) {
                $table->dropColumn('tmdb_id');
            });
        }
    }
};


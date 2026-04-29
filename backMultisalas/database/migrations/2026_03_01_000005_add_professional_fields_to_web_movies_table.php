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
        Schema::table('web_movies', function (Blueprint $table) {
            if (!Schema::hasColumn('web_movies', 'backdrop_imagen')) {
                $table->string('backdrop_imagen')->nullable()->after('imagen');
            }
            if (!Schema::hasColumn('web_movies', 'url_video_youtube')) {
                $table->string('url_video_youtube')->nullable()->after('trailer_youtube');
            }
            if (!Schema::hasColumn('web_movies', 'bucket')) {
                $table->string('bucket')->nullable()->after('url_video_youtube');
            }
            if (!Schema::hasColumn('web_movies', 'votos_total')) {
                $table->integer('votos_total')->nullable()->after('puntaje_metacritic');
            }
            if (!Schema::hasColumn('web_movies', 'popularidad')) {
                $table->decimal('popularidad', 12, 3)->nullable()->after('votos_total');
            }
            if (!Schema::hasColumn('web_movies', 'tagline')) {
                $table->string('tagline')->nullable()->after('popularidad');
            }
            if (!Schema::hasColumn('web_movies', 'url_oficial')) {
                $table->string('url_oficial')->nullable()->after('tagline');
            }
            if (!Schema::hasColumn('web_movies', 'descuento')) {
                $table->decimal('descuento', 5, 2)->default(0)->after('calidad');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_movies', function (Blueprint $table) {
            $columns = [
                'backdrop_imagen',
                'url_video_youtube',
                'bucket',
                'votos_total',
                'popularidad',
                'tagline',
                'url_oficial',
                'descuento',
            ];

            $existing = array_filter($columns, function ($column) {
                return Schema::hasColumn('web_movies', $column);
            });

            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('descripcion')->constrained('users')->onDelete('set null');
        });

        // Update existing items to copy user_id from the parent report
        DB::table('withdrawal_items')
            ->join('withdrawal_reports', 'withdrawal_items.withdrawal_report_id', '=', 'withdrawal_reports.id')
            ->update(['withdrawal_items.user_id' => DB::raw('withdrawal_reports.user_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_items', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

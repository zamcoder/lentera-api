<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * created_by — pembuat lingkaran (null utk circle seed sistem). Dipakai untuk
 * batas anti-spam (maks N circle per user) & kepemilikan (fase 2: edit/hapus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            if (! Schema::hasColumn('circles', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('member_count');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('created_by');
            $table->dropColumn('created_by');
        });
    }
};

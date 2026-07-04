<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambal kolom UI circles yang hilang pada environment yang menjalankan
 * versi awal create_circles (sebelum emoji/pal/description/member_count).
 * Idempoten (cek keberadaan kolom) — aman untuk fresh install maupun prod lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            if (! Schema::hasColumn('circles', 'emoji')) {
                $table->string('emoji')->nullable()->after('theme');
            }
            if (! Schema::hasColumn('circles', 'pal')) {
                $table->string('pal')->nullable()->after('emoji');
            }
            if (! Schema::hasColumn('circles', 'description')) {
                $table->text('description')->nullable()->after('pal');
            }
            if (! Schema::hasColumn('circles', 'member_count')) {
                $table->unsignedInteger('member_count')->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        // Tidak menurunkan kolom; data UI tak berbahaya untuk ditinggalkan.
    }
};

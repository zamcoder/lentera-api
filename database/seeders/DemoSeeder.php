<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder — data contoh agar konsol punya isi untuk ditinjau (antrean,
 * laporan, akun). Jalankan: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $members = collect([
            ['handle' => 'Pejalan Senja', 'status' => 'active'],
            ['handle' => 'Langit Kelabu', 'status' => 'active'],
            ['handle' => 'Badai Utara', 'status' => 'active'],
            ['handle' => 'Promo Senja', 'status' => 'muted'],
            ['handle' => 'Embun Pagi', 'status' => 'active'],
        ])->map(fn ($m) => User::firstOrCreate(
            ['handle' => $m['handle']],
            ['password_hash' => Hash::make('rahasia123'), 'role' => 'user', 'status' => $m['status']],
        ));

        // ---- Antrean: kiriman tertahan ----
        $held = [
            [
                'author' => $members[0], 'surface' => 'gratitude',
                'body' => 'Bersyukur akhirnya bisa lepas dari orang-orang menyebalkan di kantor itu.',
                'source' => 'ai', 'reason' => 'AI menandai nada negatif (58%). Curahan personal — bukan menyerang individu.',
                'self_harm' => false,
            ],
            [
                'author' => $members[3], 'surface' => 'gratitude',
                'body' => 'Cek bio aku ya, ada promo skincare murah, DM aja buruan stok terbatas.',
                'source' => 'ai', 'reason' => 'AI menandai sebagai spam/promosi (91%). Ajakan jual-beli di luar tujuan komunitas.',
                'self_harm' => false,
            ],
            [
                'author' => $members[1], 'surface' => 'circle',
                'body' => 'Capek banget rasanya. Kadang pengen semua ini berakhir aja, lelah pura-pura kuat.',
                'source' => 'ai', 'reason' => 'Penanganan khusus — terdeteksi isyarat menyakiti diri. Tawarkan dukungan, jangan blokir dingin.',
                'self_harm' => true,
            ],
        ];
        foreach ($held as $h) {
            Post::firstOrCreate(
                ['author_id' => $h['author']->id, 'body' => $h['body']],
                [
                    'surface' => $h['surface'], 'anon' => true, 'pseudonym' => $h['author']->handle,
                    'status' => Post::STATUS_HELD, 'mod_source' => $h['source'], 'mod_reason' => $h['reason'],
                    'self_harm' => $h['self_harm'],
                ],
            );
        }

        // ---- Feed: beberapa kiriman approved ----
        foreach (['Terima kasih untuk secangkir teh hangat sore ini.', 'Hari ini aku berani berkata cukup, dan itu melegakan.'] as $i => $body) {
            Post::firstOrCreate(
                ['author_id' => $members[$i]->id, 'body' => $body],
                ['surface' => 'gratitude', 'anon' => true, 'pseudonym' => $members[$i]->handle, 'status' => Post::STATUS_APPROVED, 'published_at' => now()],
            );
        }

        // ---- Laporan: kiriman approved yang dilaporkan ----
        $reported = Post::firstOrCreate(
            ['author_id' => $members[2]->id, 'body' => 'Kalian semua lemah kalau cuma bisa mengeluh di sini terus.'],
            ['surface' => 'gratitude', 'anon' => true, 'pseudonym' => 'Badai Utara', 'status' => Post::STATUS_APPROVED, 'published_at' => now()],
        );
        Report::firstOrCreate(
            ['post_id' => $reported->id, 'reporter_id' => $members[0]->id],
            ['reason' => 'harassment', 'note' => 'Merendahkan anggota lain', 'status' => 'open'],
        );
        Report::firstOrCreate(
            ['post_id' => $reported->id, 'reporter_id' => $members[4]->id],
            ['reason' => 'hate', 'status' => 'open'],
        );
    }
}

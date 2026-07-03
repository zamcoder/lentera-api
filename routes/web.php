<?php

use Illuminate\Support\Facades\Route;

/*
| Konsol moderasi (SPA React) disajikan Laravel. Semua path web (kecuali /api
| dan /up yang punya handler sendiri) mengembalikan shell konsol agar
| React Router menangani routing sisi-klien (/ringkasan, /antrean, dst).
*/
Route::view('/{any?}', 'console')
    ->where('any', '^(?!api|up).*$');

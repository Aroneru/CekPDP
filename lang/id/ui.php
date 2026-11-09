<?php

declare(strict_types=1);

return [
    'tagline' => 'Pemeriksa transparansi kebijakan privasi berdasarkan UU PDP',
    'skip_to_content' => 'Langsung ke konten utama',
    'new_analysis' => 'Analisis baru',

    'language' => [
        'label' => 'Bahasa antarmuka',
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ],

    'notice' => [
        'label' => 'Pemberitahuan',
        'body' => 'Alat ini memberikan tinjauan awal otomatis dan bukan nasihat hukum. Konsultasikan dengan profesional yang berkualifikasi sebelum mengambil keputusan terkait kepatuhan.',
    ],

    'errors' => [
        '404' => [
            'title' => 'Halaman tidak ditemukan',
            'body' => 'Halaman yang Anda cari tidak ada.',
        ],
        '419' => [
            'title' => 'Halaman kedaluwarsa',
            'body' => 'Formulir ini sudah terlalu lama terbuka sehingga tidak dapat dikirim. Muat ulang halaman, lalu tempel dan kirim teks kebijakan sekali lagi.',
        ],
        '429' => [
            'title' => 'Terlalu banyak permintaan',
            'body' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, lalu coba lagi.',
        ],
    ],

    'footer' => [
        'ruleset' => 'Daftar periksa UU PDP versi :version, terakhir ditinjau :date.',
        'scope' => 'CekPDP hanya menilai isi teks kebijakan, bukan praktik kepatuhan organisasi.',
        'translation' => 'Teks hukum dalam bahasa Inggris adalah terjemahan tidak resmi; teks Bahasa Indonesia yang berlaku.',
    ],
];

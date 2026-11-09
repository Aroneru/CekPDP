<?php

declare(strict_types=1);

return [
    'eyebrow' => 'Pemeriksa kebijakan privasi · UU No. 27 Tahun 2022',
    'title' => 'Periksa kebijakan privasi terhadap UU PDP',
    'intro' => 'CekPDP membandingkan teks kebijakan privasi dengan :count butir transparansi dan pengungkapan berdasarkan UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi. Untuk setiap butir Anda mendapat status, alasan, kutipan dari kebijakan, dan dasar hukumnya.',
    'scope' => 'Yang dinilai hanya isi teks kebijakan, bukan apakah organisasi benar-benar patuh.',

    'text' => [
        'label' => 'Teks kebijakan privasi',
        'hint' => 'Tempel teks lengkap kebijakan dalam Bahasa Indonesia atau bahasa Inggris, minimal :min karakter dan maksimal :max karakter. Teks yang terlalu panjang tidak dipotong; Anda akan diberi tahu ukurannya.',
        'count' => ':count karakter',
        'placeholder' => 'Tempel teks kebijakan privasi di sini…',
    ],

    'facts' => [
        'version' => 'Daftar periksa',
        'reviewed' => 'Ditinjau',
        'items' => 'Butir',
        'items_value' => ':legal hukum · :best praktik',
        'method' => 'Penilai',
        'method_mock' => 'Offline, kata kunci',
        'method_ai' => 'Claude (Anthropic)',
    ],

    'samples' => [
        'label' => 'Coba contoh fiktif:',
        'loaded' => 'Contoh dimuat: :title. Jawaban konteks diisi sesuai contoh.',
        'id-01-belanja-lengkap' => 'Toko daring, lengkap',
        'id-03-fintech-sedang' => 'Aplikasi fintech, sedang',
        'id-09-umkm-minim' => 'Toko roti kecil, minimal',
        'en-04-gdpr-style' => 'Layanan bergaya GDPR',
    ],

    'context' => [
        'heading' => 'Konteks',
        'optional' => 'opsional',
        'intro' => 'Jawaban Anda menentukan butir mana yang berlaku. Jika ragu, pilih "Tidak yakin": butir terkait tetap diperiksa.',
    ],

    'answers' => [
        'yes' => 'Ya',
        'no' => 'Tidak',
        'unsure' => 'Tidak yakin',
    ],

    'disclosure' => [
        'heading' => 'Sebelum Anda mengirim',
        'mock' => 'Mode offline aktif: teks dianalisis di server CekPDP dengan pencocokan kata kunci dan tidak dikirim ke penyedia AI pihak ketiga. Hasil mode ini jauh kurang akurat dibanding analisis AI.',
        'ai' => 'Teks kebijakan akan dikirim ke :provider, penyedia AI pihak ketiga, untuk dianalisis.',
        'confidential' => 'Jangan kirim informasi rahasia atau Data Pribadi. Kebijakan privasi yang sudah atau akan dipublikasikan umumnya aman untuk dianalisis.',
        'retention' => 'Teks disimpan terenkripsi dan dihapus otomatis :minutes menit setelah analisis selesai. Anda juga dapat menghapusnya lebih awal.',
        'acknowledge' => 'Saya sudah membaca pemberitahuan di atas dan tidak mengirim informasi rahasia.',
    ],

    'submit' => 'Analisis kebijakan',
    'submitting' => 'Mengirim…',
    'submit_hint' => 'Analisis berjalan di latar belakang; kemajuannya ditampilkan setelah Anda mengirim.',
    'errors_heading' => 'Periksa kembali isian berikut:',

    'about' => [
        'heading' => 'Tentang daftar periksa',
        'ruleset' => 'Versi :version, terakhir ditinjau :date: :legal persyaratan hukum dan :best praktik terbaik. Setiap rujukan pasal diverifikasi dengan salinan resmi UU No. 27 Tahun 2022.',
        'regulations' => 'Peraturan pelaksana',
        'not_used' => 'Tidak digunakan dalam daftar periksa ini.',
        'status' => [
            'in_force' => 'Berlaku',
            'promulgated_not_in_force' => 'Belum berlaku',
            'not_enacted' => 'Belum ditetapkan',
        ],
    ],
];

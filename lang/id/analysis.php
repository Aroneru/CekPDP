<?php

declare(strict_types=1);

return [
    'status' => [
        'addressed' => 'Tercakup',
        'partially_addressed' => 'Tercakup sebagian',
        'not_found' => 'Tidak ditemukan',
        'needs_human_review' => 'Perlu tinjauan manusia',
        'not_applicable' => 'Tidak berlaku',
    ],

    'type' => [
        'legal' => 'Persyaratan hukum',
        'best_practice' => 'Praktik terbaik',
    ],

    'interpretive' => 'Interpretasi',

    'stages' => [
        'queued' => 'Menunggu giliran',
        'extracting' => 'Menyiapkan teks',
        'structuring' => 'Mengenali bahasa dan bagian kebijakan',
        'evaluating' => 'Menilai setiap butir',
        'verifying' => 'Memverifikasi kutipan',
        'scoring' => 'Menghitung cakupan',
        'done' => 'Selesai',
    ],

    'stage_state' => [
        'done' => 'selesai',
        'current' => 'sedang berjalan',
        'pending' => 'belum dimulai',
    ],

    'progress' => [
        'title' => 'Menganalisis kebijakan',
        'intro' => 'Analisis berjalan di latar belakang. Halaman ini diperbarui otomatis dan menampilkan hasil setelah selesai.',
        'label' => 'Kemajuan analisis',
        'current' => 'Tahap saat ini: :stage.',
        'noscript' => 'JavaScript tidak aktif, jadi halaman ini dimuat ulang setiap 5 detik.',
        'stalled' => 'Belum dimulai? Analisis dijalankan oleh antrean (queue worker). Jika Anda menjalankan CekPDP sendiri, pastikan antrean berjalan.',
    ],

    'failed' => [
        'title' => 'Analisis gagal',
        'retry' => 'Mulai analisis baru',
        'expires' => 'Teks kebijakan dihapus otomatis :time.',
    ],

    'errors' => [
        'invalid_provider_output' => 'Penyedia analisis mengembalikan jawaban dalam format yang tidak terduga, juga setelah dicoba sekali lagi. Tidak ada hasil sebagian yang ditampilkan. Silakan coba lagi.',
        'ruleset_unavailable' => 'Daftar periksa yang dipakai analisis ini tidak dapat dimuat.',
        'timeout' => 'Analisis melebihi batas waktu. Silakan coba lagi.',
        'internal_error' => 'Terjadi kesalahan tak terduga saat menganalisis. Silakan coba lagi.',
        'rate_limited' => 'Anda sudah menjalankan :max analisis dalam satu jam terakhir. Coba lagi dalam :minutes menit.',
    ],

    'missing' => [
        'title' => 'Analisis tidak ditemukan',
        'body' => 'Analisis ini tidak ada atau sudah dihapus. Analisis dihapus otomatis :minutes menit setelah selesai, dan hanya dapat dibuka di peramban yang membuatnya.',
    ],

    'deleted' => 'Analisis beserta teks kebijakannya sudah dihapus.',
    'delete' => 'Hapus sekarang',

    'results' => [
        'title' => 'Hasil analisis',
        'offline' => 'Hasil ini dibuat dalam mode offline dengan pencocokan kata kunci, bukan dengan AI. Anggap sebagai gambaran kasar saja.',
        'retention' => 'Teks kebijakan dan hasil ini dihapus otomatis :time.',
        'summary_heading' => 'Ringkasan',
        'details' => [
            'language' => 'Bahasa terdeteksi',
            'size' => 'Ukuran',
            'size_value' => ':characters karakter, :words kata, :sections bagian',
            'provider' => 'Metode',
            'ruleset' => 'Daftar periksa',
            'ruleset_value' => 'Versi :version (ditinjau :date)',
            'completed' => 'Selesai',
        ],
        'languages' => [
            'id' => 'Bahasa Indonesia',
            'en' => 'Bahasa Inggris',
            'mixed' => 'Bahasa Indonesia dan Inggris',
            'unknown' => 'Tidak dapat ditentukan',
        ],
        'providers' => [
            'mock' => 'Mode offline (pencocokan kata kunci)',
            'claude' => 'Claude (Anthropic)',
        ],
        'counts_caption' => 'Jumlah butir per status',
        'counts_type' => 'Jenis',
        'legal_heading' => 'Persyaratan hukum',
        'best_heading' => 'Praktik terbaik',
        'not_applicable_heading' => 'Tidak berlaku',
        'not_applicable_intro' => 'Butir berikut tidak berlaku berdasarkan jawaban konteks Anda.',
        'context_heading' => 'Jawaban konteks',
        'review_heading' => 'Perlu tinjauan manusia',
        'review_intro' => 'Butir berikut tidak dapat dinilai dengan yakin. Bacalah bagian kebijakan yang terkait sendiri.',
        'none' => 'Tidak ada.',
    ],

    'score' => [
        'title' => 'Cakupan Kebijakan',
        'explanation' => 'Seberapa banyak persyaratan hukum UU PDP yang berlaku diungkapkan dalam kebijakan. Ini mengukur pengungkapan informasi, bukan kepatuhan.',
        'formula' => 'Rumus: (Tercakup + 0,5 × Tercakup sebagian) ÷ (Tercakup + Tercakup sebagian + Tidak ditemukan). Setiap butir berbobot sama.',
        'arithmetic' => '(:addressed + 0,5 × :partial) ÷ :evaluated = :percent%',
        'excluded' => ':count butir yang perlu tinjauan manusia tidak dihitung.',
        'withheld_label' => 'Tidak ditampilkan',
        'withheld' => [
            'too_many_reviews' => 'Skor tidak ditampilkan: :count dari :total persyaratan hukum (:share%) perlu tinjauan manusia, lebih dari batas :threshold%. Tinjau butir tersebut terlebih dahulu.',
            'no_legal_requirements' => 'Skor tidak ditampilkan karena tidak ada persyaratan hukum yang berlaku.',
        ],
        'best_practices' => 'Praktik terbaik: :addressed dari :total tercakup. Praktik terbaik tidak memengaruhi skor.',
    ],

    'item' => [
        'reason' => 'Alasan',
        'evidence' => 'Kutipan dari kebijakan',
        'section' => 'Bagian :section',
        'no_evidence' => 'Tidak ada kutipan.',
        'unverified' => 'Kutipan ini tidak ditemukan dalam teks kebijakan.',
        'section_corrected' => 'Ditemukan di bagian :actual (disebut :cited).',
        'downgraded' => [
            'unverified_quote' => 'Status diubah menjadi Perlu tinjauan manusia karena ada kutipan yang tidak dapat diverifikasi. Penilaian awal: :status.',
            'missing_evidence' => 'Status diubah menjadi Perlu tinjauan manusia karena tidak ada kutipan pendukung. Penilaian awal: :status.',
        ],
        'language_check' => 'Ditentukan dengan deteksi bahasa otomatis.',
        'look_for' => 'Yang dicari',
        'criteria' => 'Arti status untuk butir ini',
        'basis' => 'Dasar hukum',
        'related' => 'Ketentuan terkait',
        'official_text' => 'Teks resmi (JDIH BPK, PDF hlm. :page)',
        'reader' => 'Baca di Pasal.id (tidak resmi)',
        'provision_text' => 'Bunyi ketentuan',
        'unofficial_translation' => 'Terjemahan tidak resmi:',
        'modified_by' => 'Dibaca bersama :reference.',
        'next_step' => 'Langkah berikutnya',
        'example' => 'Contoh rumusan',
        'example_label' => 'Draf, perlu ditinjau ahli hukum',
        'interpretation' => 'Catatan interpretasi',
        'override' => 'Mengapa butir ini praktik terbaik',
        'not_applicable_because' => 'Tidak berlaku karena jawaban Anda:',
        'answer' => ':question Jawaban: :answer.',
    ],
];

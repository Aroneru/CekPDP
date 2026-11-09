<?php

declare(strict_types=1);

/*
 * Indonesian validation messages for the rules CekPDP uses. Laravel ships
 * English messages only; English falls back to the framework's defaults.
 */
return [
    'accepted' => ':Attribute harus disetujui.',
    'array' => ':Attribute tidak valid.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',

    'custom' => [
        'text' => [
            'required' => 'Tempel teks kebijakan privasi terlebih dahulu.',
            'too_short' => 'Teks terlalu pendek: :actual karakter. Tempel kebijakan lengkap, minimal :min karakter.',
            'too_long' => 'Teks terlalu panjang: :actual karakter, sedangkan batasnya :max karakter. Teks tidak dipotong otomatis. Hapus bagian yang bukan kebijakan privasi, atau analisis kebijakan dalam beberapa bagian.',
        ],
        'acknowledge' => [
            'accepted' => 'Centang pernyataan bahwa Anda sudah membaca pemberitahuan dan tidak mengirim informasi rahasia.',
        ],
        'context' => [
            'invalid' => 'Ada jawaban konteks yang tidak valid. Pilih Ya, Tidak, atau Tidak yakin.',
        ],
    ],

    'attributes' => [
        'text' => 'teks kebijakan',
        'acknowledge' => 'pernyataan',
        'context' => 'jawaban konteks',
    ],
];

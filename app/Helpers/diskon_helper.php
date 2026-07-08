<?php

if (!function_exists('hitung_biaya_jasa')) {

    function hitung_biaya_jasa($total_harga)
    {
        if ($total_harga <= 10000000) {
            return $total_harga * 0.01;
        }

        return $total_harga * 0.02;
    }
}

if (!function_exists('hitung_diskon_voucher')) {

    function hitung_diskon_voucher($total_harga, $voucher_code)
    {
        $voucher = strtoupper(trim($voucher_code));

        switch ($voucher) {

            case 'PROMO2025':
                return $total_harga * 0.10;

            case 'PROMO2026':
                return $total_harga * 0.15;

            case 'AKHIRTAHUN':
                return $total_harga * 0.25;

            default:
                return 0;
        }
    }
}

if (!function_exists('hitung_free_mouse')) {

    function hitung_free_mouse($total_harga)
    {
        if ($total_harga > 15000000) {
            return 150000;
        }

        return 0;
    }
}
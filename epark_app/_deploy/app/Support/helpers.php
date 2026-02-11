<?php

if (!function_exists('format_chf')) {
    /**
     * Format a number as CHF currency.
     */
    function format_chf(float $amount, int $decimals = 2): string
    {
        return 'CHF ' . number_format($amount, $decimals, '.', ' ');
    }
}

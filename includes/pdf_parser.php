<?php
declare(strict_types=1);

/**
 * Lightweight PDF text extraction with graceful fallback for scanned PDFs.
 * Uses `pdftotext` if available on the host, or extracts uncompressed/FlateDecode text streams in pure PHP.
 */
function extract_pdf_text(string $filePath): string
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return '';
    }

    // 1. Try poppler pdftotext if shell_exec is enabled on the server
    if (function_exists('shell_exec')) {
        $escaped = escapeshellarg($filePath);
        $bin = trim((string) @shell_exec('command -v pdftotext 2>/dev/null'));
        if ($bin !== '') {
            $text = @shell_exec("{$bin} -enc UTF-8 {$escaped} - 2>/dev/null");
            if (is_string($text) && trim($text) !== '') {
                return trim(preg_replace('/\s+/', ' ', $text) ?? '');
            }
        }
    }

    // 2. Pure-PHP stream parser fallback (works on shared hosting without shell_exec)
    $raw = @file_get_contents($filePath);
    if (!is_string($raw) || $raw === '') {
        return '';
    }

    $extracted = [];
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $matches)) {
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            $chunk = $decoded !== false ? $decoded : $stream;

            // Extract text inside PDF Tj / TJ operators
            if (preg_match_all('/\(([^()]*)\)\s*Tj/s', $chunk, $tj)) {
                foreach ($tj[1] as $str) {
                    $extracted[] = $str;
                }
            }
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $chunk, $tjArr)) {
                foreach ($tjArr[1] as $inner) {
                    if (preg_match_all('/\(([^()]*)\)/', $inner, $sub)) {
                        $extracted[] = implode('', $sub[1]);
                    }
                }
            }
        }
    }

    $joined = trim(preg_replace('/\s+/', ' ', implode(' ', $extracted)) ?? '');
    // Strip non-printable characters
    $joined = preg_replace('/[^\x20-\x7E]/', ' ', $joined) ?? '';
    return trim(mb_substr($joined, 0, 50000));
}

<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class EmailBodyCleaner
{
    /**
     * Convert an HTML email body to clean plain text.
     *
     * Strips HTML tags, removes quoted reply chains, email signatures,
     * and collapses excessive whitespace.
     */
    public static function clean(string $html, int $maxLen = 1500): string
    {
        $text = $html;

        // Strip <style> and <script> blocks
        $text = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $text);
        $text = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $text);

        // Convert <br> to newlines
        $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $text);

        // Convert block elements to newlines
        $text = (string) preg_replace('/<\/(p|div|tr|li|h[1-6]|blockquote)>/i', "\n", $text);
        $text = (string) preg_replace('/<(p|div|tr|li|h[1-6]|blockquote)\b[^>]*>/i', "\n", $text);

        // Remove remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove quoted reply chains: "On ... wrote:" and everything after
        $text = (string) preg_replace('/\bOn\s.+?\bwrote:\s*$.*/ms', '', $text);

        // Remove Czech variant: "Dne ... napsal:" and everything after
        $text = (string) preg_replace('/\bDne\s.+?\bnapsal[a]?:\s*$.*/ms', '', $text);

        // Remove lines starting with >
        $text = (string) preg_replace('/^>.*$/m', '', $text);

        // Remove email signatures (line starting with "-- ")
        $text = (string) preg_replace('/^-- \s*$.*/ms', '', $text);

        // Collapse excessive whitespace
        $text = (string) preg_replace('/[^\S\n]+/', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        // Truncate if needed
        if ($maxLen > 0 && mb_strlen($text) > $maxLen) {
            $text = mb_substr($text, 0, $maxLen) . '...';
        }

        return $text;
    }
}

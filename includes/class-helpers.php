<?php
/**
 * Helper condivisi del plugin.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Chiave univoca per deduplicare giocatori nel ranking (nome normalizzato).
 */
function tp_normalize_player_name_key(string $display_name): string
{
    $s = trim(mb_strtolower($display_name));
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) {
            $s = $t;
        }
    }
    $s = preg_replace('/[^a-z0-9 ]/', '', $s) ?? $s;
    $s = mb_substr(trim($s), 0, 160);

    if ($s !== '') {
        return $s;
    }

    return 'h' . substr(hash('sha256', mb_strtolower(trim($display_name))), 0, 32);
}

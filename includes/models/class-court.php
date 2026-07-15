<?php
/**
 * Model campo da gioco.
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Court
{
    /** @return list<array<string, mixed>> */
    public static function for_tournament(int $tournament_id, bool $active_only = true): array
    {
        global $wpdb;

        $table = TP_Database::table('courts');
        $sql   = "SELECT * FROM {$table} WHERE tournament_id = %d";
        if ($active_only) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY id ASC';

        $rows = $wpdb->get_results($wpdb->prepare($sql, $tournament_id), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public static function count(int $tournament_id, bool $active_only = true): int
    {
        return count(self::for_tournament($tournament_id, $active_only));
    }

    public static function sync_count(int $tournament_id, int $num_courts): void
    {
        TP_Activator::ensure_schema();

        $num_courts = max(1, min(16, $num_courts));
        $existing   = self::for_tournament($tournament_id, false);
        $current    = count($existing);

        if ($current < $num_courts) {
            for ($i = $current + 1; $i <= $num_courts; $i++) {
                self::create($tournament_id, sprintf(/* translators: %d: court number */ __('Campo %d', 'tornei-padel'), $i));
            }

            return;
        }

        if ($current > $num_courts) {
            $to_deactivate = array_slice($existing, $num_courts);
            foreach ($to_deactivate as $court) {
                self::set_active((int) $court['id'], false);
            }
        }

        $active = self::for_tournament($tournament_id, true);
        foreach ($active as $index => $court) {
            $expected = sprintf(/* translators: %d: court number */ __('Campo %d', 'tornei-padel'), $index + 1);
            if (($court['name'] ?? '') !== $expected) {
                self::rename((int) $court['id'], $expected);
            }
        }
    }

    /**
     * Assegna i campi in modo rotativo (carico equo).
     *
     * @param list<int> $match_ids
     */
    public static function assign_matches_equally(int $tournament_id, array $match_ids): void
    {
        $courts = self::for_tournament($tournament_id, true);
        if ($courts === [] || $match_ids === []) {
            return;
        }

        $num_courts = count($courts);
        foreach (array_values($match_ids) as $index => $match_id) {
            $court_id = (int) $courts[$index % $num_courts]['id'];
            TP_Match::set_court((int) $match_id, $court_id);
        }
    }

    private static function create(int $tournament_id, string $name): int
    {
        global $wpdb;

        $wpdb->insert(
            TP_Database::table('courts'),
            [
                'tournament_id' => $tournament_id,
                'name'          => $name,
                'indoor'        => 1,
                'active'        => 1,
            ],
            ['%d', '%s', '%d', '%d']
        );

        return (int) $wpdb->insert_id;
    }

    private static function set_active(int $court_id, bool $active): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('courts'),
            ['active' => $active ? 1 : 0],
            ['id' => $court_id],
            ['%d'],
            ['%d']
        );
    }

    private static function rename(int $court_id, string $name): void
    {
        global $wpdb;

        $wpdb->update(
            TP_Database::table('courts'),
            ['name' => $name],
            ['id' => $court_id],
            ['%s'],
            ['%d']
        );
    }
}

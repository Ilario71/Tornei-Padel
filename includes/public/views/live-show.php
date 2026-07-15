<?php
/**
 * Pagina live singolo torneo.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $tournament
 */

if (! defined('ABSPATH')) {
    exit;
}

$tid            = (int) $tournament['id'];
$groups         = TP_Group::for_tournament($tid);
$all_matches    = TP_Match::for_tournament($tid);
$group_matches  = array_values(array_filter($all_matches, static fn ($m) => ($m['phase'] ?? '') === 'group'));
$knockout_matches = array_values(array_filter($all_matches, static fn ($m) => ($m['phase'] ?? '') === 'knockout'));
$tournament_slug  = (string) $tournament['slug'];

get_header();
?>
<main class="tp-live-show tp-page" data-tp-live>
    <header class="tp-page-header">
        <p class="tp-breadcrumb"><a href="<?php echo esc_url(home_url('/torneo-live')); ?>">← LIVE</a></p>
        <h1><?php echo esc_html($tournament['name']); ?></h1>
        <span class="tp-live-pulse" data-tp-live-status><?php esc_html_e('Aggiornamento automatico', 'tornei-padel'); ?></span>
    </header>

    <div class="tp-live-grid">
        <section class="tp-panel" id="tp-matches-panel">
            <h2><?php esc_html_e('Partite', 'tornei-padel'); ?></h2>

            <?php if ($group_matches !== []) : ?>
                <h3 class="tp-match-phase-title"><?php esc_html_e('Gironi', 'tornei-padel'); ?></h3>
                <ul class="tp-match-feed">
                    <?php foreach ($group_matches as $match) : ?>
                        <?php include TP_PLUGIN_DIR . 'includes/public/views/partials/live-match-item.php'; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($knockout_matches !== []) : ?>
                <h3 class="tp-match-phase-title"><?php esc_html_e('Tabelloni', 'tornei-padel'); ?></h3>
                <ul class="tp-match-feed">
                    <?php foreach ($knockout_matches as $match) : ?>
                        <?php include TP_PLUGIN_DIR . 'includes/public/views/partials/live-match-item.php'; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($group_matches === [] && $knockout_matches === []) : ?>
                <p class="tp-empty-sm"><?php esc_html_e('Nessuna partita programmata.', 'tornei-padel'); ?></p>
            <?php endif; ?>
        </section>

        <section class="tp-panel" id="tp-standings-panel">
            <h2><?php esc_html_e('Classifiche', 'tornei-padel'); ?></h2>
            <?php foreach ($groups as $group) : ?>
                <?php $standings = TP_Standings_Service::for_group((int) $group['id']); ?>
                <div class="tp-group-standings">
                    <h3><?php echo esc_html($group['name']); ?></h3>
                    <?php if ($standings === []) : ?>
                        <p class="tp-empty-sm"><?php esc_html_e('In attesa di risultati.', 'tornei-padel'); ?></p>
                    <?php else : ?>
                        <table class="tp-standings-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php esc_html_e('Squadra', 'tornei-padel'); ?></th>
                                    <th><?php esc_html_e('Pt', 'tornei-padel'); ?></th>
                                    <th>V</th><th>P</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standings as $st) : ?>
                                    <tr>
                                        <td><?php echo esc_html((string) ($st['rank_pos'] ?? '-')); ?></td>
                                        <td><?php echo esc_html($st['team_name']); ?></td>
                                        <td><strong><?php echo esc_html((string) $st['points']); ?></strong></td>
                                        <td><?php echo esc_html((string) $st['wins']); ?></td>
                                        <td><?php echo esc_html((string) $st['losses']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
    <script>
        window.tpLive = <?php echo wp_json_encode([
            'restUrl'      => rest_url('tornei-padel/v1/live/' . $tournament['slug']),
            'matchUrlBase' => home_url('/torneo-live/' . $tournament['slug'] . '/partita/'),
            'pollMs'       => 10000,
        ]); ?>;
    </script>
</main>
<?php
get_footer();

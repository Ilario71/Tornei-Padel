<?php
/**
 * Gestione torneo — admin.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $tournament
 * @var list<array<string, mixed>> $teams
 * @var list<array<string, mixed>> $groups
 * @var list<array<string, mixed>> $group_matches
 * @var list<array<string, mixed>> $knockout_matches
 * @var bool $supports_knockout
 * @var array{gold: list<string>, silver: list<string>} $qualified_preview
 */

if (! defined('ABSPATH')) {
    exit;
}

$tid = (int) $tournament['id'];
$is_champions_padel = TP_Champions_Padel_Service::is_champions_padel($tournament);
?>
<div class="wrap tp-admin-wrap">
    <h1 class="tp-admin-title">
        <span class="tp-icon">🎾</span>
        <?php echo esc_html($tournament['name']); ?>
    </h1>

    <?php if ($is_champions_padel) : ?>
        <p class="description"><?php esc_html_e('Formato Champion\'s Padel: 32 squadre, 8 gironi da 4, ottavi con accoppiamenti UEFA.', 'tornei-padel'); ?></p>
        <div class="tp-card tp-card--inline">
            <?php echo TP_Champions_Padel_Service::rules_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    <?php endif; ?>

    <?php
    if (isset($_GET['tp_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(rawurldecode((string) $_GET['tp_error'])) . '</p></div>';
    } elseif (isset($_GET['knockout_built'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Tabelloni Gold/Silver generati dalle classifiche attuali.', 'tornei-padel') . '</p></div>';
    } elseif (isset($_GET['knockout_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(rawurldecode((string) $_GET['knockout_error'])) . '</p></div>';
    } elseif (isset($_GET['partial_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Parziale salvato: la partita è in LIVE. I set compaiono sulla pagina pubblica; la classifica si aggiorna solo a partita chiusa.', 'tornei-padel') . '</p></div>';
    } elseif (isset($_GET['result_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Risultato definitivo salvato e classifica aggiornata.', 'tornei-padel') . '</p></div>';
        if (! empty($_GET['elo_warn'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html(rawurldecode((string) $_GET['elo_warn'])) . '</p></div>';
        }
    } elseif (isset($_GET['team_limit'])) {
        $max = max(0, (int) ($tournament['num_teams'] ?? 0));
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(
            sprintf(
                /* translators: %d: maximum number of teams */
                __('Limite iscrizioni raggiunto: massimo %d squadre.', 'tornei-padel'),
                $max
            )
        ) . '</p></div>';
    } elseif (isset($_GET['result_error'])) {
        $err = sanitize_key((string) $_GET['result_error']);
        $msg = $err === 'incomplete'
            ? __('Per chiudere la partita servono abbastanza set vincenti secondo le regole del torneo.', 'tornei-padel')
            : __('Inserisci almeno un punteggio set.', 'tornei-padel');
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    } else {
        $notices = ['saved', 'team_saved', 'drawn', 'matches', 'standings'];
        foreach ($notices as $n) {
            if (isset($_GET[$n])) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Operazione completata.', 'tornei-padel') . '</p></div>';
                break;
            }
        }
    }
    ?>

    <section class="tp-card">
        <h2><?php esc_html_e('Dettagli torneo', 'tornei-padel'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tp-form">
            <?php wp_nonce_field('tp_save_tournament'); ?>
            <input type="hidden" name="action" value="tp_save_tournament">
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php esc_html_e('Nome torneo', 'tornei-padel'); ?></label></th>
                    <td>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="regular-text"
                            required
                            value="<?php echo esc_attr((string) $tournament['name']); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th><label for="description"><?php esc_html_e('Descrizione', 'tornei-padel'); ?></label></th>
                    <td>
                        <textarea id="description" name="description" rows="3" class="large-text"><?php
                            echo esc_textarea((string) ($tournament['description'] ?? ''));
                        ?></textarea>
                    </td>
                </tr>
                <?php
                $poster_id = (int) ($tournament['poster_id'] ?? 0);
                include TP_PLUGIN_DIR . 'includes/admin/views/partials/poster-field.php';
                ?>
                <tr>
                    <th><label for="start_date"><?php esc_html_e('Data inizio', 'tornei-padel'); ?></label></th>
                    <td>
                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            required
                            value="<?php echo esc_attr((string) $tournament['start_date']); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th><label for="end_date"><?php esc_html_e('Data fine', 'tornei-padel'); ?></label></th>
                    <td>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            required
                            value="<?php echo esc_attr((string) $tournament['end_date']); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th><label for="num_courts"><?php esc_html_e('N. campi', 'tornei-padel'); ?></label></th>
                    <td>
                        <input
                            type="number"
                            id="num_courts"
                            name="num_courts"
                            min="1"
                            max="16"
                            value="<?php echo esc_attr((string) TP_Court::count($tid)); ?>"
                        >
                        <p class="description"><?php esc_html_e('Al sorteggio gironi le partite vengono assegnate ai campi in modo equilibrato.', 'tornei-padel'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Salva dettagli', 'tornei-padel'), 'secondary'); ?>
        </form>
    </section>

    <div class="tp-admin-grid">
        <section class="tp-card">
            <?php
            $max_teams = max(0, (int) ($tournament['num_teams'] ?? 0));
            $teams_full = $max_teams > 0 && count($teams) >= $max_teams;
            ?>
            <h2><?php esc_html_e('Squadre iscritte', 'tornei-padel'); ?> (<?php echo count($teams); ?><?php echo $max_teams > 0 ? ' / ' . (int) $max_teams : ''; ?>)</h2>

            <?php if ($teams_full) : ?>
                <p class="description"><?php esc_html_e('Limite iscrizioni raggiunto.', 'tornei-padel'); ?></p>
            <?php else : ?>
            <?php
            $tournament_id = $tid;
            $form_mode     = 'save';
            $submit_label  = __('Aggiungi', 'tornei-padel');
            include TP_PLUGIN_DIR . 'includes/admin/views/partials/team-add-form.php';
            ?>
            <?php endif; ?>

            <?php if ($teams !== []) : ?>
                <ul class="tp-team-list">
                    <?php foreach ($teams as $team) : ?>
                        <li>
                            <strong><?php echo esc_html($team['team_name']); ?></strong>
                            <span><?php echo esc_html($team['player1_name'] . ' · ' . $team['player2_name']); ?></span>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tp-inline-delete">
                                <?php wp_nonce_field('tp_delete_team_' . (int) $team['id']); ?>
                                <input type="hidden" name="action" value="tp_delete_team">
                                <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
                                <input type="hidden" name="team_id" value="<?php echo esc_attr((string) $team['id']); ?>">
                                <button type="submit" class="button-link-delete" onclick="return confirm('<?php esc_attr_e('Eliminare questa squadra?', 'tornei-padel'); ?>');">×</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="description"><?php esc_html_e('Aggiungi almeno 2 squadre prima del sorteggio.', 'tornei-padel'); ?></p>
            <?php endif; ?>
        </section>

        <section class="tp-card">
            <h2><?php esc_html_e('Organizzazione', 'tornei-padel'); ?></h2>
            <div class="tp-action-buttons">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('tp_draw_random_' . $tid); ?>
                    <input type="hidden" name="action" value="tp_draw_random">
                    <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
                    <?php submit_button($is_champions_padel ? __('Sorteggia 8 gironi', 'tornei-padel') : __('Sorteggio gironi', 'tornei-padel'), 'primary tp-btn-primary', 'submit', false); ?>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('tp_recalc_standings_' . $tid); ?>
                    <input type="hidden" name="action" value="tp_recalc_standings">
                    <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
                    <?php submit_button(__('Ricalcola classifiche', 'tornei-padel'), 'secondary', 'submit', false); ?>
                </form>
                <?php if ($supports_knockout) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Rigenerare i tabelloni cancellerà le partite eliminatorie esistenti e le ricreerà dalle classifiche attuali.', 'tornei-padel')); ?>');">
                    <?php wp_nonce_field('tp_rebuild_knockout_' . $tid); ?>
                    <input type="hidden" name="action" value="tp_rebuild_knockout">
                    <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
                    <?php submit_button($is_champions_padel ? __('Genera ottavi Champions', 'tornei-padel') : __('Genera tabellone', 'tornei-padel'), 'primary tp-btn-primary', 'submit', false); ?>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($supports_knockout && ($qualified_preview['gold'] !== [] || $qualified_preview['silver'] !== [])) : ?>
                <div class="tp-qualified-preview">
                    <h3><?php esc_html_e('Qualificate (anteprima)', 'tornei-padel'); ?></h3>
                    <?php if ($qualified_preview['gold'] !== []) : ?>
                        <p><strong><?php esc_html_e('Gold:', 'tornei-padel'); ?></strong> <?php echo esc_html(implode(' · ', $qualified_preview['gold'])); ?></p>
                    <?php endif; ?>
                    <?php if ($qualified_preview['silver'] !== []) : ?>
                        <p><strong><?php esc_html_e('Silver:', 'tornei-padel'); ?></strong> <?php echo esc_html(implode(' · ', $qualified_preview['silver'])); ?></p>
                    <?php endif; ?>
                    <p class="description">
                        <?php
                        $mode = (string) ($tournament['qualification_mode'] ?? 'gold_only');
                        if ($mode === 'gold_silver') {
                            esc_html_e('Gold: prime 2 per girone. Silver: 3ª e 4ª per girone.', 'tornei-padel');
                        } else {
                            printf(
                                esc_html__('Gold: prime %d per girone.', 'tornei-padel'),
                                (int) ($tournament['qualification_count'] ?? 2)
                            );
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($groups !== []) : ?>
                <h3><?php esc_html_e('Gironi', 'tornei-padel'); ?></h3>
                <?php foreach ($groups as $group) : ?>
                    <div class="tp-group-box">
                        <h4><?php echo esc_html($group['name']); ?></h4>
                        <ul>
                            <?php foreach (TP_Group::teams_in_group((int) $group['id']) as $gt) : ?>
                                <li><?php echo esc_html($gt['team_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                        $standings = TP_Standings_Service::for_group((int) $group['id']);
                        if ($standings !== []) :
                            ?>
                            <table class="tp-mini-table">
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
                                            <td><?php echo esc_html((string) $st['points']); ?></td>
                                            <td><?php echo esc_html((string) $st['wins']); ?></td>
                                            <td><?php echo esc_html((string) $st['losses']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <section class="tp-card tp-matches-section">
        <h2><?php esc_html_e('Partite gironi', 'tornei-padel'); ?> (<?php echo count($group_matches); ?>)</h2>

        <?php if ($group_matches === []) : ?>
            <p><?php esc_html_e('Esegui il sorteggio gironi per generare le partite.', 'tornei-padel'); ?></p>
        <?php else : ?>
            <?php
            $matches = $group_matches;
            include TP_PLUGIN_DIR . 'includes/admin/views/partials/match-table.php';
            ?>
        <?php endif; ?>
    </section>

    <?php if ($supports_knockout) : ?>
    <section class="tp-card tp-matches-section tp-knockout-section">
        <h2><?php esc_html_e('Tabelloni eliminatori', 'tornei-padel'); ?> (<?php echo count($knockout_matches); ?>)</h2>

        <?php if ($knockout_matches === []) : ?>
            <p><?php esc_html_e('Completa i gironi, poi clicca «Genera tabellone» per creare Gold e (se previsto) Silver.', 'tornei-padel'); ?></p>
        <?php else : ?>
            <?php
            $matches = $knockout_matches;
            include TP_PLUGIN_DIR . 'includes/admin/views/partials/match-table.php';
            ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournaments')); ?>">← <?php esc_html_e('Torna alla lista', 'tornei-padel'); ?></a>
        |
        <a href="<?php echo esc_url(home_url('/torneo-live/' . $tournament['slug'])); ?>" target="_blank">
            <?php esc_html_e('Anteprima LIVE', 'tornei-padel'); ?> ↗
        </a>
    </p>

    <?php if (TP_Roles::can_manage()) : ?>
    <section class="tp-card tp-danger-zone">
        <h2><?php esc_html_e('Zona pericolosa', 'tornei-padel'); ?></h2>
        <p><?php esc_html_e('Elimina definitivamente questo torneo con tutte le squadre, partite, classifiche e tabelloni collegati.', 'tornei-padel'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Eliminare definitivamente il torneo «%s»? Questa azione non può essere annullata.', 'tornei-padel'), $tournament['name'])); ?>');">
            <?php wp_nonce_field('tp_delete_tournament_' . $tid); ?>
            <input type="hidden" name="action" value="tp_delete_tournament">
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
            <?php submit_button(__('Elimina torneo', 'tornei-padel'), 'delete', 'submit', false); ?>
        </form>
    </section>
    <?php endif; ?>
</div>

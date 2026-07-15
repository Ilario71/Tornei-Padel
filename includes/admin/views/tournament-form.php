<?php
/**
 * Form creazione torneo — admin.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed>|null $tournament
 * @var string $action
 */

if (! defined('ABSPATH')) {
    exit;
}

$formulas = TP_Tournament::formula_options();
?>
<div class="wrap tp-admin-wrap">
    <h1 class="tp-admin-title">
        <span class="tp-icon">🎾</span>
        <?php esc_html_e('Nuovo torneo', 'tornei-padel'); ?>
    </h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tp-form">
        <?php wp_nonce_field('tp_save_tournament'); ?>
        <input type="hidden" name="action" value="tp_save_tournament">

        <div class="tp-card">
            <h2><?php esc_html_e('Informazioni generali', 'tornei-padel'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php esc_html_e('Nome torneo', 'tornei-padel'); ?></label></th>
                    <td><input type="text" id="name" name="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="description"><?php esc_html_e('Descrizione', 'tornei-padel'); ?></label></th>
                    <td><textarea id="description" name="description" rows="4" class="large-text"></textarea></td>
                </tr>
                <?php
                $poster_id = 0;
                include TP_PLUGIN_DIR . 'includes/admin/views/partials/poster-field.php';
                ?>
                <tr>
                    <th><label for="start_date"><?php esc_html_e('Data inizio', 'tornei-padel'); ?></label></th>
                    <td><input type="date" id="start_date" name="start_date" required value="<?php echo esc_attr(gmdate('Y-m-d')); ?>"></td>
                </tr>
                <tr>
                    <th><label for="end_date"><?php esc_html_e('Data fine', 'tornei-padel'); ?></label></th>
                    <td><input type="date" id="end_date" name="end_date" required value="<?php echo esc_attr(gmdate('Y-m-d')); ?>"></td>
                </tr>
                <tr>
                    <th><label for="formula"><?php esc_html_e('Formato', 'tornei-padel'); ?></label></th>
                    <td>
                        <select id="formula" name="formula" data-tp-formula-select>
                            <?php foreach ($formulas as $f) : ?>
                                <option value="<?php echo esc_attr($f['id']); ?>"><?php echo esc_html($f['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" data-tp-formula-desc></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="tp-card" data-tp-structure-fields>
            <h2><?php esc_html_e('Struttura', 'tornei-padel'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="num_teams"><?php esc_html_e('N. squadre', 'tornei-padel'); ?></label></th>
                    <td><input type="number" id="num_teams" name="num_teams" min="2" max="64" value="8"></td>
                </tr>
                <tr data-tp-groups-row>
                    <th><label for="num_groups"><?php esc_html_e('N. gironi', 'tornei-padel'); ?></label></th>
                    <td><input type="number" id="num_groups" name="num_groups" min="1" max="8" value="2"></td>
                </tr>
                <tr data-tp-brackets-row>
                    <th><label for="qualification_mode"><?php esc_html_e('Tabelloni', 'tornei-padel'); ?></label></th>
                    <td>
                        <select id="qualification_mode" name="qualification_mode">
                            <option value="gold_only"><?php esc_html_e('Solo Gold', 'tornei-padel'); ?></option>
                            <option value="gold_silver"><?php esc_html_e('Gold + Silver', 'tornei-padel'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr data-tp-brackets-row>
                    <th><label for="qualification_count"><?php esc_html_e('Qualificate per girone', 'tornei-padel'); ?></label></th>
                    <td><input type="number" id="qualification_count" name="qualification_count" min="1" max="4" value="2"></td>
                </tr>
                <tr>
                    <th><label for="num_courts"><?php esc_html_e('N. campi', 'tornei-padel'); ?></label></th>
                    <td>
                        <input type="number" id="num_courts" name="num_courts" min="1" max="16" value="2">
                        <p class="description"><?php esc_html_e('Le partite dei gironi verranno ripartite in modo equo sui campi disponibili.', 'tornei-padel'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="tp-card">
            <h2><?php esc_html_e('Regole partita', 'tornei-padel'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="sets_to_win"><?php esc_html_e('Set per vincere', 'tornei-padel'); ?></label></th>
                    <td>
                        <select id="sets_to_win" name="sets_to_win">
                            <option value="1"><?php esc_html_e('Al meglio di 1', 'tornei-padel'); ?></option>
                            <option value="2" selected><?php esc_html_e('Al meglio di 3 (2 set)', 'tornei-padel'); ?></option>
                            <option value="3"><?php esc_html_e('Al meglio di 5 (3 set)', 'tornei-padel'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="games_per_set"><?php esc_html_e('Game per set', 'tornei-padel'); ?></label></th>
                    <td><input type="number" id="games_per_set" name="games_per_set" min="4" max="9" value="6"></td>
                </tr>
                <tr>
                    <th><label for="match_duration"><?php esc_html_e('Durata slot (min)', 'tornei-padel'); ?></label></th>
                    <td><input type="number" id="match_duration" name="match_duration" min="15" value="60"></td>
                </tr>
            </table>
        </div>

        <div class="tp-card" data-tp-teams-create>
            <h2><?php esc_html_e('Squadre (opzionale)', 'tornei-padel'); ?></h2>
            <p class="description">
                <?php esc_html_e('Aggiungi le coppie ora o dopo dalla gestione del torneo. Inizia a digitare per cercare i giocatori registrati.', 'tornei-padel'); ?>
            </p>

            <?php
            $tournament_id = 0;
            $form_mode     = 'stage';
            $submit_label  = __('Aggiungi alla lista', 'tornei-padel');
            include TP_PLUGIN_DIR . 'includes/admin/views/partials/team-add-form.php';
            ?>

            <ul class="tp-staged-teams" data-tp-staged-teams hidden></ul>
            <div data-tp-staged-teams-inputs hidden aria-hidden="true"></div>
        </div>

        <?php submit_button(__('Crea torneo', 'tornei-padel'), 'primary tp-btn-primary'); ?>
    </form>
</div>

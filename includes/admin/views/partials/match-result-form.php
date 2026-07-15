<?php
/**
 * Form inserimento risultato partita — admin partial.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $match
 * @var int $tid
 * @var bool $live_mode
 */

if (! defined('ABSPATH')) {
    exit;
}

$sets = TP_Match::sets_for_match((int) $match['id']);
$set_values = [];
foreach ($sets as $set) {
    $set_values[(int) $set['set_number']] = $set;
}
$tournament_row = TP_Tournament::find($tid);
$is_champions = $tournament_row !== null && TP_Champions_Padel_Service::is_champions_padel($tournament_row);
?>
<?php if ($is_champions) : ?>
<p class="description tp-champions-scoring-hint"><?php echo esc_html(TP_Champions_Padel_Service::scoring_hint()); ?></p>
<?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tp-result-form">
    <?php wp_nonce_field('tp_save_match_' . (int) $match['id']); ?>
    <input type="hidden" name="action" value="tp_save_match_result">
    <input type="hidden" name="match_id" value="<?php echo esc_attr((string) $match['id']); ?>">
    <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tid); ?>">
    <?php for ($si = 1; $si <= 3; $si++) :
        $has_set = isset($set_values[$si]);
        $s1_val  = $has_set ? (int) $set_values[$si]['team1_score'] : null;
        $s2_val  = $has_set ? (int) $set_values[$si]['team2_score'] : null;
        $stb_on  = $has_set && ! empty($set_values[$si]['is_super_tiebreak']);
        ?>
    <span class="tp-set-input">
        <span class="tp-set-input__label">S<?php echo (int) $si; ?></span>
        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="tp-score-input"
            name="set<?php echo (int) $si; ?>_team1"
            value="<?php echo $s1_val !== null ? esc_attr((string) $s1_val) : ''; ?>" placeholder="0">
        <span class="tp-set-input__sep">-</span>
        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" class="tp-score-input"
            name="set<?php echo (int) $si; ?>_team2"
            value="<?php echo $s2_val !== null ? esc_attr((string) $s2_val) : ''; ?>" placeholder="0">
        <?php if ($is_champions) : ?>
        <label class="tp-set-stb"><input type="checkbox" name="set<?php echo (int) $si; ?>_stb" value="1"<?php echo $stb_on ? ' checked' : ''; ?>> STB</label>
        <?php endif; ?>
    </span>
    <?php endfor; ?>
    <button type="submit" name="save_type" value="partial" class="button button-secondary">
        <?php echo $live_mode ? esc_html__('Aggiorna LIVE', 'tornei-padel') : esc_html__('Salva parziale (LIVE)', 'tornei-padel'); ?>
    </button>
    <button type="submit" name="save_type" value="final" class="button button-primary">
        <?php esc_html_e('Chiudi partita', 'tornei-padel'); ?>
    </button>
</form>

<?php
/**
 * Form aggiunta squadra con autocomplete giocatori.
 *
 * @package TorneiPadel
 *
 * @var int|null    $tournament_id  ID torneo (null in creazione con staging).
 * @var string     $form_mode      'save' salva subito, 'stage' accumula in lista.
 * @var string     $submit_label   Etichetta pulsante.
 */

if (! defined('ABSPATH')) {
    exit;
}

$tournament_id = isset($tournament_id) ? (int) $tournament_id : 0;
$form_mode     = isset($form_mode) ? (string) $form_mode : 'save';
$submit_label  = isset($submit_label) ? (string) $submit_label : __('Aggiungi', 'tornei-padel');
$is_stage      = $form_mode === 'stage';
$tag           = $is_stage ? 'div' : 'form';
?>
<<?php echo $tag; ?>
    <?php if (! $is_stage) : ?>
    method="post"
    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    <?php endif; ?>
    class="tp-inline-form tp-team-add-form"
    <?php echo $is_stage ? ' data-tp-team-stage-form' : ''; ?>
>
    <?php if (! $is_stage) : ?>
        <?php wp_nonce_field('tp_save_team_' . $tournament_id); ?>
        <input type="hidden" name="action" value="tp_save_team">
        <input type="hidden" name="tournament_id" value="<?php echo esc_attr((string) $tournament_id); ?>">
    <?php endif; ?>

    <div class="tp-player-autocomplete" data-tp-player-autocomplete>
        <input
            type="text"
            name="<?php echo $is_stage ? '' : 'player1_name'; ?>"
            <?php echo $is_stage ? 'data-tp-field="player1_name"' : ''; ?>
            class="tp-player-autocomplete__input"
            placeholder="<?php esc_attr_e('Giocatore 1', 'tornei-padel'); ?>"
            autocomplete="off"
            autocapitalize="words"
            <?php echo $is_stage ? '' : 'required'; ?>
        >
        <ul class="tp-player-autocomplete__list" role="listbox" hidden></ul>
    </div>

    <div class="tp-player-autocomplete" data-tp-player-autocomplete>
        <input
            type="text"
            name="<?php echo $is_stage ? '' : 'player2_name'; ?>"
            <?php echo $is_stage ? 'data-tp-field="player2_name"' : ''; ?>
            class="tp-player-autocomplete__input"
            placeholder="<?php esc_attr_e('Giocatore 2', 'tornei-padel'); ?>"
            autocomplete="off"
            autocapitalize="words"
            <?php echo $is_stage ? '' : 'required'; ?>
        >
        <ul class="tp-player-autocomplete__list" role="listbox" hidden></ul>
    </div>

    <input
        type="text"
        name="<?php echo $is_stage ? '' : 'team_name'; ?>"
        <?php echo $is_stage ? 'data-tp-field="team_name"' : ''; ?>
        placeholder="<?php esc_attr_e('Nome coppia (opz.)', 'tornei-padel'); ?>"
    >

    <?php if ($is_stage) : ?>
        <button type="button" class="button" data-tp-stage-team><?php echo esc_html($submit_label); ?></button>
    <?php else : ?>
        <?php submit_button($submit_label, 'secondary', 'submit', false); ?>
    <?php endif; ?>
</<?php echo $tag; ?>>

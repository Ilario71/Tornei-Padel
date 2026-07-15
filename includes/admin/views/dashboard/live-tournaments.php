<?php
/**
 * Widget bacheca — tornei LIVE.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($tournaments === []) : ?>
    <p class="tp-dash-empty">
        <?php esc_html_e('Nessun torneo con LIVE attivo. Abilitalo dalla scheda del torneo.', 'tornei-padel'); ?>
    </p>
<?php else : ?>
    <ul class="tp-dash-list tp-dash-list--compact">
        <?php foreach ($tournaments as $tournament) : ?>
            <?php
            $id   = (int) ($tournament['id'] ?? 0);
            $slug = (string) ($tournament['slug'] ?? '');
            ?>
            <li class="tp-dash-list__item">
                <div class="tp-dash-list__head">
                    <strong><?php echo esc_html((string) ($tournament['name'] ?? '')); ?></strong>
                </div>
                <p class="tp-dash-list__links">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-edit&id=' . $id)); ?>">
                        <?php esc_html_e('Gestisci', 'tornei-padel'); ?>
                    </a>
                    <?php if ($slug !== '') : ?>
                        <span aria-hidden="true"> · </span>
                        <a href="<?php echo esc_url(home_url('/torneo-live/' . $slug)); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Pagina LIVE', 'tornei-padel'); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

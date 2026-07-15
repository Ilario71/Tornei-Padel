<?php
/**
 * Campo locandina torneo — media library WordPress.
 *
 * @package TorneiPadel
 *
 * @var int $poster_id
 */

if (! defined('ABSPATH')) {
    exit;
}

$poster_id  = (int) ($poster_id ?? 0);
$poster_url = $poster_id > 0 ? wp_get_attachment_image_url($poster_id, 'medium') : '';
?>
<tr>
    <th scope="row">
        <label for="tp_poster_id"><?php esc_html_e('Locandina', 'tornei-padel'); ?></label>
    </th>
    <td>
        <div class="tp-poster-field" data-tp-poster-field>
            <input type="hidden" name="poster_id" id="tp_poster_id" value="<?php echo esc_attr((string) $poster_id); ?>">
            <div class="tp-poster-field__preview" data-tp-poster-preview>
                <?php if (is_string($poster_url) && $poster_url !== '') : ?>
                    <img src="<?php echo esc_url($poster_url); ?>" alt="">
                <?php endif; ?>
            </div>
            <p class="tp-poster-field__actions">
                <button type="button" class="button" data-tp-poster-select>
                    <?php esc_html_e('Seleziona immagine', 'tornei-padel'); ?>
                </button>
                <button
                    type="button"
                    class="button"
                    data-tp-poster-remove
                    <?php echo $poster_id > 0 ? '' : 'hidden'; ?>
                >
                    <?php esc_html_e('Rimuovi', 'tornei-padel'); ?>
                </button>
            </p>
            <p class="description">
                <?php esc_html_e('Immagine promozionale mostrata nelle card del sito (formato verticale consigliato).', 'tornei-padel'); ?>
            </p>
        </div>
    </td>
</tr>

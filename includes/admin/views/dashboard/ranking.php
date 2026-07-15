<?php
/**
 * Widget bacheca — top ranking.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $players
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<?php if ($players === []) : ?>
    <p class="tp-dash-empty"><?php esc_html_e('Il ranking si popola dopo i primi risultati inseriti.', 'tornei-padel'); ?></p>
<?php else : ?>
    <ol class="tp-dash-ranking">
        <?php foreach ($players as $index => $player) : ?>
            <?php
            $elo = (int) ($player['elo_rating'] ?? TP_Ranking_Player::START_ELO);
            $level = TP_Ranking_Level_Service::from_elo($elo);
            ?>
            <li class="tp-dash-ranking__item">
                <span class="tp-dash-ranking__pos"><?php echo esc_html((string) ($index + 1)); ?></span>
                <span class="tp-dash-ranking__name"><?php echo esc_html((string) ($player['display_name'] ?? '')); ?></span>
                <span class="tp-dash-ranking__elo"><?php echo esc_html((string) $elo); ?></span>
                <span class="tp-dash-ranking__badge tp-dash-ranking__badge--<?php echo esc_attr((string) ($level['slug'] ?? 'bronze')); ?>">
                    <?php echo esc_html((string) ($level['label'] ?? '')); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="tp-dash-foot">
        <a href="<?php echo esc_url(home_url('/ranking/')); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Vedi ranking completo', 'tornei-padel'); ?>
        </a>
    </p>
<?php endif; ?>

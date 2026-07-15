<?php

/**

 * Shortcode ranking ELO.

 *

 * @package TorneiPadel

 *

 * @var list<array<string, mixed>> $rows

 * @var list<array<string, mixed>> $tiers

 */



if (! defined('ABSPATH')) {

    exit;

}



$start_elo = TP_Ranking_Player::START_ELO;

?>

<div class="tp-shortcode tp-ranking">

    <h3><?php esc_html_e('Ranking giocatori', 'tornei-padel'); ?></h3>

    <p class="tp-ranking__lead">

        <?php

        printf(

            /* translators: %d: starting ELO points */

            esc_html__('Classifica ELO individuale. Punteggio iniziale: %d · si aggiorna dopo ogni match concluso.', 'tornei-padel'),

            (int) $start_elo

        );

        ?>

    </p>



    <section class="tp-rank-levels-legend" aria-labelledby="tp-rank-levels-heading">

        <h4 id="tp-rank-levels-heading" class="tp-rank-levels-legend__title">

            <?php esc_html_e('Livelli ELO', 'tornei-padel'); ?>

        </h4>

        <p class="tp-rank-levels-legend__lead">

            <?php esc_html_e('Il badge accanto al nome indica il livello competitivo del giocatore.', 'tornei-padel'); ?>

        </p>

        <ul class="tp-rank-levels-grid">

            <?php foreach ($tiers as $tier) : ?>

                <li class="tp-rank-levels-grid__item">

                    <?php

                    $elo = (int) $tier['min'];

                    include TP_PLUGIN_DIR . 'includes/public/views/partials/ranking-level-badge.php';

                    ?>

                    <span class="tp-rank-levels-grid__range">

                        <?php echo esc_html($tier['range_label']); ?> pt

                    </span>

                    <span class="tp-rank-levels-grid__meaning">

                        <?php echo esc_html($tier['meaning']); ?>

                    </span>

                </li>

            <?php endforeach; ?>

        </ul>

    </section>



    <?php if ($rows === []) : ?>

        <p class="tp-empty"><?php esc_html_e('Il ranking verrà popolato dopo le prime partite.', 'tornei-padel'); ?></p>

    <?php else : ?>

        <div class="tp-ranking__table-wrap">

            <table class="tp-standings-table tp-ranking-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th><?php esc_html_e('Giocatore', 'tornei-padel'); ?></th>

                        <th><?php esc_html_e('ELO', 'tornei-padel'); ?></th>

                        <th><?php esc_html_e('Match', 'tornei-padel'); ?></th>

                        <th>V</th>

                        <th>P</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($rows as $i => $row) : ?>

                        <tr>

                            <td><?php echo esc_html((string) ($i + 1)); ?></td>

                            <td class="tp-ranking-table__player">

                                <?php

                                $name = (string) $row['display_name'];

                                $elo  = (int) $row['elo_rating'];

                                include TP_PLUGIN_DIR . 'includes/public/views/partials/ranking-player-name.php';

                                ?>

                            </td>

                            <td><strong><?php echo esc_html((string) $elo); ?></strong></td>

                            <td><?php echo esc_html((string) $row['matches_played']); ?></td>

                            <td><?php echo esc_html((string) $row['wins']); ?></td>

                            <td><?php echo esc_html((string) $row['losses']); ?></td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <p class="tp-ranking__footnote">

            <?php esc_html_e("In doppio l'ELO di squadra è la media dei due giocatori; vittoria o sconfitta aggiorna entrambi allo stesso modo.", 'tornei-padel'); ?>

        </p>

    <?php endif; ?>

</div>


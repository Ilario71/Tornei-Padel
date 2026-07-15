<?php
/**
 * Metadati plugin in elenco Plugin e modale "Visualizza i dettagli".
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class TP_Plugin_Info
{
    private const SLUG = 'tornei-padel';

    private const HOMEPAGE = 'https://ilawebapp.com/wppadel';

    public static function init(): void
    {
        add_filter('plugin_row_meta', [self::class, 'plugin_row_meta'], 10, 2);
        add_filter('plugins_api', [self::class, 'plugins_api'], 20, 3);
    }

    /**
     * Aggiunge il link "Visualizza i dettagli" nella riga del plugin.
     *
     * @param string[] $plugin_meta
     * @return string[]
     */
    public static function plugin_row_meta(array $plugin_meta, string $plugin_file): array
    {
        if ($plugin_file !== plugin_basename(TP_PLUGIN_FILE)) {
            return $plugin_meta;
        }

        if (! current_user_can('install_plugins') && ! current_user_can('update_plugins')) {
            return $plugin_meta;
        }

        $url = self_admin_url(
            'plugin-install.php?tab=plugin-information&plugin=' . self::SLUG . '&TB_iframe=true&width=600&height=550'
        );

        $plugin_meta[] = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
            esc_url($url),
            /* translators: %s: plugin name */
            esc_attr(sprintf(__('More information about %s'), 'Tornei Padel')),
            esc_attr('Tornei Padel'),
            __('View details')
        );

        return $plugin_meta;
    }

    /**
     * Fornisce i dati della modale dettagli (plugin non su wordpress.org).
     *
     * @param false|object|array $result
     * @return false|object|array
     */
    public static function plugins_api($result, string $action, object $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (empty($args->slug) || $args->slug !== self::SLUG) {
            return $result;
        }

        $info = self::get_plugin_information();

        return (object) $info;
    }

    /**
     * @return array<string, mixed>
     */
    private static function get_plugin_information(): array
    {
        $author = sprintf(
            '<a href="%s">%s</a>',
            esc_url(self::HOMEPAGE),
            esc_html('Tornei Padel')
        );

        return [
            'name'           => 'Tornei Padel',
            'slug'           => self::SLUG,
            'version'        => TP_VERSION,
            'author'         => $author,
            'author_profile' => self::HOMEPAGE,
            'homepage'       => self::HOMEPAGE,
            'requires'       => '6.0',
            'tested'         => '6.8',
            'requires_php'   => '8.1',
            'last_updated'   => '2026-07-15',
            'download_link'  => self::HOMEPAGE,
            'trunk'          => self::HOMEPAGE,
            'short_description' => 'Gestione completa dei tornei di padel: gironi, calendario, classifiche, live e ranking.',
            'sections'       => [
                'description'  => self::section_description(),
                'installation' => self::section_installation(),
                'faq'          => self::section_faq(),
                'changelog'    => self::section_changelog(),
            ],
            'banners'        => [],
            'icons'          => [],
        ];
    }

    private static function section_description(): string
    {
        return '<p><strong>Tornei Padel</strong> è il plugin WordPress per organizzare e gestire tornei di padel: dalla creazione dell\'evento fino al live sui campi e al ranking dei giocatori.</p>
<ul>
<li>Creazione e gestione tornei (gironi, match semplici, campionato, Champion\'s Padel)</li>
<li>Squadre/coppie, sorteggio gironi e generazione partite</li>
<li>Classifiche aggiornate e inserimento risultati (anche parziali in LIVE)</li>
<li>Pagine pubbliche: lista tornei, hub live e ranking ELO</li>
<li>Shortcode pronti all\'uso e polling REST per il live</li>
<li>Ruoli dedicati: organizzatore tornei e giocatore padel</li>
</ul>
<p>Sito del plugin: <a href="' . esc_url(self::HOMEPAGE) . '">' . esc_html(self::HOMEPAGE) . '</a></p>';
    }

    private static function section_installation(): string
    {
        return '<ol>
<li>Carica la cartella <code>tornei-padel</code> in <code>wp-content/plugins/</code> oppure installa lo ZIP da <em>Plugin → Aggiungi nuovo → Carica plugin</em>.</li>
<li>Attiva <strong>Tornei Padel</strong> dalla schermata Plugin.</li>
<li>All\'attivazione vengono create le tabelle (<code>wp_tp_*</code>) e i ruoli dedicati.</li>
<li>Vai in <em>Impostazioni → Permalink</em> e salva per aggiornare le rewrite rules.</li>
<li>(Consigliato) Attiva il tema <strong>Padel Club</strong> per le pagine Tornei, Live e Ranking già configurate.</li>
</ol>
<p>Shortcode disponibili:</p>
<ul>
<li><code>[tornei_padel_lista]</code> — griglia tornei</li>
<li><code>[tornei_padel_live]</code> — hub tornei in diretta</li>
<li><code>[tornei_padel_ranking]</code> — classifica ELO giocatori</li>
</ul>';
    }

    private static function section_faq(): string
    {
        return '<h4>Serve un tema specifico?</h4>
<p>Il plugin funziona con qualsiasi tema. Con il tema <strong>Padel Club</strong> le pagine pubbliche vengono create automaticamente.</p>
<h4>Come vedo il live di un torneo?</h4>
<p>Dopo aver creato un torneo, apri <code>/torneo-live/{slug-torneo}</code> oppure usa lo shortcode hub live.</p>
<h4>Chi può gestire i tornei?</h4>
<p>Amministratori e utenti con il ruolo <strong>Organizzatore tornei</strong> (<code>tp_organizer</code>).</p>
<h4>Dove trovo aggiornamenti e informazioni?</h4>
<p>Sul sito del plugin: <a href="' . esc_url(self::HOMEPAGE) . '">' . esc_html(self::HOMEPAGE) . '</a></p>';
    }

    private static function section_changelog(): string
    {
        return '<h4>0.2.7</h4>
<ul>
<li>Link sito plugin aggiornato a ' . esc_html(self::HOMEPAGE) . '</li>
<li>Scheda dettagli plugin disponibile da Plugin → Visualizza i dettagli</li>
</ul>
<h4>0.2.x</h4>
<ul>
<li>Gestione tornei, gironi, classifiche, live e ranking ELO</li>
<li>Shortcode frontend e REST per il polling live</li>
</ul>';
    }
}

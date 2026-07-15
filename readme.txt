=== Tornei Padel ===
Contributors: torneipadel
Donate link: https://ilawebapp.com/wppadel
Tags: padel, tournaments, ranking, live, sports
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestione completa dei tornei di padel: gironi, calendario, classifiche, live e ranking.

== Description ==

**Tornei Padel** è il plugin WordPress per organizzare e gestire tornei di padel: dalla creazione dell'evento fino al live sui campi e al ranking dei giocatori.

* Creazione e gestione tornei (gironi, match semplici, campionato, Champion's Padel)
* Squadre/coppie, sorteggio gironi e generazione partite
* Classifiche aggiornate e inserimento risultati (anche parziali in LIVE)
* Pagine pubbliche: lista tornei, hub live e ranking ELO
* Shortcode pronti all'uso e polling REST per il live
* Ruoli dedicati: organizzatore tornei e giocatore padel

Sito del plugin: https://ilawebapp.com/wppadel

== Installation ==

1. Carica la cartella `tornei-padel` in `wp-content/plugins/` oppure installa lo ZIP da Plugin → Aggiungi nuovo → Carica plugin.
2. Attiva **Tornei Padel** dalla schermata Plugin.
3. All'attivazione vengono create le tabelle (`wp_tp_*`) e i ruoli dedicati.
4. Vai in Impostazioni → Permalink e salva per aggiornare le rewrite rules.
5. (Consigliato) Attiva il tema **Padel Club** per le pagine Tornei, Live e Ranking già configurate.

Shortcode disponibili:

* `[tornei_padel_lista]` — griglia tornei
* `[tornei_padel_live]` — hub tornei in diretta
* `[tornei_padel_ranking]` — classifica ELO giocatori

== Frequently Asked Questions ==

= Serve un tema specifico? =

Il plugin funziona con qualsiasi tema. Con il tema Padel Club le pagine pubbliche vengono create automaticamente.

= Come vedo il live di un torneo? =

Dopo aver creato un torneo, apri `/torneo-live/{slug-torneo}` oppure usa lo shortcode hub live.

= Chi può gestire i tornei? =

Amministratori e utenti con il ruolo Organizzatore tornei (`tp_organizer`).

= Dove trovo aggiornamenti e informazioni? =

Sul sito del plugin: https://ilawebapp.com/wppadel

== Changelog ==

= 0.2.7 =
* Link sito plugin aggiornato a https://ilawebapp.com/wppadel
* Scheda dettagli plugin disponibile da Plugin → Visualizza i dettagli

= 0.2.x =
* Gestione tornei, gironi, classifiche, live e ranking ELO
* Shortcode frontend e REST per il polling live

== Upgrade Notice ==

= 0.2.7 =
Aggiorna i metadati del plugin e la scheda "Visualizza i dettagli".

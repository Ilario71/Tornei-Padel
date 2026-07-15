<?php
/**
 * Plugin Name:       Tornei Padel
 * Plugin URI:        https://ilawebapp.com/wppadel
 * Description:       Gestione completa dei tornei di padel: gironi, calendario, classifiche, live e ranking.
 * Version:           0.2.7
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Tornei Padel
 * Author URI:        https://ilawebapp.com/wppadel
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tornei-padel
 *
 * @package TorneiPadel
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('TP_VERSION', '0.2.7');
define('TP_PLUGIN_FILE', __FILE__);
define('TP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TP_DB_VERSION', '0.1.4');

require_once TP_PLUGIN_DIR . 'includes/class-helpers.php';
require_once TP_PLUGIN_DIR . 'includes/class-activator.php';
require_once TP_PLUGIN_DIR . 'includes/class-plugin-info.php';
require_once TP_PLUGIN_DIR . 'includes/class-roles.php';
require_once TP_PLUGIN_DIR . 'includes/class-database.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-tournament.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-court.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-team.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-match.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-group.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-ranking-player.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-elo-history.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-standings-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-champions-padel-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-champions-league-knockout-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-scoring-rules-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-group-draw-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-match-result-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-knockout-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-elo-rating-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-ranking-level-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-player-search-service.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-player-user-resolver.php';
require_once TP_PLUGIN_DIR . 'includes/models/class-player-notification.php';
require_once TP_PLUGIN_DIR . 'includes/services/class-player-notification-hooks.php';
require_once TP_PLUGIN_DIR . 'includes/admin/class-admin.php';
require_once TP_PLUGIN_DIR . 'includes/public/class-public.php';
require_once TP_PLUGIN_DIR . 'includes/public/class-shortcodes.php';
require_once TP_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
require_once TP_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['TP_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['TP_Activator', 'deactivate']);

TP_Plugin::instance()->init();

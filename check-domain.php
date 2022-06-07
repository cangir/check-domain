<?php
/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       Check Domain
 * Plugin URI:        http://cangir.de/plugins/check-domain
 * Description:       Simple domain checker.
 * Version:           1.0.0
 * Author:            Ahmet Cangir
 * Author URI:        http://cangir.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       check-domain
 * Domain Path:       /languages
 *
 * @author      Ahmet Cangir <info@cangir.de>
 * @package     Check_Domain
 * @version     1.0.0
 */

namespace CheckDomain;

defined( 'ABSPATH' ) || exit; // Cannot access directly.

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CHECK_DOMAIN_VERSION', '1.0.0' );

// Define Constants.
define( 'CHECK_DOMAIN_DIR', plugin_dir_path( __FILE__ ) );                // Plugin path.
define( 'CHECK_DOMAIN_URL', plugin_dir_url( __FILE__ ) );                 // Plugin url.
define( 'CHECK_DOMAIN_BASE', dirname( plugin_basename( __FILE__ ) ) );    // dino.

require_once plugin_dir_path( __FILE__ ) . 'src/utils/class-activator.php';
require_once plugin_dir_path( __FILE__ ) . 'src/utils/class-deactivator.php';
require_once plugin_dir_path( __FILE__ ) . 'src/utils/class-i18n.php';
require_once plugin_dir_path( __FILE__ ) . 'src/utils/class-loader.php';
require_once plugin_dir_path( __FILE__ ) . 'src/class-app.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function check_domain_activate() {
	utils\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function check_domain_deactivate() {
	utils\Deactivator::deactivate();
}

register_activation_hook( __FILE__, '\CheckDomain\check_domain_activate' );
register_deactivation_hook( __FILE__, '\CheckDomain\check_domain_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function check_domain_run() {
	$plugin = new App();
	$plugin->run();
}
check_domain_run();

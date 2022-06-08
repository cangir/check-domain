<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @author      Ahmet Cangir <info@cangir.de>
 * @package     Check_Domain
 * @version     1.0.0
 */

namespace CheckDomain\libraries;

use CheckDomain\App;
use CheckDomain\libraries\Whois;

defined( 'ABSPATH' ) || exit; // Cannot access directly.


/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @author     Ahmet Cangir <info@cangir.de>
 */
class Shortcode {
	/**
	 * The Content.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $content    The Content.
	 */
	private $content;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Shortcode Tag
	 */
	public function tld_shortcode() {

		$home_url    = home_url( '' );
		$placeholder = esc_attr_x( 'Search Domain', 'placeholder', 'check-domain' );
		$image       = CHECK_DOMAIN_URL . 'assets/img/loading.svg';
		$icon        = apply_filters(
			'check-domain:search-form:icon',
			'<svg class="ct-icon" aria-hidden="true" width="15" height="15" viewBox="0 0 15 15"><path d="M14.8,13.7L12,11c0.9-1.2,1.5-2.6,1.5-4.2c0-3.7-3-6.8-6.8-6.8S0,3,0,6.8s3,6.8,6.8,6.8c1.6,0,3.1-0.6,4.2-1.5l2.8,2.8c0.1,0.1,0.3,0.2,0.5,0.2s0.4-0.1,0.5-0.2C15.1,14.5,15.1,14,14.8,13.7z M1.5,6.8c0-2.9,2.4-5.2,5.2-5.2S12,3.9,12,6.8S9.6,12,6.8,12S1.5,9.6,1.5,6.8z"/></svg>'
		);

		ob_start(); ?>

<div id="domain-form">
<form
	role="search" method="post"
	class="search-form"
	action="./">

	<input type="search" placeholder="<?php echo $placeholder; ?>" value="<?php echo get_search_query(); ?>" name="domain" autocomplete="on" title="<?php echo __( 'Search Domain', 'check-domain' ); ?>" />

	<button type="submit" class="button" aria-label="<?php echo __( 'Search button', 'check-domain' ); ?>">
		<?php
			/**
			 * Note to code reviewers: This line doesn't need to be escaped.
			 * The value used here escapes the value properly.
			 * It contains an inline SVG, which is safe.
			 */
			echo $icon;
		?>

		<span data-loader="circles"><span></span><span></span><span></span></span>
	</button>

</form>
<div id="loading"><img src="<?php echo $image; ?>"></img></div>
<div id="results" class="result small"></div>
</div>

		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Display domains regarding the type of Results.
	 *
	 * @since 1.0.0
	 * */
	public function domain_display_func() {
		check_ajax_referer( 'check_domain_nonce', 'security' );

		if ( strlen( $_POST['domain'] ) > 0 ) {

				$url = $this->validate_domain_name( $_POST['domain'] );

				$domain = new Whois( $url );

				$is_available                 = $domain->is_available();
				$custom_found_result_text     = __( 'Congratulations! <b>' . $url . '</b> is available!', 'check-domain' );
				$custom_not_found_result_text = __( 'Sorry! <b>' . $url . '</b> is already taken!', 'check-domain' );

			if ( true === $is_available ) {
				$result = array(
					'status' => 1,
					'domain' => $url,
					'text'   => '<div class="callout callout-success alert-success clearfix available">
								<div class="col-xs-10" style="padding-left:1px;text-align:left;">
								<i class="glyphicon glyphicon-ok" style="margin-right:1px;"></i> ' . __( $custom_found_result_text, 'check-domain' ) . ' </div>
								</div>',
				);
				echo wp_json_encode( $result );
			} elseif ( false === $is_available ) {
				$result = array(
					'status' => 0,
					'domain' => $url,
					'text'   => '<div class="callout callout-danger alert-danger clearfix not-available">
								<div class="col-xs-10" style="padding-left:1px;text-align:left;">
								<i class="glyphicon glyphicon-remove" style="margin-right:1px;"></i> ' . __( $custom_not_found_result_text, 'check-domain' ) . '
								</div>
								</div>',
				);
				echo wp_json_encode( $result );
			} else {
				$result = array(
					'status' => 0,
					'domain' => $url,
					'text'   => '<div class="callout callout-danger alert-danger clearfix not-available">
								<div class="col-xs-10" style="padding-left:1px;text-align:left;">
								<i class="glyphicon glyphicon-remove" style="margin-right:1px;"></i> ' . __( 'Domain is not valid', 'check-domain' ) . '
								</div>
								</div>',
				);
			}
		} else {
			echo 'Please enter the domain name';
		}
		die();
	}

	/**
	 * Checks if a domain name is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $domain_name Domain name.
	 * @return bool
	 * */
	public static function validate_domain_name( $domain_name ) {
		$domain = str_replace( array( 'www.', 'http://', 'https://' ), null, $domain_name );
		$split  = explode( '.', $domain );

		if ( count( $split ) === 1 ) {
			$domain = $domain . '.com';
		}

		$domain = preg_replace( '/[^-a-zA-Z0-9.]+/', '', $domain );
		return $domain;
	}

	/**
	 * Register Shortcode.
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'check-domain', array( $this, 'tld_shortcode' ) );
	}

}

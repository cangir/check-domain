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

defined( 'ABSPATH' ) || exit; // Cannot access directly.

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @author     Ahmet Cangir <info@cangir.de>
 */
class Domain_Availability {
	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      boolean    $error_reporting    The current version of the plugin.
	 */
	private $error_reporting = true;

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
	 * Show Error
	 *
	 * @return bool, true to show db errors or false otherwise
	 */
	public function show_error():bool {
		return $this->error_reporting;
	}

	/**
	 * This function checks if the supplied domain name is registered
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $domain The domain that will be checked for registration.
	 * @return boolean true means the domain is NOT registered.
	 */
	public function is_available( $domain ) {

		// make the domain lowercase.
		$domain = strtolower( $domain );

		// Set the timeout (in seconds) for the socket open function.
		$timeout = 10;

		/**
		 * This array contains the list of WHOIS servers and the "domain not found" string
		 * to be searched for to check if the domain is available for registration.
		 *
		 * NOTE: The "domain not found" string may change at any time for any reason.
		 */

		$file_dir = CHECK_DOMAIN_DIR . 'tld-whois-zones.json';
		$file_dir_open = fopen( $file_dir, 'r' );
		$file = fread( $file_dir_open, filesize( $file_dir ) );
		fclose( $file_dir_open );
		$whois_arr = json_decode( $file, true );

		// gethostbyname returns the same string if it cant find the domain,.
		// we do a further check to see if it is a false positive.
		// if (gethostbyname( $domain ) == $domain) {.
		// get the TLD of the domain.
		$tld = $this->get_tld( $domain );

		// If an entry for the TLD exists in the whois array.
		if ( isset( $whois_arr[ $tld ][0] ) ) {
			// set the hostname for the whois server.
			$whois_server = $whois_arr[ $tld ][0];

			// set the "domain not found" string.
			$bad_string = $whois_arr[ $tld ][1];
		} else {
			// TODO: REFACTOR THIS
			// TLD is not in the whois array, die
			// throw new Exception("WHOIS server not found for that TLD");.
			return '2';
		}

		$status = $this->check_domain_name_availability( $domain, $whois_server, $bad_string );

		return $status;


}

	/**
	 * Extracts the TLD from a domain, supports URLS with "www." at the beginning.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 *
	 * @param string $domain The domain that will get it's TLD extracted.
	 * @return string The TLD for $domain
	 * @throws Exception Exception.
	 */
	public function get_tld( $domain ) {
		$split = explode( '.', $domain );

		if ( count( $split ) === 0 ) {
			throw new Exception( 'Invalid domain extension' );

		}
		return end( $split );
	}

	/**
	 * If the response from gethostbyname() is anything else than the domain you
	 * passed to the function, it means the domain is registered.
	 *
	 * @param string $domain_name Domain name.
	 * @param string $whois_server Whois Server.
	 * @param string $find_text Text to be searched.
	 *
	 * @author  Helge Sverre <email@helgesverre.com>
	 */
	public function check_domain_name_availability( $domain_name, $whois_server, $find_text ) {

		// Open a socket connection to the whois server.
		$con = fsockopen( $whois_server, 43 );
		if ( ! $con ) {
			return false;
		}

		// Send the requested domain name.
		fputs( $con, $domain_name . "\r\n" );

		// Read and store the server response.
		$response = ' :';
		while ( ! feof( $con ) ) {
			$response .= fgets( $con, 128 );
		}

		// Close the connection.
		fclose( $con );

		// Check the Whois server response.
		if ( strpos( strtolower( $response ), strtolower( $find_text ) ) ) {
			return '1';
		} else {
			return '0';
		}
	}



}

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
class Whois {
	/**
	 * Domain.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $domain    Domain.
	 */
	private $domain;

	/**
	 * TLD.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $tld    TLD.
	 */
	private $tld;

	/**
	 * Sub domain.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $sub_domain    Sub domain.
	 */
	private $sub_domain;

	/**
	 * Servers.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $servers    Servers.
	 */
	private $servers;

	/**
	 * Full Domain name.
	 *
	 * @param string $domain full domain name (without trailing dot).
	 * @throws \InvalidArgumentException If arguments are invalid.
	 */
	public function __construct( $domain ) {
		$this->domain = $domain;
		// check $domain syntax and split full domain name on sub_domain and tld.
		if (
			preg_match( '/^([\p{L}\d\-]+)\.((?:[\p{L}\-]+\.?)+)$/ui', $this->domain, $matches )
			|| preg_match( '/^(xn\-\-[\p{L}\d\-]+)\.(xn\-\-(?:[a-z\d-]+\.?1?)+)$/ui', $this->domain, $matches )
		) {
			$this->sub_domain = $matches[1];
			$this->tld        = $matches[2];
		} else {
			// If arguments are invalid.
			throw new \InvalidArgumentException( "Invalid $domain syntax" );
		}
		// setup whois servers array from json file.
		$this->servers = json_decode( file_get_contents( CHECK_DOMAIN_DIR . 'tld-whois-zones.json' ), true );
	}

	/**
	 * Get Whois info from appropriate server.
	 */
	public function info() {
		if ( $this->is_valid() ) {
			$whois_server = $this->servers[ $this->tld ][0];

			// If tld have been found.
			if ( $whois_server != '' ) {

				// if whois server serve replay over HTTP protocol instead of WHOIS protocol.
				if ( preg_match( '/^https?:\/\//i', $whois_server ) ) {

					// curl session to get whois reposnse.
					$ch  = curl_init();
					$url = $whois_server . $this->sub_domain . '.' . $this->tld;
					curl_setopt( $ch, CURLOPT_URL, $url );
					curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
					curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
					curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

					$data = curl_exec( $ch );

					if ( curl_error( $ch ) ) {
						return 'Connection error!';
					} else {
						$string = strip_tags( $data );
					}
					curl_close( $ch );

				} else {

					// Getting whois information.
					$fp = fsockopen( $whois_server, 43 );
					if ( ! $fp ) {
						return 'Connection error!';
					}

					$dom = $this->sub_domain . '.' . $this->tld;
					fputs( $fp, "$dom\r\n" );

					// Getting string.
					$string = '';

					// Checking whois server for .com and .net.
					if ( 'com' === $this->tld || 'net' === $this->tld ) {
						while ( ! feof( $fp ) ) {
							$line = trim( fgets( $fp, 128 ) );

							$string .= $line;

							$line_array = explode( ':', $line );

							if ( strtolower( $line_array[0] ) == 'whois server' ) {
								$whois_server = trim( $line_array[1] );
							}
						}
						// Getting whois information.
						$fp = fsockopen( $whois_server, 43 );
						if ( ! $fp ) {
							return 'Connection error!';
						}

						$dom = $this->sub_domain . '.' . $this->tld;
						fputs( $fp, "$dom\r\n" );

						// Getting string.
						$string = '';

						while ( ! feof( $fp ) ) {
							$string .= fgets( $fp, 128 );
						}

						// Checking for other tld's.
					} else {
						while ( ! feof( $fp ) ) {
							$string .= fgets( $fp, 128 );
						}
					}
					fclose( $fp );
				}

				$string_encoding = mb_detect_encoding( $string, 'UTF-8, ISO-8859-1, ISO-8859-15', true );
				$string_utf8     = mb_convert_encoding( $string, 'UTF-8', $string_encoding );

				return htmlspecialchars( $string_utf8, ENT_COMPAT, 'UTF-8', true );
			} else {
				return 'No whois server for this tld in list!';
			}
		} else {
			return "Domain name isn't valid!";
		}
	}

	/**
	 * Inserts HTML line breaks before all newlines in a string.
	 */
	public function html_info() {
		return nl2br( $this->info() );
	}

	/**
	 * Get full domain name.
	 *
	 * @return string full domain name
	 */
	public function get_domain() {
		return $this->domain;
	}

	/**
	 * Get Whois info from appropriate server.
	 *
	 * @return string top level domains separated by dot
	 */
	public function get_tld() {
		return $this->tld;
	}

	/**
	 * Get sub domain.
	 *
	 * @return string return sub_domain (low level domain)
	 */
	public function get_sub_domain() {
		return $this->sub_domain;
	}

	/**
	 * Check if domain is avilable.
	 */
	public function is_available() {
		$whois_string     = $this->info();
		$not_found_string = '';
		if ( isset( $this->servers[ $this->tld ][1] ) ) {
			$not_found_string = $this->servers[ $this->tld ][1];
		}

		$whois_string2 = @preg_replace( '/' . $this->domain . '/', '', $whois_string );
		$whois_string  = @preg_replace( '/\s+/', ' ', $whois_string );

		$array = explode( ':', $not_found_string );
		if ( 'MAXCHARS' === $array[0] ) {
			if ( strlen( $whois_string2 ) <= $array[1] ) {
				return true;
			} else {
				return false;
			}
		} else {
			if ( preg_match( '/' . $not_found_string . '/i', $whois_string ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if domain is valid.
	 */
	public function is_valid() {
		if (
			isset( $this->servers[ $this->tld ][0] )
			&& strlen( $this->servers[ $this->tld ][0] ) > 6
		) {
			$tmp_domain = strtolower( $this->sub_domain );
			if (
				preg_match( '/^[a-z0-9\-]{3,}$/', $tmp_domain )
				&& ! preg_match( '/^-|-$/', $tmp_domain ) // && !preg_match("/--/", $tmp_domain)
			) {
				return true;
			}
		}

		return false;
	}

		/**
		 * Validate a domain and add .com if query doesn't have tld.
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
}

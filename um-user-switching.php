<?php
/**
 * Plugin Name: UM User Switching
 * Plugin URI:  https://suiteplugins.com
 * Description: Integrates User Switching and Ultimate Member
 * Version:     1.0.1.1
 * Author:      SuitePlugins
 * Author URI:  https://suiteplugins.com
 * Donate link: https://suiteplugins.com
 * License:     GPLv3
 * Text Domain: um-user-switching
 * Domain Path: /languages
 *
 * @link https://suiteplugins.com
 *
 * @package UM User Switching
 * @version 1.0.0
 */

/**
 * Copyright (c) 2017 SuitePlugins (email : support@suiteplugins.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */


/**
 * Autoloads files with classes when needed
 *
 * @since  1.0.0
 * @param  string $class_name Name of the class being requested.
 * @return void
 */
function um_user_switching_autoload_classes( $class_name ) {
	if ( 0 !== strpos( $class_name, 'UMUS_' ) ) {
		return;
	}

	$filename = strtolower( str_replace(
		'_', '-',
		substr( $class_name, strlen( 'UMUS_' ) )
	) );

	UM_User_Switching::include_file( 'includes/class-' . $filename );
}
spl_autoload_register( 'um_user_switching_autoload_classes' );

/**
 * Main initiation class
 *
 * @since  1.0.0
 */
final class UM_User_Switching {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  1.0.0
	 */
	const VERSION = '1.0.1.1';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages
	 *
	 * @var array
	 * @since  1.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin
	 *
	 * @var UM_User_Switching
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  1.0.0
	 * @return UM_User_Switching A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Add hooks and filters
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'um_profile_header', array( $this, 'um_add_switch_button' ), 99 );
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function init() {
		// bail early if requirements aren't met
		if ( ! $this->check_requirements() ) {
			return;
		}

		// load translated strings for plugin
		load_plugin_textdomain( 'um-user-switching', false, dirname( $this->basename ) . '/languages/' );
	}

	/**
	 * Add link to UM Member header
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function um_add_switch_button() {
		$user_switching = user_switching::get_instance();

		// Get current user ID based on profile.
		$profile_id = um_get_requested_user();

		// if not on profile then bail.
		if ( empty( $profile_id ) ) {
			return;
		}
		// Get user data.
		$user = get_userdata( $profile_id );

		// Check if user data exists.
		if ( ! $user ) {
			return;
		}
		if ( ! $link = $user_switching->maybe_switch_url( $user ) ) {
			return;
		}
		?>
		<a href="<?php echo esc_url( $link ); ?>" class="" id="user_switching"><i class="um-faicon-sign-in" aria-hidden="true"></i> <?php echo esc_html__( 'Switch&nbsp;To', 'um-user-switching' ); ?></a>
		<?php
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  1.0.0
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {
		// bail early if pluginmeets requirements
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function deactivate_me() {
		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  1.0.0
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		if ( ! class_exists( 'user_switching' ) ) {
			$this->activation_errors[] = sprintf( __( 'UM User Switching requires <a href="%s">%s</a> to be installed and activated', 'um-user-switching' ), esc_url( 'https://wordpress.org/plugins/user-switching/' ), __( 'User Switching', 'um-user-switching' ) );
		}
		/*
		if ( ! class_exists( 'UM' )  ) {
			$this->activation_errors[] = sprintf( __( 'UM User Switching requires <a href="%s">%s</a> to be installed and activated', 'um-user-switching' ), esc_url( 'https://wordpress.org/plugins/ultimate-member/' ), __( 'Ultimate Member', 'um-user-switching' ) );
		}*/
		if ( ! empty( $this->activation_errors ) ) {
			return false;
		}
		// Add detailed messages to $this->activation_errors array
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function requirements_not_met_notice() {
		// compile default message
		$default_message = sprintf(
			__( 'UM User Switching is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'um-user-switching' ),
			admin_url( 'plugins.php' )
		);

		// default details to null
		$details = null;

		// add details if any exist
		if ( ! empty( $this->activation_errors ) && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// output errors
		?>
		<div id="message" class="error">
			<p><?php echo $default_message; ?></p>
			<?php echo $details; ?>
		</div>
		<?php
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  1.0.0
	 * @param string $field Field to get.
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory
	 *
	 * @since  1.0.0
	 * @param  string $filename Name of the file to be included.
	 * @return bool   Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory
	 *
	 * @since  1.0.0
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url
	 *
	 * @since  1.0.0
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

/**
 * Grab the UM_User_Switching object and return it.
 * Wrapper for UM_User_Switching::get_instance()
 *
 * @since  1.0.0
 * @return UM_User_Switching  Singleton instance of plugin class.
 */
function um_user_switching() {
	return UM_User_Switching::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( um_user_switching(), 'hooks' ) );

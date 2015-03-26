<?php
/**
 * Plugin Name: My reCaptcha
 * Plugin URI: http://roidayan.com
 * Description: Google reCaptcha V2 plugin
 * Version: 1.0.1
 * Author: Roi Dayan
 * Author URI: http://roidayan.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 3.5
 * Tested up to: 4.1.1
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


define( 'MYCP_TD', 'MYCP' );


class MyReCaptcha {

	function __construct() {
		$this->options_key = 'my_recaptcha';
		$this->options_page = 'my_recaptcha_options_page';

		$this->default_options = array (
			'public_key' => '',
			'private_key' => '',
			'lang' => 'en'
		);

		add_action( 'appthemes_add_submenu_page', array( $this, 'admin_page' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'after_setup_theme', array( $this, 'setup_theme' ), 1000 );
		add_action( 'register_form', array( $this, 'show_recaptcha' ) );

		add_filter( 'registration_errors', array($this, 'check_recaptcha' ), 10, 3 );
	}

	function show_recaptcha() {
		if ( ! current_theme_supports( 'my-recaptcha' ) ) {
			return;
		}

		list( $options ) = get_theme_support( 'my-recaptcha' );
		$recaptcha =  'recaptcha-v2.php';
		$lang = isset( $options['lang'] ) ? $options['lang'] : '';

		require_once ( $recaptcha );

		wp_enqueue_script( 'google-recaptcha', recaptcha_get_script_url( $lang ), false, '1.0.0', true );

		echo '<p>';
		echo recaptcha_get_html( $options['public_key'], $lang );
		echo '</p>';
	}

	function check_recaptcha( $errors, $sanitized_user_login, $user_email ) {
		if ( ! current_theme_supports( 'my-recaptcha' ) ) {
			return $errors;
		}

		list( $options ) = get_theme_support( 'my-recaptcha' );
		$recaptcha =  'recaptcha-v2.php';
		require_once ( $recaptcha );

		$resp = recaptcha_check_answer( $options['private_key'], $_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response'] );

		if ( ! $resp->is_valid ) {
			$errors->add( 'recaptcha_error', __( '<strong>ERROR</strong>: The reCaptcha anti-spam response is incorrect.', MYCP_TD ) );
		}

		return $errors;
	}

	function setup_theme() {
		$this->add_recaptcha_support();
	}

	function add_recaptcha_support() {
		if ( ! $this->get_option('enable') ) {
			return;
		}

		add_theme_support( 'my-recaptcha', array(
			'public_key' => $this->get_option( 'public_key' ),
			'private_key' => $this->get_option( 'private_key' ),
			'lang' => $this->get_option( 'lang' ),
		) );
	}

	function admin_page() {
		add_options_page(
					__( 'reCaptcha Settings', MYCP_TD ),
					__( 'reCaptcha', MYCP_TD ),
					'manage_options',
					'my-recaptcha',
					array( $this, 'show_options_page' ) );
	}

	function admin_menu() {
		register_setting( $this->options_key, $this->options_key );
		$section = 'my_recaptcha';
		add_settings_section( $section,
							  false,
							  false,
							  $this->options_page );
		$fields = array(
			'enable' => array(
				'title' => __( 'Enable:', MYCP_TD ),
				'type' => 'checkbox',
			),

			'public_key' => array(
				'title' => __( 'Public key:', MYCP_TD ),
			),

			'private_key' => array(
				'title' => __( 'Private key:', MYCP_TD ),
			),

			'lang' => array(
				'title' => __( 'Language code:', MYCP_TD ),
			),
		);

		foreach ( $fields as $field => $meta ) {
			$args = array_merge( array( 'label_for' => $field ), $meta );
			add_settings_field( $field,
								$meta['title'],
								array( $this, 'show_field' ),
								$this->options_page,
								$section,
								$args
			);
		}
	}

	function show_field( $args ) {
		$id = $args['label_for'];
		$val = $this->get_option( $id );
		$type = empty( $args['type'] ) ? 'text' : $args['type'];
		$extra = empty( $args['extra'] ) ? '' : $args['extra'];

		if ( $type == 'checkbox' || $type == 'radio' ) {
			$checked = checked( 1, $val, false );
			$val = 1;
		} else {
			$checked = '';
		}

		echo '<input id="' . $id . '" name="' . $this->options_key . '[' . $id . ']" type="' . $type
			 . '" value="' . esc_attr( $val ) . '" ' . $checked . ' ' . $extra . '>';

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	function show_options_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'reCaptcha Settings', MYCP_TD ); ?></h2>
			<form action="options.php" method="post">
			<?php
				settings_fields( $this->options_key );
				do_settings_sections( $this->options_page );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	function get_option( $opt ) {
		$options = get_option( $this->options_key );
		if ( ! empty( $options[ $opt ] ) ) {
			$val = $options[ $opt ];
		} elseif ( ! empty( $this->default_options[ $opt ] ) ) {
			$val = $this->default_options[ $opt ];
		} else {
			$val = '';
		}
		return $val;
	}
}

new MyReCaptcha;
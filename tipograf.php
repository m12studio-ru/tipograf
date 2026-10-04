<?php
/**
 * Plugin Name:       Типограф: висячие предлоги
 * Plugin URI:        https://github.com/m12studio-ru/tipograf
 * Description:       Hanging prepositions fix for Russian texts: keeps short words, particles, dashes and units on one line with the neighbouring word using non-breaking spaces.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            m12studio
 * Author URI:        https://m12studio.ru
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tipograf
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'TIPOGRAF_VERSION', '0.1.0' );

require_once __DIR__ . '/includes/class-tipograf-engine.php';

function tipograf_defaults() {
	return array(
		'short_words' => true,
		'particles'   => true,
		'dash'        => true,
		'units'       => true,
		'words'       => Tipograf_Engine::DEFAULT_WORDS,
	);
}

function tipograf_options() {
	$saved = get_option( 'tipograf_options', array() );
	return array_merge( tipograf_defaults(), is_array( $saved ) ? $saved : array() );
}

/*
 * Обработка страниц: весь готовый HTML на сайте (тексты, заголовки, меню, поля, шаблоны темы).
 */

add_action( 'template_redirect', 'tipograf_start_buffer', 0 );

function tipograf_start_buffer() {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_robots() || is_trackback()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'tipograf_buffer' );
}

function tipograf_buffer( $html ) {
	foreach ( headers_list() as $header ) {
		if ( 0 === stripos( $header, 'content-type:' ) && false === stripos( $header, 'text/html' ) ) {
			return $html;
		}
	}
	return Tipograf_Engine::process( $html, tipograf_options() );
}

/*
 * Кеш страниц: после изменения настроек, включения и выключения старые страницы надо пересобрать.
 */

function tipograf_flush_page_cache() {
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache(); // WP Super Cache
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain(); // WP Rocket
	}
	do_action( 'litespeed_purge_all' ); // LiteSpeed Cache
}

register_activation_hook( __FILE__, 'tipograf_flush_page_cache' );
register_deactivation_hook( __FILE__, 'tipograf_flush_page_cache' );
add_action( 'update_option_tipograf_options', 'tipograf_flush_page_cache' );

/*
 * Настройки: Настройки → Типограф.
 */

add_action( 'init', 'tipograf_load_textdomain' );

function tipograf_load_textdomain() {
	load_plugin_textdomain( 'tipograf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'admin_init', 'tipograf_register_settings' );

function tipograf_register_settings() {
	register_setting(
		'tipograf',
		'tipograf_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'tipograf_sanitize',
			'default'           => tipograf_defaults(),
		)
	);
}

function tipograf_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( array( 'short_words', 'particles', 'dash', 'units' ) as $key ) {
		$out[ $key ] = ! empty( $input[ $key ] );
	}
	$words        = preg_split( '/[\s,]+/u', mb_strtolower( sanitize_textarea_field( $input['words'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
	$out['words'] = implode( ' ', array_unique( $words ) );
	return $out;
}

add_action( 'admin_menu', 'tipograf_admin_menu' );

function tipograf_admin_menu() {
	add_options_page( __( 'Typograph', 'tipograf' ), __( 'Typograph', 'tipograf' ), 'manage_options', 'tipograf', 'tipograf_settings_page' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tipograf_action_links' );

function tipograf_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=tipograf' ) ) . '">' . esc_html__( 'Settings', 'tipograf' ) . '</a>' );
	return $links;
}

function tipograf_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o     = tipograf_options();
	$rules = array(
		'short_words' => array( __( 'Short words', 'tipograf' ), __( 'Prepositions, conjunctions and short pronouns stay with the next word: «с вами», «на сайт», «не всегда».', 'tipograf' ) ),
		'particles'   => array( __( 'Particles', 'tipograf' ), __( 'же, ли, бы stay with the previous word: «он же», «был ли».', 'tipograf' ) ),
		'dash'        => array( __( 'Dash', 'tipograf' ), __( 'A dash never starts a line: «Москва — столица».', 'tipograf' ) ),
		'units'       => array( __( 'Numbers and abbreviations', 'tipograf' ), __( 'A number stays with its unit and an abbreviation with the next word: «10 м», «5 шт», «№ 5», «г. Москва».', 'tipograf' ) ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Typograph', 'tipograf' ); ?></h1>
		<p><?php esc_html_e( 'Text on the site pages is processed when the page is served; the texts in the database are not changed. After changing the settings the page cache is cleared automatically (WP Super Cache, WP Rocket, LiteSpeed); with another cache plugin, clear it manually.', 'tipograf' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'tipograf' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Rules', 'tipograf' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $rules as $key => $rule ) : ?>
								<p>
									<label>
										<input type="checkbox" name="tipograf_options[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $o[ $key ] ); ?>>
										<strong><?php echo esc_html( $rule[0] ); ?></strong>
									</label>
									<br><span class="description"><?php echo esc_html( $rule[1] ); ?></span>
								</p>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tipograf-words"><?php esc_html_e( 'Short words', 'tipograf' ); ?></label></th>
					<td>
						<textarea id="tipograf-words" name="tipograf_options[words]" rows="4" class="large-text"><?php echo esc_textarea( $o['words'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Separated by spaces or commas, case does not matter.', 'tipograf' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

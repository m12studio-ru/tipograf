<?php
/**
 * Удаление плагина: убираем его настройки.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'tipograf_options' );

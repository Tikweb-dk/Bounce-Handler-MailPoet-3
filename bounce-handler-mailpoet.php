<?php

if(!defined('ABSPATH')) exit;

/**
 * Plugin Name:       Bounce Handler Mailpoet
 * Description:       Bounce Handler Mailpoet is an add-on for MailPoet 3 to handle bounce emails easily, when using your own SMTP server.
 * Version:           1.3.11
 * Author:            Tikweb
 * Author URI:        http://www.tikweb.dk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bounce-handler-mailpoet
 * Domain Path:       /languages
 */

/*
Bounce Handler Mailpoet is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Bounce Handler Mailpoet is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Bounce Handler Mailpoet. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
*/

// If this file is called directly, abort.
if(!defined( 'WPINC' )){
	die;
}

if(!defined('ABSPATH')){
	exit;
}

/**
 * Define root path
 */
if(!defined('MBH_ROOT_PATH')){
	$mbh_root = plugin_dir_path(__FILE__);
	define('MBH_ROOT_PATH', $mbh_root);
}

/**
 * Define Mailpoet Root url
 */
if(!defined('MAILPOET_ROOT_URL')){
	$mailpoet_root_url = plugins_url().'/mailpoet';
	define('MAILPOET_ROOT_URL', $mailpoet_root_url);
}

/**
 * If php version is lower
 */
if(version_compare(phpversion(), '5.4', '<')){
	function mailpoet_bh_php_version_notice(){
		?>
		<div class="error">
			<p><?php _e('MailPoet plugin requires PHP version 5.4 or newer, Please upgrade your PHP.', 'bounce-handler-mailpoet'); ?></p>
		</div>
		<?php
	}
	add_action('admin_notices', 'mailpoet_bh_php_version_notice');
	return;
}

/**
 * Install Database for Bounced log
 */
function mbh_logdb(){
	global $wpdb;

	$table_name = $wpdb->prefix . 'bounced_email_logs';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		email varchar(100),
		reason varchar(50),
		last_checked datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'mbh_logdb' );


/**
 * Plugin Helper Functions
 */
require_once MBH_ROOT_PATH . 'includes/helper-functions.php';

/**
 * Include plugin.php to detect plugin.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Check MailPoet active
 */
if(!is_plugin_active('mailpoet/mailpoet.php')){
	add_action('admin_notices', function(){
		?>
		<div class="error">
			<p><?php _e('Bounce Handler Mailpoet plugin requires MailPoet plugin, Please activate MailPoet first to using Bounce Handler Mailpoet.', 'bounce-handler-mailpoet'); ?></p>
		</div>
		<?php
	});
	return;	// If not then return
}

/**
 * The core plugin class
 * that is used to define Admin page and settings.
 */
require_once MBH_ROOT_PATH . 'includes/class-mailpoet-bounce-handler.php';

/**
 * The bounce handling class
 * that is used to check bounce connection, .
 */
require_once MBH_ROOT_PATH . 'includes/class-mailpoet-handle-bounces.php';

/**
 * Include bounce detect class.
 */
require_once MBH_ROOT_PATH.'includes/class-mailpoet-bounce-detect.php';

/**
 * Cron job scheduler file.
 */
require_once MBH_ROOT_PATH . 'includes/scheduler.php';

/**
 * Bounce handler logger file
 */
require_once MBH_ROOT_PATH.'includes/mbh-logger.php';

/**
 * GDPR Personal Data Exporter
 */
require_once MBH_ROOT_PATH .'includes/personal-data-exporter.php';
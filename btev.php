<?php
/*
Plugin Name: Bluetrait Event Viewer
Plugin URI: http://wordpress.org/extend/plugins/bluetrait-event-viewer/
Description: BTEV monitors events that occur in your WordPress install.
Version: 2.0.2
Author: Michael Dale
Author URI: http://michaeldale.com.au/
*/

/*
Change this value to TRUE to stop admins from disabling/uninstalling/changing the BTEV plugin. 
See FAQ for more info http://wordpress.org/extend/plugins/bluetrait-event-viewer/faq/
*/
define('BTEV_LOCKDOWN', FALSE);

/*
Stop people from accessing the file directly and causing errors.
*/
if (!function_exists('add_action')) {
	die('You cannot run this file directly.');
}

/*
Don't break stuff if user tries to use this plugin on anything less than WordPress 2.0.0
*/
if (!function_exists('get_role')) {
	return;
}

/*
	BTEV 2 Requires PHP 5.2 or greater.
*/
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	die('BTEV requires PHP 5.2.0 or higher to run.');
}

include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'btev_apptrack.class.php');
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'btev_upgrade.class.php');
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'btev.class.php');

$btev = new btev();
$btev->set_var('plugin_basename', plugin_basename(__FILE__));
$btev->set_var('basename', basename(__FILE__));
$btev->load();

//trigger an event with this function. You can use this function for your own plugins. See http://wordpress.org/extend/plugins/bluetrait-event-viewer/other_notes/ for more details
function btev_trigger_error($error_string, $error_number = E_USER_ERROR, $file = __FILE__, $line = __LINE__, $error_context = '', $custom_source = '') {
	global $btev;

	do_action('btev_trigger_error', array(
		'error_string' => $error_string, 
		'error_number' => $error_number, 
		'error_file' => $file, 
		'error_line' => $line,
		'error_context' => $error_context,
		'custom_source' => $custom_source
		)
	);
	
	$btev->error_report($error_number, $error_string, $file, $line, $error_context, $custom_source);
}

/*
//Just a piece of test code, no major changes are currently planned
add_action('after_plugin_row_' . plugin_basename(__FILE__), 'btev_version_2_notice');


function btev_version_2_notice() {
	echo '<tr><td class="plugin-update" colspan="5">Bluetrait Event Viewer 2 is coming soon. Because of large changes version 2 will erase any current events in the system.</td></tr>';
}
*/
?>
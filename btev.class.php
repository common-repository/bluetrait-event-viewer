<?php
/*
	Bluetrait Event Viewer 2 Class
	Copyright Dalegroup Pty Ltd 2014
	support@dalegroup.net
*/

class btev {

	private $vars 		= array();
	private $config 	= array();

	function __construct() {
		global $wpdb;
	
		$this->vars['version']			= '2.0.2';
		$this->vars['db_version']		= 3;
		$this->vars['tables']['events']	= $wpdb->prefix . 'btev_events';
		$this->vars['tables']['users']	= $wpdb->prefix . 'users';

	}
	
	public function set_var($name, $value) {
		$this->vars[$name] = $value;
	}
	
	public function get_var($name) {
		if (isset($this->vars[$name])) {
			return $this->vars[$name];
		}
		return false;	
	}
	
	public function get_table($name) {
		if (isset($this->vars['tables'][$name])) {
			return $this->vars['tables'][$name];
		}
		return false;
	}
	
	//returns a config value from the site array
	public function get_config($config_name, $unserialize = true) {
		
		$btev_site = $this->config;
		
		if (!empty($btev_site)) {
			if (array_key_exists($config_name, $btev_site)) {
				if ($unserialize == true) {
					$str = 's';
					$array = 'a';
					$integer = 'i';
					$any = '[^}]*?';
					$count = '\d+';
					$content = '"(?:\\\";|.)*?";';
					$open_tag = '\{';
					$close_tag = '\}';
					$parameter = "($str|$array|$integer|$any):($count)" . "(?:[:]($open_tag|$content)|[;])";           
					$preg = "/$parameter|($close_tag)/";
					if(!preg_match_all($preg, $btev_site[$config_name], $matches)) {           
						return $btev_site[$config_name];
					}
					else {
						return unserialize($btev_site[$config_name]);
					}
				}
				else {
					return $btev_site[$config_name];
				}
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	//sets a config value into the array
	public function set_config($config_name, $config_value, $update_now = FALSE) {
			
		if (is_array($config_value)) {
			$this->config[$config_name] = serialize($config_value);
		}
		else {
			$this->config[$config_name] = $config_value;
		}
		
		if ($update_now) {
			$this->save_config();
		}
	}

	//saves the site array back to the database
	public function save_config() {				
		update_option('btev_config', $this->config);	
	}
	
	public function load() {
	
		//setup the database, check/upgrade database
		$this->is_installed();
		$this->config = get_option('btev_config');
		$this->upgrade();

		//take over trigger_error
		if ($this->get_config('set_error_report')) {
			set_error_handler(array($this, 'error_report'));
		}
		
		$this->load_hooks();
	}
	
	private function load_hooks() {
		
		//menu actions
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_menu', array($this, 'admin_menu_settings'));
		add_action('admin_menu', array($this, 'admin_menu_details'));

		//events
		add_action('password_reset', array($this, 'trigger_password_reset'));
		add_action('delete_user', array($this, 'trigger_delete_user'));
		add_action('activate_' . $this->get_var('plugin_basename'), array($this, 'trigger_activate_btev'));
		add_action('deactivate_' . $this->get_var('plugin_basename'), array($this, 'trigger_deactivate_btev'));
		add_action('wp_login', array($this, 'trigger_wp_login'));
		add_action('lostpassword_post', array($this, 'trigger_lostpassword_post'));
		add_action('profile_update', array($this, 'trigger_profile_update'));
		add_action('add_attachment', array($this, 'trigger_add_attachment'));
		add_action('wp_logout', array($this, 'trigger_wp_logout'));
		add_action('user_register', array($this, 'trigger_user_register'));
		add_action('switch_theme', array($this, 'trigger_switch_theme'));
		add_action('publish_post', array($this, 'trigger_publish_post'));
		

		//WordPress configuration monitoring
		//add_action('updated_option', 'btev_trigger_updated_option');
		//add_action('added_option', 'btev_trigger_added_option');

		//cron stuff
		add_filter('cron_schedules', array($this, 'cron_more_reccurences'));
		add_action('btev_cron_daily_tasks_hook', array($this, 'cron_daily_tasks'));
		add_action('btev_cron_weekly_tasks_hook', array($this, 'cron_weekly_tasks'));

		//display dashboard widget
		if ($this->get_config('display_on_dashboard')) {
			add_action('wp_dashboard_setup', array($this, 'dashboard_setup'));
		}

		//if (version_compare($wp_version, '2.5', '<')) {

		add_action('wp_login_failed', array($this, 'trigger_login_error'));
		add_action('phpmailer_init', array($this, 'trigger_phpmailer_init'));


		//load BTEV
		add_action('init', array($this, 'add_admin_cap'));
		add_action('init', array($this, 'submit_uninstall'));
		add_action('init', array($this, 'request_handle'), 100);

		//monitor plugins
		add_action('load-plugins.php', array($this, 'monitor_plugins'));
		add_action('update.php', array($this, 'monitor_plugins'));		

	}
	
	//populates the site table with info
	private function create_config() {
		
		$site = array();
		//version 0.2
		$site['debug'] 					= 0;
		$site['log_all']		 		= 0;
		$site['display_on_dashboard'] 	= 1;
		$site['installed'] 				= current_time('mysql');
		$site['next_update_check'] 		= '';
		$site['last_update_response'] 	= '';
		$site['version'] 				= $this->get_var('version');
		//version 0.3
		$site['set_error_report'] 		= 0;
		$site['override_wp_login'] 		= 0;
		//version 0.4
		$site['update_check_interval'] 	= 86400;
		//version 0.5
		$site['override_wp_mail'] 		= 0;
		//version 1.4
		//$site['source_cropping']	 	= '';
		//version 1.5
		$site['auto_prune'] 			= 1;
		$site['event_count'] 			= 10000;
		//version 1.8
		$site['external_access'] 		= '0';
		$site['external_access_key'] 	= '';
		//version 1.8.2
		$site['log_notice'] 			= 1;
		$site['log_warning'] 			= 1;
		$site['log_error'] 				= 1;
		//version 2.0
		$site['db_version']				= $this->get_var('db_version');

		add_option('btev_config', $site, 'Bluetrait Event Viewer Config');
	}
	
	//install database tables
	private function install() {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');

		if($wpdb->get_var("show tables like '$btev_tb_events'") != $btev_tb_events) {

			$sql = "CREATE TABLE " . $btev_tb_events . " (
				`type` varchar(100) NOT NULL default '',
				`date` datetime NOT NULL default '0000-00-00 00:00:00',
				`source` text NOT NULL,
				`file_line` varchar(100) NOT NULL,
				`user_id` mediumint(9) unsigned NOT NULL default '0',
				`ip_address` varchar(100) NOT NULL default '',
				`event_id` int(11) NOT NULL auto_increment,
				`event_no` int(11) NOT NULL default '0',
				`description` TEXT NOT NULL,
				`trace` TEXT,
				`server_id` int(11),
				`custom_source` varchar(255),
				`event_synced` int(1) unsigned NOT NULL default '0',
				UNIQUE KEY  (event_id)
			) DEFAULT CHARSET=utf8;";

			if (file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
				//wordpress 2.3+
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			}
			else {
				//wordpress 2.2.x and lower
				require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			}
			
			dbDelta($sql);
		}
		
		//create config will do nothing if option already exists
		$this->create_config();	
		$this->config 	= get_option('btev_config');
		$this->schedule_tasks();
		$this->trigger_error('Bluetrait Event Viewer ' . $this->get_var('version') . ' Has Been Successfully Installed.', E_USER_NOTICE);
		add_option('btev_installed', '1');
	}
		
	//checks if an upgrade is needed
	private function upgrade() {
		$update = new btev_upgrade($this);

		$update->upgrade();
	}
	
	//this function does the uninstalling
	function uninstall() {
		global $wpdb;
		
		/*
		Deactivate Plugin
		*/
		$current = get_option('active_plugins');
		array_splice($current, array_search($this->get_var('plugin_basename'), $current), 1 ); // Array-fu!
		update_option('active_plugins', $current);

		/*
		Drop Events Table
		*/
		$btev_tb_events = $this->get_table('events');
		if (!empty($btev_tb_events)) {
			if($wpdb->get_var("show tables like '$btev_tb_events'") == $btev_tb_events) {
				$wpdb->query("DROP TABLE $btev_tb_events");
			}
		}
		
		/*
		Delete Options from WordPress Table
		*/
		delete_option('btev_config');
		delete_option('btev_installed');
		
		/*
		Unschedule Cron Tasks
		*/
		if (function_exists('wp_clear_scheduled_hook')) {
			wp_clear_scheduled_hook('btev_cron_daily_tasks_hook');
			wp_clear_scheduled_hook('btev_cron_weekly_tasks_hook');
		}
		
		/*
		Redirect To Plugin Page
		*/
		wp_redirect('plugins.php?deactivate=true');
	
	}
	
	//copy of btev_trigger_error for internal plugin use
	public function trigger_error($error_string, $error_number = E_USER_ERROR, $file = __FILE__, $line = __LINE__, $error_context = '', $custom_source = '') {

		do_action('btev_trigger_error', array(
		'error_string' => $error_string, 
		'error_number' => $error_number, 
		'error_file' => $file, 
		'error_line' => $line,
		'error_context' => $error_context,
		'custom_source' => $custom_source)
		);
		
		$this->error_report($error_number, $error_string, $file, $line, $error_context, $custom_source);
	}
	
	//writes event to database and can be used for set_error_handler
	public function error_report($errno, $errstr, $errfile, $errline, $error_context = '', $custom_source = '') {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');
		
		if ($this->get_config('debug')) {
			echo '<br />' . esc_html($errno) . ': ' . esc_html($errstr) .  ' in <b>' . esc_html($errfile) . '</b> on line <b>' . esc_html($errline) . '</b>';
		}
		
		switch ($errno) {	
			case E_USER_ERROR:
				if ($this->get_config('log_error')) {
					/*
					Get the backtrace here
					*/
					if (function_exists('debug_print_backtrace')) {
						ob_start();
						debug_print_backtrace();
						$trace = ob_get_contents();
						ob_end_clean();
					}
					else {
						$trace = 'Backtrace is not supported in PHP4, please upgrade to PHP5.';
					}
					
					$result = $wpdb->query(
					"INSERT INTO $btev_tb_events 
					(`type`, `event_no`, `date`, `source`, `user_id`, `ip_address`, `description`, `file_line`, `trace`, `custom_source`) 
					VALUES 
					('ERROR',
					'" . $wpdb->escape($errno) . "',
					'" . $wpdb->escape(current_time('mysql')) ."',
					'" . $wpdb->escape($errfile) . "',
					'" . $wpdb->escape($this->user_id()) . "',
					'" . $wpdb->escape($this->ip_address()) . "',
					'" . $wpdb->escape($errstr) . "',
					'" . $wpdb->escape($errline) . "',
					'" . $wpdb->escape($trace) . "',
					'" . $wpdb->escape($custom_source) . "')
					");
				}
			break;
			
			case E_USER_WARNING:
				if ($this->get_config('log_warning')) {
					$result = $wpdb->query(
					"INSERT INTO $btev_tb_events 
					(`type`, `event_no`, `date`, `source`, `user_id`, `ip_address`, `description`, `file_line`, `custom_source`) 
					VALUES 
					('WARNING',
					'" . $wpdb->escape($errno) . "',
					'" . $wpdb->escape(current_time('mysql')) ."',
					'" . $wpdb->escape($errfile) . "',
					'" . $wpdb->escape($this->user_id()) . "',
					'" . $wpdb->escape($this->ip_address()) . "',
					'" . $wpdb->escape($errstr) . "',
					'" . $wpdb->escape($errline) . "',
					'" . $wpdb->escape($custom_source) . "')
					");
				}
			break;
			
			case E_USER_NOTICE:
				if ($this->get_config('log_notice')) {
					$result = $wpdb->query(
					"INSERT INTO $btev_tb_events 
					(`type`, `event_no`, `date`, `source`, `user_id`, `ip_address`, `description`, `file_line`, `custom_source`) 
					VALUES 
					('NOTICE',
					'" . $wpdb->escape($errno) . "',
					'" . $wpdb->escape(current_time('mysql')) ."',
					'" . $wpdb->escape($errfile) . "',
					'" . $wpdb->escape($this->user_id()) . "',
					'" . $wpdb->escape($this->ip_address()) . "',
					'" . $wpdb->escape($errstr) . "',
					'" . $wpdb->escape($errline) . "',
					'" . $wpdb->escape($custom_source) . "')
					");
				}
			break;
			
			default:
				if ($this->get_config('log_all')) {
					/*
					Get the backtrace here
					*/
					if (function_exists('debug_print_backtrace')) {
						ob_start();
						debug_print_backtrace();
						$trace = ob_get_contents();
						ob_end_clean();
					}
					else {
						$trace = 'Backtrace is not supported in PHP4, please upgrade to PHP5.';
					}
					
					$result = $wpdb->query(
					"INSERT INTO $btev_tb_events 
					(`type`, `event_no`, `date`, `source`, `user_id`, `ip_address`, `description`, `file_line`, `trace`, `custom_source`) 
					VALUES 
					('DEBUG',
					'" . $wpdb->escape($errno) . "',
					'" . $wpdb->escape(current_time('mysql')) ."',
					'" . $wpdb->escape($errfile) . "',
					'" . $wpdb->escape($this->user_id()) . "',
					'" . $wpdb->escape($this->ip_address()) . "',
					'" . $wpdb->escape($errstr) . "',
					'" . $wpdb->escape($errline) . "',
					'" . $wpdb->escape($trace) . "',
					'" . $wpdb->escape($custom_source) . "')
					");			
				}
			break;
		}
	}
	
	//returns the users ip address
	function ip_address() {
		return $_SERVER['REMOTE_ADDR'];
	}

	
	//basic date function.
	public function now($format = 'Y-m-d H:i:s', $add_seconds = 0) {

		$base_time = time() + $add_seconds + 3600 * get_option('gmt_offset');
		
		switch($format) {
		
			case 'Y-m-d H:i:s':
				return gmdate('Y-m-d H:i:s', $base_time);
			break;
			
			case 'H:i:s':
				return gmdate('H:i:s', $base_time);
			break;
			
			case 'Y-m-d':
				return gmdate('Y-m-d', $base_time);
			break;
			
			case 'Y':
				return gmdate('Y', $base_time);
			break;
			
			case 'm':
				return gmdate('m', $base_time);
			break;
			
			case 'd':
				return gmdate('d', $base_time);
			break;
		
		}
	}
		
	//add dashboard widget for WordPress 2.7+, otherwise add standard activity box
	public function dashboard_setup() {
		if (function_exists('wp_add_dashboard_widget')) {
			wp_add_dashboard_widget( 'dashboard_btev_recent_events', __( 'Recent Events' ), array($this, 'activity_box_end'));
		}
	}
	
	function send_alert($array) {
		
		if (!isset($array['name'])) return false;
		
		$config			= $this->get_config('events_map');
		$alert_address	= $this->get_config('email_alert_list');
		
		if (isset($config[$array['name']])) {
			if ($config[$array['name']]['email_alert']) {
				
				$subject 	= get_option('blogname') . ' - BTEV - ' . $array['name'];
				$body		= 'Description: ' . $array['description'] . "\nAlert From: " . get_option('siteurl') . "\nIP Address: " . $this->ip_address() . "\nDate: " . current_time('mysql') . "\n\nPowered by Bluetrait Event Viewer";
				
				//global email list
				if (!empty($alert_address)) {
					$pos = strpos($alert_address, ',');
					if ($pos === false) {
						@wp_mail($alert_address, $subject, $body);
					}
					else {
						$email_array = explode(',', $alert_address);
						if (is_array($email_array)) {
							foreach($email_array as $email) {
								@wp_mail($email, $subject, $body);
							}
						}
					}
				}
				
				unset($email);
				unset($email_array);
			
				//per alert email list
				if (!empty($config[$array['name']]['email_list'])) {
					$pos = strpos($config[$array['name']]['email_list'], ',');
					if ($pos === false) {
						@wp_mail($config[$array['name']]['email_list'], $subject, $body);
					}
					else {
						$email_array = explode(',', $config[$array['name']]['email_list']);
						if (is_array($email_array)) {
							foreach($email_array as $email) {
								@wp_mail($email, $subject, $body);
							}
						}
					}
				}
			}
		}
	}
	
	
	//link to event viewer
	function subpanel_link() {
		return 'index.php?page=btev.php';
	}

	//link to event viewer settings
	function subpanel_settings_link() {
		return 'options-general.php?page=btev.php_settings';
	}

	//link to event details settings
	function subpanel_details_link() {
		return 'options-general.php?page=btev.php_event_details';
	}


	//checks to see if btev is installed
	function is_installed() {
		global $wpdb, $btev_tb_events;
		
		$btev_tb_events = $this->get_table('events');

		if($wpdb->get_var("show tables like '$btev_tb_events'") != $btev_tb_events) {
			$this->install();
			$this->trigger_error('Unable to locate BTEV database table. It has been automatically recreated, events in the log may have been lost.', E_USER_ERROR);
			$this->trigger_error("You can safely ignore the previous message if this is the first time you've activated this plugin.", E_USER_NOTICE);
			return false;
		}
		else {
			return true;
		}

	}

	//this function checks that the user is really trying to uninstall and if they have permission to (if so uninstall)
	function submit_uninstall() {
		
		if (isset($_POST['submit_uninstall'])) {
			if (current_user_can('btev') && !BTEV_LOCKDOWN) {
				if (function_exists('check_admin_referer')) {
					check_admin_referer('btev-uninstall');
				}
				$this->uninstall();
			}
			else {
				$this->trigger_error("Unauthorised Uninstall Attempt of BTEV.", E_USER_WARNING);
			}
		}
	}

	//get the current id of user logged in
	function user_id() {
		if (function_exists('wp_get_current_user')) {
			$user_object = wp_get_current_user();
			return $user_object->ID;
		}
		else {
			return 0;
		}
	}

	//add a role so that we can check if the user can "do stuff" (TM)
	function add_admin_cap() {
		$role = get_role('administrator');
		$role->add_cap('btev');
	}

	//used for Bluetrait Connector
	function get_events_not_synced() {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');

		$query = "
			SELECT 
			`type` AS `event_severity`,
			`type` AS `event_type`,
			`custom_source` AS `event_source`,
			`date` AS `event_date`,
			`date` AS `event_date_utc`,
			`source` AS `event_file`, 
			`file_line` AS `event_file_line`,
			`user_id`,
			`ip_address` AS `event_ip_address`,
			`event_id`,
			`event_no` AS `event_number`,
			`description` AS `event_description`,
			`trace` AS `event_trace`
			FROM $btev_tb_events
			WHERE event_synced = 0
			ORDER BY event_id";
		$events_result = $wpdb->get_results($query, 'ARRAY_A');
		
		return $events_result;
		
	}

	//mark events that were synced
	function set_synced($events) {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');
			
		foreach ($events as $event) {
			$query = "UPDATE $btev_tb_events SET event_synced = 1 WHERE event_id = " . (int) $event['event_id'];
			$wpdb->query($query);
		}
		return true;
	}

	//checks if there is a newer version of btev
	function update_check() {
		global $wpdb;
		
		if (current_time('mysql') > $this->get_config('next_update_check')) {
			
			$btev_apptrack = new btev_apptrack();
			
			$send_data['application_id'] 	= 7;
			$send_data['version'] 			= $this->get_config('version');
			$response 						= $btev_apptrack->send($send_data);
								
			$next_update = $this->now('Y-m-d H:i:s', $this->get_config('update_check_interval'));
			
			$this->set_config('next_update_check', $next_update); 
			$this->save_config();
			
			if (!empty($response)) {	
				$this->set_config('last_update_response', $response); 
				$this->save_config();
			}
		}
	}

	//displays if there is a newer version on the settings page
	function display_updates() {
		
		$this->update_check();
		
		$update = new btev_upgrade($this);
		
		if (!$update->update_available()) {				
			?><strong>You're currently running the latest version, <?php echo $this->get_config('version'); ?>.</strong><?php
		}
		else {
			$info = $update->get_update_info();

			?>
			<div id="message" class="updated fade">
				<p>
				<strong>There is a new version of this software available (You're running <?php echo $this->get_config('version'); ?> the latest is <?php echo esc_html($info['version']); ?>). 
				You can download the latest version from <a href="<?php echo esc_html($info['download_url']); ?>">here</a>. 
				</strong></p>
			</div>
			<?php if (!empty($info['message'])) { ?>
				<br /><br />
				<strong><?php echo esc_html($info['message']);?></strong>
			<?php }
		}
	}



	//adds the event viewer to the dashboard submenu
	function admin_menu() {
		if (function_exists('add_submenu_page')) {
			add_submenu_page('index.php', 'Event Viewer', 'Event Viewer', 8, $this->get_var('basename'), array($this, 'subpanel'));
		}
	}

	//adds the event viewer settings to the options submenu
	function admin_menu_settings() {
		if (function_exists('add_options_page')) {
			add_options_page('Event Viewer Settings', 'Event Viewer Settings', 8, $this->get_var('basename') . '_settings', array($this, 'subpanel_settings'));
		}
	}
	//adds the event viewer settings to the options submenu
	function admin_menu_details() {
		if (function_exists('add_options_page')) {
			add_options_page('Event Viewer Details', 'Event Viewer Details', 8, $this->get_var('basename'). '_event_details', array($this, 'subpanel_event_details'));
		}
	}




	/*
		The following part handles the cron section for Bluetrait Event Viewer
	===========================================================================================================================================
	*/
	//this is where we schedule the cron tasks
	function schedule_tasks() {

		if (function_exists('wp_next_scheduled')) {
			if (!wp_next_scheduled('btev_cron_daily_tasks_hook')) {
				wp_schedule_event(0, 'daily', 'btev_cron_daily_tasks_hook');
			}
			if (!wp_next_scheduled('btev_cron_weekly_tasks_hook')) {
				wp_schedule_event(0, 'weekly', 'btev_cron_weekly_tasks_hook');
			}
			return true;
		}
		else {
			return false;
		}

	}

	//add the weekly option to the wordpress cron
	function cron_more_reccurences() {
		return array(
			'weekly' => array('interval' => 604800, 'display' => 'Weekly')
		);
	}

	//this is where we can now put any functions we want to run every day
	function cron_daily_tasks() {
		if($this->get_config('auto_prune')) {
			$this->auto_prune();
		}
	}

	//this is where we can now put any functions we want to run every week
	function cron_weekly_tasks() {
		
		$this->update_check();
		
		$update = new btev_upgrade($this);
		
		if ($update->update_available()) {
			$info = $update->get_update_info();
		
			$this->trigger_error('There is a new version of Bluetrait Event Viewer, version ' .
			esc_html($info['version']) . ' you are running ' . esc_html($this->get_config('version')), E_USER_WARNING);
	
		}
	}

	//this function removes old entries from the event log if enabled
	function auto_prune() {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');
		
		$event_count_query = "SELECT count(*) as `count` FROM $btev_tb_events";
		$event_result = $wpdb->get_results($event_count_query, 'ARRAY_A');
		
		$delete_count = $this->get_config('event_count');
		$event_count = $event_result[0]['count'];
		
		if ($event_count > $delete_count) {
			$events_to_delete = $event_count - $delete_count;
			$events_to_delete = (int) $events_to_delete;
			
			$event_delete_query = "DELETE FROM $btev_tb_events ORDER BY date LIMIT $events_to_delete";
			$count = $wpdb->query($event_delete_query);
		}
		else {
			$count = 0;
		}

		$this->trigger_error('BTEV Auto Prune has finished and deleted "' . (int) $count . '" events.', E_USER_NOTICE);
	}

	/*
	===========================================================================================================================================
	*/

	/*
	This function makes it possible to monitor activation/deactivation of other plugins
	*/
	function monitor_plugins() {

		$current = get_plugins();

		foreach($current as $plugin_file => $plugin_data) {
			if ($plugin_file == $this->get_var('plugin_basename')) continue;
				add_action('deactivate_' . $plugin_file, array($this, 'trigger_deactivate_plugin'));
				add_action('activate_' . $plugin_file, array($this, 'trigger_activate_plugin'));
		}	
	}

	/*
	These functions trigger events to be written to the database uses Wordpress hooks.
	An add_action trigger also needs to be setup for each event to work. See btev_load();
	*/

	function trigger_password_reset() {
		$this->trigger_error('Password Reset', E_USER_NOTICE);
		return;
	}
	function trigger_delete_user($user_id) {
		$this->trigger_error('User Deleted ID ' . $user_id, E_USER_NOTICE);
		return;
	}
	function trigger_activate_btev() {
		$this->trigger_error('Bluetrait Event Viewer activated.', E_USER_NOTICE);
		return;
	}
	function trigger_deactivate_btev() {
		if (!BTEV_LOCKDOWN) {
			$this->trigger_error('Bluetrait Event Viewer deactivated.', E_USER_NOTICE);
		}
		else {
			add_action('shutdown', array($this, 'lockdown_reactivate'));
		}
		return;
	}
	
	//nope BTEV isn't going away
	function lockdown_reactivate() {
		$this->trigger_error("Unauthorised Deactivation Attempt of BTEV.", E_USER_WARNING);
		
		$current = get_option('active_plugins');
		
		if (!isset($current[$this->get_var('plugin_basename')])) {	
			$current[] = $this->get_var('plugin_basename');
			sort($current);
			update_option('active_plugins', $current);
		}
		
		return;
	}
	function trigger_wp_login($user_login) {
		$this->send_alert(array('name' => 'wp_login', 'description' => 'Login Successful "' . $user_login . '"'));
		$this->trigger_error('Login Successful "' . $user_login . '"', E_USER_NOTICE);
		return;
	}
	function trigger_wp_logout($user_login) {
		$this->trigger_error('Logout Successful.', E_USER_NOTICE);
		return;
	}
	function trigger_user_register($user_id) {
		$this->trigger_error('User Added ID ' . $user_id, E_USER_NOTICE);
		return;
	}
	function trigger_lostpassword_post() {
		$this->trigger_error('A password reset was requested.', E_USER_NOTICE);
		return;
	}
	function trigger_profile_update($user_id) {
		$this->trigger_error('A user has updated their profile.', E_USER_NOTICE);
		return;
	}
	function trigger_add_attachment($id) {
		$file = get_attached_file($id);
		$this->trigger_error('File Uploaded "' . $file . '"', E_USER_NOTICE);
		return;
	}
	
	function trigger_login_errors($errors) {
		$this->trigger_error($errors, E_USER_WARNING);
		return $errors;
	}
	function trigger_invalid_username($username) {
		$this->trigger_error('Login Failed "' . $username . '" - Unknown User', E_USER_WARNING);
		btev_send_alert(array('name' => 'wp_login_failed', 'description' => 'Login Failed "' . $username . '" - Unknown User'));

		return;
	}
	function trigger_invalid_password($username) {
		$this->trigger_error('Login Failed "' . $username . '" - Incorrect Password', E_USER_WARNING);
		$this->send_alert(array('name' => 'wp_login_failed', 'description' => 'Login Failed "' . $username . '" - Incorrect Password'));

		return;
	}
	function trigger_switch_theme($theme) {
		$this->trigger_error('Theme Switched To "' . $theme . '"', E_USER_NOTICE);
		return;
	}
	function trigger_publish_post($id) {
		$this->trigger_error('Post Published "' . get_the_title($id) . '"', E_USER_NOTICE);
		return;	
	}
	
	//for Wordpress 2.2 or higher
	function trigger_phpmailer_init(&$phpmailer) {

		$array = $phpmailer->SingleToArray; 
		
		$found = false;
		if (is_array($array) && !empty($array)) {
			foreach($array as $index => $value) {
				if (!empty($value[0])) {
					$found = true;
					if (!empty($phpmailer->Subject)) {
						$this->trigger_error('The email "'.$phpmailer->Subject.'" was sent to "' . $value[0] . '"', E_USER_NOTICE);
					}
					else {
						$this->trigger_error('An email was sent to "' . $value[0] . '"', E_USER_NOTICE);					
					}
				}
			}
		}

		if (!$found) {
			if (!empty($phpmailer->Subject)) {
				$this->trigger_error('The email "'.$phpmailer->Subject.'" was sent', E_USER_NOTICE);
			}
			else {
				$this->trigger_error('An email was sent', E_USER_NOTICE);
			}
		}
		
		return $phpmailer;
	}

	/*
		These two functions are not finished yet.
	*/
	function trigger_updated_option() {
		$this->trigger_error('Option:' . func_num_args(), E_USER_NOTICE);

		$this->trigger_error('Option: "'.$option.'" Updated To: "' . print_r($newvalue, true) . '" From: "'.$oldvalue.'"', E_USER_NOTICE);
		return;
	}
	function trigger_added_option($option, $value = NULL) {
		$this->trigger_error('Option Added: "' . $option . '"', E_USER_NOTICE);
		return;
	}

	/*
	These two plugins are a little hacky but seem to work fine. 
	*/
	function trigger_activate_plugin() {
		$plugin = trim($_GET['plugin']);
		$this->trigger_error('Plugin activated "'.$plugin.'"', E_USER_NOTICE);
		return;
	}
	function trigger_deactivate_plugin() {
		if (isset($_GET['plugin'])) {
			$plugin = trim($_GET['plugin']);
			$this->trigger_error('Plugin deactivated "'.$plugin.'"', E_USER_NOTICE);
		}
		elseif ($_GET['action'] == 'deactivate-all') {
			//are we Deactivating All?
			$this->trigger_error('All plugins have been deactivated.', E_USER_NOTICE);
		}
		
		return;
	}
	//for WordPress 2.5 or higher
	function trigger_login_error($username) {
		
		$user = get_userdatabylogin($username);
		
		if ( !$user || ($user->user_login != $username) ) {
			$this->send_alert(array('name' => 'wp_login_failed', 'description' => 'Login Failed "' . $username . '" - Unknown User'));
			$this->trigger_error('Login Failed "' . $username . '" - Unknown User', E_USER_WARNING);
		}
		else {
			$this->send_alert(array('name' => 'wp_login_failed', 'description' => 'Login Failed "' . $username . '" - Incorrect Password'));
			$this->trigger_error('Login Failed "' . $username . '" - Incorrect Password', E_USER_WARNING);
		}
	}

	//hooks into Wordpress so that we can output "custom content"
	function request_handle() {
		if (isset($_GET['btev_recent_event_rss'])) {
			if (isset($_GET['btev_access_key'])) {
				$this->recent_events_rss($_GET['btev_access_key']);
			}
			else {
				$this->recent_events_rss();
			}
		}
	}
	
	//outputs last 10 events via RSS
	function recent_events_rss($access_key = '') {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');
		
		//should probably move this into a function or something
		$btev_rss_url = get_option('siteurl') . '/?btev_recent_event_rss';

		header('Content-type: text/xml; charset=' . get_option('blog_charset'), true);
		echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' .'>';
		?>
		<rss version="2.0" 
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		>
			<channel>
				<title>BTEV: <?php bloginfo_rss('name'); ?></title>
				<link><?php bloginfo_rss('url') ?></link>
				<description>Recent Events for <?php bloginfo_rss('name'); ?></description>
				<generator>Bluetrait Event Viewer</generator>
				<atom:link href="<?php echo esc_html($btev_rss_url); ?>" rel="self" type="application/rss+xml" />
				<?php
				if ($this->get_config('external_access') && $access_key == $this->get_config('external_access_key')) {
					$event_query = "SELECT `event_id`, `date`, `description`, `type` FROM $btev_tb_events ORDER BY event_id DESC LIMIT 10";		
					$event_result = $wpdb->get_results($event_query, 'ARRAY_A');
					foreach ($event_result as $event_array) { 
						?>
						<item>
							<title><?php echo esc_html($event_array['type']); ?>: <?php echo esc_html($event_array['description']); ?></title>
							<link><?php echo get_option('siteurl'); ?>/wp-admin/<?php echo $this->subpanel_details_link(); ?>&amp;event_id=<?php echo (int) $event_array['event_id']; ?></link>
							<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', date('Y-m-d H:i:s', strtotime($event_array['date'])), false); ?></pubDate>
							<description><![CDATA[Date/Time: <?php echo esc_html($event_array['date']); ?>]]></description>
						</item>
						<?php
					}
				}
				else {
				?>
				<item>
					<title>RSS Feed Disabled or Incorrect Password</title>
				</item>
				<?php
				}
				?>
			</channel>
		</rss>
		<?php
		die();
	}
	
	/*
		===========================
			HTML PAGES BELOW
		===========================
	*/
	
	//html for event viewer
	function subpanel() {
		global $wpdb;
		
		if (isset($_POST['submit'])) {
			if (function_exists('check_admin_referer')) {
				check_admin_referer('btev-clear-logs');
			}
			if (!BTEV_LOCKDOWN) {
				$result = $wpdb->query("DELETE FROM " . $this->get_table('events'));
				$this->trigger_error('Event Log Cleared.', E_USER_NOTICE);
			}
			else {
				$this->trigger_error("Unauthorised Deletion Attempt of BTEV Logs.", E_USER_WARNING);
			}
		}

		if (!empty($_GET['bt_page'])) {
			$page = (int) $_GET['bt_page'];
			if ($page == 0) $page = 1;
		}
		else {
			$page = 1;
		}
		
		if (!isset($_GET['bt_filter'])) {
			$filter = '';
		}
		else {
			$filter = $_GET['bt_filter'];
		}
		
		switch($filter) {
		
			case 1:
					$query = ' WHERE `type` = \'NOTICE\'';
					$filter = 1;
			break;
			
			case 2:
					$query = ' WHERE `type` = \'WARNING\'';
					$filter = 2;
			break;
			
			case 3:
					$query = ' WHERE `type` = \'ERROR\'';
					$filter = 3;
			break;
			
			case 4:
					$query = ' WHERE `type` = \'DEBUG\'';
					$filter = 4;
			break;
		
			default:
					$query = '';
					$filter = '';
		}
			$offset = $page * 100 - 100;
			
			if ($offset < 0) {
				$offset = 0;
			}
			
			$offset = (int) $offset;
			
			$next_page = $page + 1;
			$previous_page = $page - 1;
			if ($previous_page < 1) {
				$previous_page = 1;
			}
			if ($next_page < 1) {
				$next_page = 1;
			}

			$event_query = '
				SELECT '.$this->get_table('events').'.*, ' . $this->get_table('users') . '.display_name'
				.' FROM ' . $this->get_table('events') 
				. ' LEFT JOIN ' . $this->get_table('users') 
					. ' ON ' . $this->get_table('events') . '.user_id = ' . $this->get_table('users') . '.ID ' 
				.  $query 
				. ' ORDER BY event_id DESC LIMIT ' . $offset . ', 100 ';
			
			$event_result = $wpdb->get_results($event_query, 'ARRAY_A');
			
			/*
			$event_result 	= $wpdb->get_results
				(
					$wpdb->prepare('SELECT * FROM ' . $this->get_table('events') . ' WHERE 1 = 1 ORDER BY event_id DESC LIMIT %d, 100', $offset),
					'ARRAY_A'
				);
			*/
						
			$event_page_num = $wpdb->query($event_query);

			if ($event_page_num != 100) {
				$next_page = 1;		
				$link_text = 'First Page';
			}
			else {
				$link_text = 'Next Page';
			}
			
			if (!empty($filter)) {
				$page_filter = '&amp;bt_filter=' . $filter;
			}
			else {
				$page_filter = '';
			}
		?>
		<div class="wrap" style="max-width: 10000px;">
			<h2>Events <a href="<?php echo $this->subpanel_settings_link(); ?>" class="add-new-h2">Settings</a></h2>
			
			<p><a href="<?php echo $this->subpanel_link(); ?>">Display All</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_filter=1">Display Notices</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_filter=2">Display Warnings</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_filter=3">Display Errors</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_filter=4">Display Debug</a></p>
			<p><a href="<?php echo $this->subpanel_link(); ?>&amp;bt_page=<?php echo $previous_page . $page_filter; ?>">&laquo; Previous Page</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_page=<?php echo $next_page . $page_filter; ?>"><?php echo $link_text; ?> &raquo;</a></p>
			<table class="widefat">
				<thead>
					<tr>
						<th>When</th>
						<th>Type</th>
						<th>User</th>
						<th>IP Address</th>
						<th>Description</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>When</th>
						<th>Type</th>
						<th>User</th>
						<th>IP Address</th>
						<th>Description</th>
					</tr>
				</tfoot>
				<tbody>
					<?php 
					if ($event_page_num != 0) {
						$i = 1;
						foreach ($event_result as $event_array) {
						?>
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td><a href="<?php echo $this->subpanel_details_link(); ?>&amp;event_id=<?php echo esc_html($event_array['event_id']); ?>"><?php echo esc_html($event_array['date']); ?></a></td>
							<td><?php echo esc_html($event_array['type']); ?></td>
							<td><?php echo '<a href="user-edit.php?user_id=' . esc_html($event_array['user_id']) . '">' . esc_html($event_array['display_name']). '</a>'; ?></td>
							<td><?php echo esc_html($event_array['ip_address']); ?></td>
							<td><?php echo esc_html($event_array['description']); ?></td>
						</tr>
						<?php 
						$i++; 
						}
					}					
					?>
				</tbody>
			</table>
			<p><a href="<?php echo $this->subpanel_link(); ?>&amp;bt_page=<?php echo $previous_page . $page_filter; ?>">&laquo; Previous Page</a> | <a href="<?php echo $this->subpanel_link(); ?>&amp;bt_page=<?php echo $next_page . $page_filter; ?>"><?php echo $link_text; ?> &raquo;</a></p>
			<script type="text/javascript">
			<!--
			function btev_clear_events() {
				if (confirm("Are you sure you wish to clear the Bluetrait Event log?")){
					return true;
				}
				else{
					return false;
				}
			}
			//-->
			</script>		
			<form method="post" action="" onsubmit="return btev_clear_events(this);">
				<?php
				if (function_exists('wp_nonce_field')) {
					wp_nonce_field('btev-clear-logs');
				}
				?>
				<p><input type="submit" name="submit" value="Clear Logs" class="button-secondary delete" /></p>
			</form>
		</div>
		<?php
	}
	
	//add recent events to dashboard (only for an admin)
	public function activity_box_end() {
		global $wpdb;
		
		if (current_user_can('btev')) {
			$event_query = 'SELECT `date`, `description`, `type`, `event_id` FROM ' . $this->get_table('events') . ' ORDER BY event_id DESC LIMIT 10';		
			$event_result = $wpdb->get_results($event_query, 'ARRAY_A');
			
			?>
			<table style="width: 100%; text-align: center;">
				<thead>
					<tr>
						<th>When</th>
						<th>Type</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$i = 1;
						if (is_array($event_result)) {
							foreach ($event_result as $event_array) { 
								?>
								<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
									<td><a href="<?php echo $this->subpanel_details_link(); ?>&amp;event_id=<?php echo esc_html($event_array['event_id']); ?>"><?php echo esc_html($event_array['date']); ?></a></td>
									<td><?php echo esc_html($event_array['type']); ?></td>
									<td><?php echo esc_html($event_array['description']); ?></td>
								</tr>
								<?php 
								$i++;
							}
						}
					?>
				</tbody>
			</table>
			<p><a href="<?php echo $this->subpanel_link(); ?>" class="button">View All</a></p>
			<?php
		}
	}
	
	
	//html for event viewer settings
	function subpanel_settings() {
		
		if (isset($_POST['submit'])) { 
			if (!BTEV_LOCKDOWN) {
				$this->set_config('display_on_dashboard', $_POST['btev_display_on_dashboard'] ? 1 : 0);
				$this->set_config('set_error_report', $_POST['btev_set_error_report'] ? 1 : 0);
				$this->set_config('log_all', $_POST['btev_log_all'] ? 1 : 0);
				$this->set_config('debug', $_POST['btev_debug'] ? 1 : 0);
				$this->set_config('auto_prune', $_POST['btev_auto_prune'] ? 1 : 0);
				$this->set_config('event_count', (int) $_POST['btev_event_count']);
				$this->set_config('external_access', $_POST['btev_external_access'] ? 1 : 0);
				$this->set_config('external_access_key', $_POST['btev_external_access_key']);
				$this->set_config('log_notice', $_POST['btev_log_notice'] ? 1 : 0);
				$this->set_config('log_warning', $_POST['btev_log_warning'] ? 1 : 0);
				$this->set_config('log_error', $_POST['btev_log_error'] ? 1 : 0);
				
				$array_test = array
				(
					'wp_login' => array('email_alert' => $_POST['btev_event_wp_login'] ? 1 : 0, 'email_list' => ''),
					'wp_login_failed' => array('email_alert' => $_POST['btev_event_wp_login_failed'] ? 1 : 0, 'email_list' => ''),
				);
				
				$this->set_config('events_map', $array_test);
				
				$this->set_config('email_alert_list', $_POST['btev_email_alert_list']);
				
				$this->save_config();
				$this->trigger_error("Bluetrait Event Viewer Settings Updated.", E_USER_NOTICE);
				?>
				<div id="message" class="updated fade"><p><strong><?php _e('Settings Updated.') ?></strong></p></div>
			<?php
			}
			else {
				$this->trigger_error("Unauthorised Update Attempt of BTEV Settings.", E_USER_WARNING);
			}
		} ?>
		<div class="wrap">
			<h2>Bluetrait Event Viewer Settings <a href="<?php echo $this->subpanel_link(); ?>" class="add-new-h2">Events</a></h2>

			<?php $this->display_updates(); ?>
			
			<form action="<?php echo $this->subpanel_settings_link(); ?>" method="post">
				<table class="form-table">
				
				<h3>General Settings</h3>
				<tr valign="top">
					<th scope="row">Display Recent Events</th>
					<td>
						<select name="btev_display_on_dashboard">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('display_on_dashboard') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						On dashboard (frontpage)
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Auto Prune Events</th>
					<td>
						<select name="btev_auto_prune">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('auto_prune') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Events Kept</th>
					<td>
						<input name="btev_event_count" type="text" size="10" value="<?php echo esc_html($this->get_config('event_count')); ?>" />
						<br />
						Number of events kept until Auto Prune deletes them (oldest deleted first)
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">External Access</th>
					<td>
						<select name="btev_external_access">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('external_access') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						Allows RSS events feed to be used
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">External Access Key</th>
					<td>
						<input name="btev_external_access_key" type="text" size="10" value="<?php echo esc_html($this->get_config('external_access_key')); ?>" />
						<br />
						Allows you to password protect RSS events feed
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">RSS URL</th>
					<td>
						<?php
							$btev_rss_url = get_option('siteurl') . '/?btev_recent_event_rss';
							$btev_external = $this->get_config('external_access_key');
							if (!empty($btev_external)) {
								$btev_rss_url .= '&amp;btev_access_key=' . $this->get_config('external_access_key');
							}
						?>
						<input name="btev_rss_url" type="text" size="100" value="<?php echo esc_html($btev_rss_url); ?>" disabled="disabled" />
						<br />
						Editing Disabled
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Log Notices</th>
					<td>
						<select name="btev_log_notice">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('log_notice') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Log Warnings</th>
					<td>
						<select name="btev_log_warning">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('log_warning') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Log Errors</th>
					<td>
						<select name="btev_log_error">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('log_error') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						
					</td>
				</tr>
				
				</table>
				
						
				<h3>Email Alerts</h3>
				<?php $email_config = $this->get_config('events_map'); ?>
				<p>Note email alerts can sometimes slow down the login process.</p>
				<table class="form-table">
				
					<tr valign="top">
						<th scope="row">Email Addresses</th>
						<td>
							<input name="btev_email_alert_list" type="text" size="100" value="<?php echo esc_html($this->get_config('email_alert_list')); ?>" />
							<br />
							For multiple addresses use example@example.com,support@example.net
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Successful Logins</th>
						<td>
							<select name="btev_event_wp_login">
								<option value="0">Off</option>
								<option value="1" <?php if ($email_config['wp_login']['email_alert'] == 1) { echo 'selected="selected"'; } ?>>On</option>
							</select>
							<br />
							
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row">Failed Logins</th>
						<td>
						<select name="btev_event_wp_login_failed">
							<option value="0">Off</option>
							<option value="1" <?php if ($email_config['wp_login_failed']['email_alert'] == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						
						</td>
					</tr>
					
				</table>
				
				<h3>Advanced Settings</h3>
				
				<table class="form-table">
				<p>These settings may affect the performance of your blog and are only recommended if you know what you're doing (i.e writing plugins).</p>
				
				<tr valign="top">
					<th scope="row">PHP Error Reporting</th>
					<td>
						<select name="btev_set_error_report">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('set_error_report') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Full Database Logging</th>
					<td>
						<select name="btev_log_all">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('log_all') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						Logs all errors to the database from PHP Error Reporting (not recommended)
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Debugging</th>
					<td>
						<select name="btev_debug">
							<option value="0">Off</option>
							<option value="1" <?php if ($this->get_config('debug') == 1) { echo 'selected="selected"'; } ?>>On</option>
						</select>
						<br />
						Displays all events in HTML (not recommended, for development purposes only)
					</td>
				</tr>
				
				</table>
								
				<p><input type="submit" name="submit" value="Submit" class="button-primary" /></p>
			</form>
			<br />
			<div id="btev_uninstall">
				<script type="text/javascript">
				<!--
				function btev_uninstall() {
					if (confirm("Are you sure you wish to uninstall Bluetrait Event Viewer?")){
						return true;
					}
					else{
						return false;
					}
				}
				//-->
				</script>
				<form action="<?php echo $this->subpanel_settings_link(); ?>" method="post" onsubmit="return btev_uninstall(this);">
				<?php
					if (function_exists('wp_nonce_field')) {
						wp_nonce_field('btev-uninstall');
					}
					?>
					<p><input type="submit" name="submit_uninstall" value="Uninstall" class="button-secondary delete" /> (This removes all BTEV database entries, including events and settings)</p>
				</form>
			</div>
		</div>
		<?php
	}
	
	//html for event viewer
	function subpanel_event_details() {
		global $wpdb;
		
		$btev_tb_events = $this->get_table('events');
		
		if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
			$event_id = (int) $_GET['event_id'];
		}
		else {
			$event_id = 0;
		}
		
		$event_query = '
		SELECT '.$this->get_table('events').'.*, ' . $this->get_table('users') . '.display_name'
		.' FROM ' . $this->get_table('events') 
		. ' LEFT JOIN ' . $this->get_table('users') 
			. ' ON ' . $this->get_table('events') . '.user_id = ' . $this->get_table('users') . '.ID ' 
		.  ' WHERE event_id = ' . $wpdb->escape($event_id) . ' LIMIT 1';
		
		$events = $wpdb->get_results($event_query, 'ARRAY_A');
		
		$event = $events[0];
		
		?>
		<div class="wrap">
			<h2>Event Details <a href="<?php echo $this->subpanel_link(); ?>" class="add-new-h2">Events</a></h2>
			<div class="tablecontain">
				<?php
				if (!empty($events)) {
				$i = 1;
				?>
				<table class="widefat">
					<thead>
						<tr>
							<th>Name</th>
							<th>Value</th>
						</tr>
					</thead>
					<tbody>
					
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>When</td>
							<td><?php echo esc_html($event['date']); ?></td>
						</tr>
						<?php $i++; ?>
						
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Type</td>
							<td><?php echo esc_html($event['type']); ?></td>
						</tr>
						<?php $i++; ?>
						
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>User</td>
							<td><?php echo '<a href="user-edit.php?user_id=' . esc_html($event['user_id']) . '">' . esc_html($event['display_name']). '</a>'; ?></td>
						</tr>
						<?php $i++; ?>
					
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>User ID</td>
							<td><?php echo esc_html($event['user_id']); ?></td>
						</tr>
						<?php $i++; ?>
						
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>IP Address</td>
							<td><?php echo esc_html($event['ip_address']); ?></td>
						</tr>
						<?php $i++; ?>

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Reverse DNS Entry</td>
							<td><?php echo esc_html(@gethostbyaddr($event['ip_address'])); ?></td>
						</tr>
						<?php $i++; ?>	

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Description</td>
							<td><?php echo esc_html($event['description']); ?></td>
						</tr>
						<?php $i++; ?>		

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Custom Source</td>
							<td><?php echo esc_html($event['custom_source']); ?></td>
						</tr>
						<?php $i++; ?>	

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Source</td>
							<td><?php echo esc_html($event['source']); ?></td>
						</tr>
						<?php $i++; ?>	

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>File Line</td>
							<td><?php echo esc_html($event['file_line']); ?></td>
						</tr>
						<?php $i++; ?>	
						
						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Event ID</td>
							<td><?php echo esc_html($event['event_id']); ?></td>
						</tr>
						<?php $i++; ?>	

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Event Number</td>
							<td><?php echo esc_html($event['event_no']); ?></td>
						</tr>
						<?php $i++; ?>	

						<tr<?php if ($i % 2 == 0) echo ' class="alternate"'; ?>>
							<td>Trace</td>
							<td><?php echo esc_html($event['trace']); ?></td>
						</tr>
						<?php $i++; ?>	
					</tbody>
				</table>
				<?php
				}
				else {
				?>
					<div id="message" class="updated fade">
						<p>Event not found.</p>
					</div>
				<?php
				}
				?>
			</div>
		</div>	
	<?php
		
	}

}
<?php
/*
	Bluetrait Event Viewer Upgrade Class
	Copyright Dalegroup Pty Ltd 2014
	support@dalegroup.net
*/

class btev_upgrade {

	private $btev = NULL;

	function __construct($btev) {
		$this->btev = $btev;
	}

	public function version_info() {

		$update_message = $this->btev->get_config('last_update_response');
		
		$return['code_program_version']				= $this->btev->get_var('version');
		$return['code_database_version']			= $this->btev->get_var('db_version');
		$return['installed_program_version']		= $this->btev->get_config('version');
		$return['installed_database_version']		= $this->btev->get_config('db_version');
		$return['latest_program_version']			= '';
		$return['latest_database_version']			= '';
		
		if (!empty($update_message)) {
								
			if (isset($update_message['version'])) {
				$return['latest_program_version']			= (string) $update_message['version'];
			}		
			
		}	
		
		return $return;
	}
	
	public function get_update_info() {

		$update_array = $this->btev->get_config('last_update_response');
		
		if (is_array($update_array)) {
			return $update_array;
		}
		else {
			return array();
		}
	}
	
	public function update_available() {

		$update_array = $this->btev->get_config('last_update_response');
		
		$update = false;
		
		if (!empty($update_array)) {
			if (isset($update_array['version'])) {
				$version = $this->btev->get_config('version');
				$version = explode('-', $version);
				if (version_compare($version[0], $update_array['version'], '<')) {
					$update = true;
				}
			}
		}
		
		return $update;
	}
	
	private function do_upgrade($array = NULL) {

		for ($i = $this->btev->get_config('db_version') + 1; $i <= $this->btev->get_var('db_version'); $i++) {
			if (method_exists($this, 'dbup_' . $i)) {
				call_user_func(array($this, 'dbup_' . $i));		
			}
		}
		
		return true;
	}
	
	private function dbup_2() {
		
		$version = '2.0.1';
		
		$this->btev->set_config('db_version', 2);		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);	
	}
	
	private function dbup_3() {
		
		$version = '2.0.2';
		
		$this->btev->set_config('db_version', 3);		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);	
	}
	
	
	//upgrade database if needed
	public function upgrade() {
		
		if (!$this->btev->get_config('db_version')) {

			//old style upgrade!
			
			if ($this->btev->get_config('version') != $this->btev->get_var('version')) {
				switch ($this->btev->get_config('version')) {
					case '1.6':
						$this->btev_to_17();
						$this->btev_to_18();
						$this->btev_to_181();
						$this->btev_to_182();
						$this->btev_to_183();
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.7':
						$this->btev_to_18();
						$this->btev_to_181();
						$this->btev_to_182();
						$this->btev_to_183();
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.8':
						$this->btev_to_181();
						$this->btev_to_182();
						$this->btev_to_183();
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.8.1':
						$this->btev_to_182();
						$this->btev_to_183();
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.8.2':
						$this->btev_to_183();
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.8.3':
						$this->btev_to_190();
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.9.0':
						$this->btev_to_191();
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.9.1':
						$this->btev_to_192();
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.9.2':
						$this->btev_to_1921();
						$this->btev_to_193();
						$this->btev_to_200();
					break;

					case '1.9.2.1':
						$this->btev_to_193();
						$this->btev_to_200();
					break;
					
					case '1.9.3':
						$this->btev_to_200();
					break;
					
					default:
						//cannot upgrade
				}
				
			}
		}
		else {
			//new style upgrade
			$this->do_upgrade();
		}
	} 

	/*
		Old Version Upgrade Functions
		Can upgrade from Version 1.6 or higher
	*/

	//version specific upgrade function
	function btev_to_17() {

		$version = '1.7';
		$this->btev->set_config('version', $version);
		$this->btev->save_config();
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
		 
	}

	//version specific upgrade function
	function btev_to_18() {

		$version = '1.8';
		$this->btev->set_config('version', $version);
		
		$this->btev->set_config('external_access', '0');
		$this->btev->set_config('external_access_key', '');
		
		$this->btev->save_config();
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
		 
	}

	//version specific upgrade function
	function btev_to_181() {

		$version = '1.8.1';
		$this->btev->set_config('version', $version);
		
		//make sure cron stuff is up to date
		$this->btev->schedule_tasks();
		
		$this->btev->save_config();
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
		 
	}

	//version specific upgrade function
	function btev_to_182() {
		global $wpdb;
		
		$btev_tb_events = $this->btev->get_table('events');

		$version = '1.8.2';
		
		//for future use
		$wpdb->query("ALTER TABLE $btev_tb_events ADD COLUMN `server_id` int(11)");
		
		//allow a custom "source"
		$wpdb->query("ALTER TABLE $btev_tb_events ADD COLUMN `custom_source` varchar(255)");

		//add new logging options
		$this->btev->set_config('log_notice', 1);
		$this->btev->set_config('log_warning', 1);
		$this->btev->set_config('log_error', 1);
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
		
	}

	//version specific upgrade function
	function btev_to_182_fix() {
		global $wpdb;
		
		$btev_tb_events = $this->btev->get_table('events');
		
		$query = 'SHOW COLUMNS FROM ' . $btev_tb_events;
		$results = $wpdb->get_results($query, 'ARRAY_A');
		
		foreach($results as $column) {
			if ($column['Field'] == 'server_id') {
				$found_col1 = true;
			}
			elseif ($column['Field'] == 'custom_source') {
				$found_col2 = true;
			}
		}
		
		if (!$found_col1 || !$found_col2) {
			if (!$found_col1) {
				//for future use
				$wpdb->query("ALTER TABLE $btev_tb_events ADD COLUMN `server_id` int(11)");
			}
			
			if (!$found_col2) {
				//allow a custom "source"
				$wpdb->query("ALTER TABLE $btev_tb_events ADD COLUMN `custom_source` varchar(255)");
			}
			
			$this->btev->trigger_error('BTEV fixed database upgrade error from 1.8.2', E_USER_NOTICE);
			
		}
		else {
			$this->btev->trigger_error('BTEV 1.8.2 database fix not required', E_USER_NOTICE);
		}
	}

	//version specific upgrade function
	function btev_to_183() {

		$version = '1.8.3';
		
		$this->btev_to_182_fix();

		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
	}

	//version specific upgrade function
	function btev_to_190() {
		global $wpdb;
		
		$btev_tb_events = $this->btev->get_table('events');
		
		$wpdb->query("ALTER TABLE $btev_tb_events ADD COLUMN `event_synced` int(1) unsigned NOT NULL DEFAULT '0'");
		
		$version = '1.9.0';
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
	}

	//version specific upgrade function
	function btev_to_191() {
			
		$version = '1.9.1';
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);
	}

	function btev_to_192() {
		
		$version = '1.9.2';
		
		//should split out into a seperate db table in future (if I get time).
		$array = array
				(
					'wp_login' => array('email_alert' => 0, 'email_list' => ''),
					'wp_login_failed' => array('email_alert' => 0, 'email_list' => ''),
				);
				
		
		$this->btev->set_config('email_alert_list', '');
		
		$this->btev->set_config('events_map', $array);
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);

	}

	function btev_to_1921() {
		
		$version = '1.9.2.1';
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);

	}

	function btev_to_193() {
		
		$version = '1.9.3';
		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);

	}
	
	function btev_to_200() {
		
		$version = '2.0';
		
		$this->btev->set_config('db_version', 1);		
		$this->btev->set_config('version', $version);
		
		$this->btev->save_config();
		
		$this->btev->trigger_error('BTEV database upgraded to version '. $version, E_USER_NOTICE);

	}
	
	
}
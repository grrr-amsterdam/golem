<?php
/**
 * Golem_Cli_Command_Ssh
 * Connects to server
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Ssh extends Golem_Cli_Command {
		
	public function main(array $args = array()) {
		$ssh_helper_path = GOLEM_APPLICATION_PATH . '/../scripts/ssh_helper.rb';
		$deploy_rb_path = APPLICATION_PATH . '/configs/deploy.rb';

		// sanity check
		if (!file_exists($deploy_rb_path)) {
			Garp_Cli::errorOut('No deploy.rb found. I don\'t know what to do. ¯\(°_o)/¯');
			return false;
		}
		$settings = shell_exec("ruby $ssh_helper_path $deploy_rb_path");
		$settings = json_decode($settings, true);
		if (!$settings) {
			Garp_Cli::errorOut("Unable to collect ssh settings. Please check $deploy_rb_path");
			return false;
		}

		// load env specific settings
		if (empty($settings[APPLICATION_ENV])) {
			Garp_Cli::errorOut('No settings found for environment ' . APPLICATION_ENV);
			return false;
		}
		$settings = $settings[APPLICATION_ENV];
		if (empty($settings['server']) || empty($settings['user'])) {
			Garp_Cli::errorOut("'server' and 'user' are required settings. Please check $deploy_rb_path");
			return false;
		}

		// finally! 
		passthru('ssh ' . escapeshellarg($settings['user']) . 
			'@' . escapeshellarg($settings['server']));
	}

}

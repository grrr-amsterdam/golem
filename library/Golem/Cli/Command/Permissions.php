<?php
/**
 * Golem_Cli_Command_Permissions
 * Sets permissions on certain folders
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Permissions extends Golem_Cli_Command {
	/**
 	 * Set permissions on certain folders
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function set(array $args = array()) {
		if (!file_exists('application/data/cache') ||
			!file_exists('application/data/logs') ||
			!file_exists('public/uploads')) {
			Garp_Cli::lineOut('It looks like there are no directories for me to set permissions on.', Garp_Cli::YELLOW);
			return true;
		}
		Garp_Cli::lineOut('Setting permissions on writable folders...');
		passthru('chmod -R 777 application/data/cache');
		passthru('chmod -R 777 application/data/logs');
		passthru('chmod -R 777 public/uploads');

		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
		return true;
	}

	/**
 	 * Help
 	 * @return Boolean
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut(' golem permissions set', Garp_Cli::BLUE);
		return true;
	}
}

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
		$project = $this->_toolkit->getCurrentProject();
		$this->_toolkit->enterProject($project);

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

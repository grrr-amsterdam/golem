<?php
/**
 * Golem_Cli_Command_Open
 * Opens the site in the browser
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Open extends Golem_Cli_Command {
	public function main(array $args = array()) {
		$domain = Zend_Registry::get('config')->app->domain;
		`open http://$domain`;
	}
}

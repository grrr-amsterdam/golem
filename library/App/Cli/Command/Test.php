<?php
/**
 * Garp_Cli_Command_Test
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class App_Cli_Command_Test extends Garp_Cli_Command_Test {
	/**
	 * OVERWRITTEN TO ONLY RUN `DEFAULT` TESTS!
	 * Garp tests make no sense in this case: in Golem there are no models, controllers, views.
	 *
	 */
	public function main(array $args = array()) {
		if (1 === count($args) && !empty($args[0]) && 'help' === strtolower($args[0])) {
			$this->help();
			return;
		}
		// check for illegal options
		$allowedArgs = array('module', 'group');
		foreach ($args as $key => $value) {
			if (!in_array($key, $allowedArgs)) {
				Garp_Cli::errorOut('Illegal option '.$key);
				Garp_Cli::lineOut('Type \'g Test help\' for usage');
				return;
			}
		}

		$command = $this->_command;
		if (!empty($args['group'])) {
			$command .= '--group='.$args['group'].' ';
		}

		if (array_key_exists('module', $args) && $args['module']) {
			if ($args['module'] === 'garp') {
				$path = $this->_garpPath;
				$command .= $path;
			} elseif ($args['module'] === 'default') {
				$path = $this->_appPath;
				$command .= $path;
			} else {
				throw new Exception("Only 'garp' and 'default' are valid configurable modules for the test environment.");
			}
		} else {
			$command .= $this->_appPath;
		}
		system($command);
	}


	/**
 	 * Help method
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Test everything:');
 	   	Garp_Cli::lineOut('  g Test');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Only execute Garp tests:');
 	   	Garp_Cli::lineOut('  g Test --module=garp');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Only execute App tests:');
		Garp_Cli::lineOut('  g Test --module=default');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Execute Garp tests within the group "Cache":');
		Garp_Cli::lineOut('  g Test --module=garp --group=Cache');
		Garp_Cli::lineOut('');
	}
}

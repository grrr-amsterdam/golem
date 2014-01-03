<?php
/**
 * Golem_Cli_Command_Shell
 * Provide an interactive PHP shell
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Shell extends Golem_Cli_Command {

	public function main(array $args = array()) {
		Garp_Cli::lineOut('Welcome to the Garp interactive shell.', Garp_Cli::GREEN);
		Garp_Cli::lineOut('Use Ctrl-C to quit.');

		while (true) {
			$line = Garp_Cli::prompt('');
			ob_start(array($this, '_output'));
			eval($line);
			ob_end_flush();
		}

	}

	protected function _output($buffer) {
		$out = Garp_Cli::lineOut($buffer, Garp_Cli::BLUE, true, false);
		return $out;
	}

}

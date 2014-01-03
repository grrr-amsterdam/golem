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

	public $__result;

	public function main(array $args = array()) {
		Garp_Cli::lineOut('Welcome to the Garp interactive shell.', Garp_Cli::YELLOW);
		Garp_Cli::lineOut('Use Ctrl-C to quit.');

		while (true) {
			// Grab a line of PHP code from the prompt
			if (function_exists('readline') && function_exists('readline_add_history')) {
				$line = readline('> ');
				readline_add_history($line);
			} else {
				$line = Garp_Cli::prompt('');
			}

			// Execute it, and grab its output
			ob_start(array($this, 'output'));

			/**
 			 * Note that $this->__result will be populated, if no target variable is given
 			 * in the expression. In other words, this will populate $this->__result:
 			 * $someModel->fetchAll();
 			 * But this won't:
 			 * $rows = $someModel->fetchAll();
 			 * Because we assume the user wants to do something with the variable.
			 */
			if (!preg_match('/^(\$\w+\s?\=)|print|echo/', $line)) {
				$line = '$this->__result = ' . $line;
			}
			// Fix missing semicolon
			if (substr($line, -1) !== ';') {
				$line .= ';';
			}
			eval($line);
			ob_end_flush();

			// Clear result var
			$this->__result = null;
		}

	}

	/**
 	 * Output the result of the eval'd expression
 	 * @return String 
 	 */
	public function output($buffer) {
		if (!$buffer && !$this->__result) {
			return '';
		}

		$out = '';
		// Always include the output generated by the expression first
		if ($buffer) {
			$out .= Garp_Cli::lineOut($buffer, NULL, true, false);
		}

		// And follow-up with the populated result, if any
		if ($this->__result) {
			// Try to format as user-friendly as possible
			$this->__result = is_object($this->__result) && method_exists($this->__result, 'toArray') ? $this->__result->toArray() : $this->__result;
			$this->__result = var_export($this->__result, true);
			$out .= Garp_Cli::lineOut($this->__result, Garp_Cli::BLUE, true, false);
		}

		return $out;
	}

}

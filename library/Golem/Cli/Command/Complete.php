<?php
/**
 * Golem_Cli_Command_Complete
 * Assists in bash completion
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_Complete extends Golem_Cli_Command {

	protected $_ignored_commands = array('complete', 'exception');

	public function main(array $args = array()) {
		// 0 => "g"
		unset($args[0]);

		/**
 		 *
 		 * @todo Finish this. It would be nice to be able to tab-complete it
 		 * all using Golem, but for now it's just too complex. The current 
 		 * setup with Garp_Cli_Command::complete() works, but it would be cool
 		 * to be able to also complete arguments and whatnot.
 		 *
 		 */
		// First command
		//if (count($args) <= 1) {
			$out = $this->_completeFirstArg($args);
		/*
		} else {
			// Assume: 0 = cmd, 1 = action, rest is args
			try {
				$cmd = $this->_toolkit->getCommandClass($args[0]);
			} catch (Golem_Exception $e) {
				$out = array('neen');
				return false;
			}
			$out = $cmd->getPublicMethods();
		}
		 */
		Garp_Cli::lineOut(implode(' ', $out));
		return true;
	}

	/**
 	 * Complete first argument (can be an actual command when in a project, or the name of a project
 	 */
	protected function _completeFirstArg($args) {
		if (!$this->_toolkit->getCurrentProject()) {
			// Only sys commands are available
			return $this->_toolkit->getSysCommands();
		}
		$namespaces = $this->_toolkit->getAvailableCommandNamespaces();
		$out = array();
		foreach ($namespaces as $namespace) {
			$this->_iterate_namespace($namespace, $out);
		}
		return $out;
	}		

	/**
 	 * Iterate possible command namespace
 	 */
	protected function _iterate_namespace($namespace, &$out) {
		$dir = APPLICATION_PATH . '/../library/' . $namespace . '/Cli/Command/';
		if (!is_dir($dir) || !is_readable($dir)) {
			return;
		}

		$dir_iterator = new DirectoryIterator($dir);
		foreach ($dir_iterator as $fileinfo) {
			$this->_iterate_dir($fileinfo, $out);
    	}
	}

	/**
 	 * Iterate library directory
 	 */
	protected function _iterate_dir(DirectoryIterator $fileinfo, &$out) {
    	if ($fileinfo->isDot() || !$fileinfo->isFile()) {
			return;
		}
        $filename = $fileinfo->getFilename();
		$cmd_name = substr($filename, 0, strrpos($filename, '.'));
		$cmd_name = strtolower($cmd_name);
		if (in_array($cmd_name, $this->_ignored_commands)) {
			return;
		}
		$out[] = $cmd_name;
	}		

}

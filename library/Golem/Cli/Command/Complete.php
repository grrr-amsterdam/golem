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
		$namespaces = $this->_toolkit->getAvailableCommandNamespaces();
		$out = array();
		foreach ($namespaces as $namespace) {
			$this->_iterate_namespace($namespace, $out);
		}
		Garp_Cli::lineOut(implode(' ', $out));
		return true;
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

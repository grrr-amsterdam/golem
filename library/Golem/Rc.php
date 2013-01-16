<?php
/**
 * Golem_Rc
 * Represents the .golemrc config file.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem
 */
class Golem_Rc {
	/**#@+
 	 * Constants for data keys
 	 * @var String
 	 */
	const WORKSPACE = 'workspace';
    /**#@-*/


	/**
 	 * Questions that return the above constants.
 	 * @var Array
 	 */
	protected $_questions = array(
		self::WORKSPACE => 'What is your workspace?'
	);


	/**
 	 * Defaults to the above constants.
 	 * Set in self::_setDefaults().
 	 * @var Array
 	 */
	protected $_defaults = array();

	
	/**
 	 * Path to file
 	 * @var String
 	 */
	protected $_file;


	/**
 	 * Config data. This is static so that we can keep it in memory 
 	 * across instances. This is useful when the .golemrc file fails 
 	 * to be written. A user can still use golem but has to configure 
 	 * it again next time.
 	 *
 	 * @var Array
 	 */
	protected static $_data;


	/**
 	 * Class constructor
 	 * @param String $file Path to config file
 	 * @return Void
 	 */
	public function __construct($file) {
		$this->_file = $file;
		$this->_setDefaults();
	}


	/**
 	 * @return Boolean Wether the file exists.
 	 */
	public function exists() {
		return file_exists($this->_file);
	}


	/**
 	 * Prompt the user for the missing data
 	 * @param String $key Ask for a specific key
 	 * @return Void
 	 */
	public function askForData($key = null) {
		$data = array();
		foreach ($this->_questions as $key => $question) {
			$value = Garp_Cli::prompt($question.' ('.$this->_defaults[$key].')');
			if (!$value) {
				$value = $this->_defaults[$key];
			}
			$data[$key] = $value;
		}
		self::$_data = $data;
	}


	/**
 	 * Return the contents of .golemrc
 	 * @return Array
 	 */
	public function getData() {
		if (!self::$_data) {
			self::$_data = include $this->_file;
		}
		return self::$_data;
	}


	/**
 	 * Write config to the file.
 	 * @return Boolean
 	 */
	public function write() {
		return file_put_contents($this->_file, self::$_data);
	}


	/**
 	 * Set defaults for config values.
 	 * In method form because PHP does not allow expressions in property declarations.
 	 * @return Void
 	 */
	protected function _setDefaults() {
		$this->_defaults = array(
			self::WORKSPACE => realpath(APPLICATION_PATH.'/../../')
		);
	}
}

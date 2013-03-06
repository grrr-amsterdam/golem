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
	const WORKSPACE          = 'workspace';
	const HOSTS_FILE         = 'hosts_file';
	const APACHE_VHOSTS_FILE = 'apache_vhosts_file';
    /**#@-*/


	/**
 	 * Questions that return the above constants.
 	 * @var Array
 	 */
	protected $_questions = array(
		self::WORKSPACE => 'Where is your workspace?',
		self::HOSTS_FILE => 'Where is your hosts file?',
		self::APACHE_VHOSTS_FILE => 'Where is your vhosts file?',
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
 	 * Check wether all keys are present. 
 	 * Handy for updating requirements.
 	 * @return Boolean
 	 */
	public function isConfigurationComplete() {
		if ($this->exists()) {
			$data = $this->getData();
			$existingKeys = array_keys($data);
			$requiredKeys = array_keys($this->_questions);
			return !count(array_diff($requiredKeys, $existingKeys));
		}
		return false;
	}


	/**
 	 * Prompt the user for the missing data
 	 * @param String $key Ask for a specific key
 	 * @return Void
 	 */
	public function askForData($key = null) {
		$data = array();
		// If key is given, append that key to existing data
		if ($key && !empty($this->_questions[$key])) {
			$data = self::$_data;
			$question = $this->_questions[$key];
			$value = Garp_Cli::prompt($question.' ('.$this->_defaults[$key].')');
			if (!$value) {
				$value = $this->_defaults[$key];
			}
			$data[$key] = $value;
		} else {
			foreach ($this->_questions as $key => $question) {
				$value = Garp_Cli::prompt($question.' ('.$this->_defaults[$key].')');
				if (!$value) {
					$value = $this->_defaults[$key];
				}
				$data[$key] = $value;
			}
		}
		self::$_data = $data;
	}


	/**
 	 * Ask only for missing data
 	 * @return Void
 	 */
	public function askForMissingData() {
		$existingKeys = array_keys(self::$_data);
		$requiredKeys = array_keys($this->_questions);
		$missingKeys = array_diff($requiredKeys, $existingKeys);
		foreach ($missingKeys as $key) {
			$this->askForData($key);
		}
	} 


	/**
 	 * Return the contents of .golemrc
 	 * @param String $key
 	 * @return Array
 	 */
	public function getData($key = null) {
		if (!self::$_data) {
			self::$_data = include $this->_file;
		}
		if ($key) {
			if (!isset(self::$_data[$key])) {
				throw new InvalidArgumentException("Key $key not found in GolemRc.");
			}
			return self::$_data[$key];
		}
		return self::$_data;
	}


	/**
 	 * Write config to the file.
 	 * @return Boolean
 	 * @todo Move array2phpStatement to utility class outside of Spawn namespace
 	 */
	public function write() {
		$phpArray = Garp_Model_Spawn_Util::array2phpStatement(self::$_data);
		$phpReturnStatement  = "<?php\n";
		$phpReturnStatement .= "return $phpArray;";
		return file_put_contents($this->_file, $phpReturnStatement);
	}


	/**
 	 * Set defaults for config values.
 	 * In method form because PHP does not allow expressions in property declarations.
 	 * @return Void
 	 */
	protected function _setDefaults() {
		$this->_defaults = array(
			self::WORKSPACE => realpath(GOLEM_APPLICATION_PATH.'/../../'),
			self::HOSTS_FILE => '/etc/hosts',
			self::APACHE_VHOSTS_FILE => '/etc/apache2/extra/httpd-vhosts.conf'
		);
	}
}

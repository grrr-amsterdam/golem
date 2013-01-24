<?php
/**
 * Golem_Toolkit
 * Manages all your Golem hopes and dreams.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Golem
 */
class Golem_Toolkit {
	/**
 	 * Relative location of .golemrc
 	 * @var String
 	 */
	const GOLEMRC = '/data/.golemrc';


	/**
 	 * @var Golem_Toolkit
 	 */
	protected static $_instance;


	/**
 	 * Golem configuration
 	 * @var Golem_Rc
 	 */
	protected $_rc;


	/**
 	 * Project folders
 	 * @var Array
 	 */
	protected $_projects;


	/**
 	 * Singleton interface
 	 * @param Golem_Rc $golemRc
 	 * @return Golem_Toolkit
 	 */
	public static function getInstance(Golem_Rc $golemRc) {
		if (!self::$_instance) {
			self::$_instance = new Golem_Toolkit($golemRc);
		}
		return self::$_instance;
	}


	/**
 	 * Class constructor
 	 * @param Golem_Rc $golemRc
 	 * @return Void
 	 */
	protected function __construct(Golem_Rc $golemRc) {
		$this->setRc($golemRc);
	}


	/**
 	 * Main startup method
 	 * @return Boolean Wether the command was processed successfully.
 	 */
	public function main() {
		// Create application, bootstrap, and run
		$application = new Garp_Application(
			APPLICATION_ENV, 
			APPLICATION_PATH.'/configs/application.ini'
		);
		$application->bootstrap();

		// Save the application in the registry, so it can be used by commands.
		Zend_Registry::set('application', $application);

		// Process the command
		$args = Garp_Cli::parseArgs($_SERVER['argv']);
		list($cmd, $action, $args) = $this->_parseArgs($args);
		$success = $this->executeCommand($cmd, $action, $args);
		return $success;
	}


	/**
 	 * Execute a Garp_Cli_Command.
 	 * @param Mixed $cmd Either a Garp_Cli_Command instance, or the suffix of its classname.
 	 * @param String $action Which method to execute on the class
 	 * @param Array $args 
 	 * @return Boolean
 	 */
	public function executeCommand($cmd, $action, array $args = array()) {
		if (!$cmd instanceof Garp_Cli_Command) {
			$cmdClassName = 'Golem_Cli_Command_'.ucfirst(strtolower($cmd));
			$cmd = new $cmdClassName($this);
		}
		$response = $cmd->{$action}($args);
		return $response;
	}


	/**
 	 * Parse commandline arguments into cmd - action pairs
 	 * @param Array $args 
	 * @return Array Containing (0) Cmd name, (1) Action name, (2) Arguments
 	 */
	protected function _parseArgs(array $args) {
		if (empty($args)) {
			return array('sys', 'help', array());
		}
	}


	/**
 	 * Set which Golem_Rc to use.
 	 * @param Golem_Rc $golemRc
 	 * @return Void
 	 */
	public function setRc(Golem_Rc $golemRc) {
		if (!$golemRc->isConfigurationComplete()) {
			// make sure we have a working setup
			$success = $this->executeCommand('sys', 'configure');
			// @todo Is it right to exit here? It feels cleaner to do the setup, then exit, then execute the original command.
			Garp_Cli::halt($success);
		}
		$this->_rc = $golemRc;
	}


	/**
 	 * Get GolemRc
 	 * @return Golem_Rc
 	 */
	public function getRc() {
		return $this->_rc;
	}


	/**
 	 * Get Garp projects
 	 * @return Array
 	 */
	public function getProjects() {
		if (!$this->_projects) {
			$this->_projects = $this->_collectProjectList();
		}
		return $this->_projects;
	}


	/**
 	 * Iterate workspace directory to gather project folders.
 	 * @return Array
 	 */
	protected function _collectProjectList() {
		$workspace = $this->_rc->getData(Golem_Rc::WORKSPACE);
		$projects = array();
		$dirIterator = new DirectoryIterator($workspace);
		foreach ($dirIterator as $fileInfo) {
			if ($fileInfo->isDir()) {
				$projectPath = $fileInfo->getPath().DIRECTORY_SEPARATOR.$fileInfo->getFilename();
				if ($this->_isGarpProject($projectPath)) {
					$projects[] = $fileInfo->getFilename();
				}
			}
		}
		return $projects;
	}


	/**
 	 * Check if a given path is a Garp project.
 	 * Note: you can never know for sure, we simply use duck typing to see if 
 	 * it looks like a Garp setup.
 	 * @param String $path
 	 * @return Boolean
 	 */
	protected function _isGarpProject($path) {
		$garpFolder   = $path.DIRECTORY_SEPARATOR.'garp';
		$appFolder    = $path.DIRECTORY_SEPARATOR.'application';
		$publicFolder = $path.DIRECTORY_SEPARATOR.'public';
		return file_exists($garpFolder) &&
			file_exists($appFolder) &&
			file_exists($publicFolder);
	}
}

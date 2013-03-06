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
 	 * Commands that do not act upon an existing project
 	 * @var Array
 	 */
	protected static $_sysCommands = array(
		'sys', 'build', 'checkout'
	);


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
		// Autoloading is presumably not yet setup at this point. Manually include Garp_Cli.
		if (!class_exists('Garp_Loader') && !class_exists('Garp_Cli')) {
			require_once(GOLEM_APPLICATION_PATH.'/../library/Garp/Cli.php');
		}
		// Figure out the project, command and arguments
		$args = Garp_Cli::parseArgs($_SERVER['argv']);
		list($project, $cmd, $args) = $this->_parseArgs($args);

		$applicationEnv = $this->_determineApplicationEnv($args);
		if (!$applicationEnv) {
			$this->_throwException('MissingEnvironment', 'APPLICATION_ENV is not set. Please set it as a shell variable or pass it along as an argument, like so: --e=development');
		}
		define('APPLICATION_ENV', $applicationEnv);

 		// The following makes sure the right configuration is used for a given project.
		$garpInitPath = GOLEM_APPLICATION_PATH.'/../garp/application/init.php';
		if ($project) {
			if ($this->getCurrentProject() != $project) {
				$this->enterProject($project);
			}
			$garpInitPath = 'garp/application/init.php';
		}
		// Note: constants such as APPLICATION_PATH are set from the init file.
		require_once($garpInitPath);

		// Create application and bootstrap
		$application = new Garp_Application(APPLICATION_ENV, APPLICATION_PATH.'/configs/application.ini');
		$application->bootstrap();

		// Save the application in the registry, so it can be used by commands.
		Zend_Registry::set('application', $application);
		// Last but not least, execute the command.
		$success = $this->executeCommand($cmd, $args);
		return $success;
	}


	/**
 	 * Execute a Garp_Cli_Command.
 	 * @param Mixed $cmd Either a Garp_Cli_Command instance, or the suffix of its classname.
 	 * @param Array $args 
 	 * @return Boolean
 	 */
	public function executeCommand($cmd, array $args = array()) {
		if (!$cmd instanceof Garp_Cli_Command) {
			$cmd = $this->getCommandClass($cmd);
		}
		$response = $cmd->main($args);
		return $response;
	}


	/**
 	 * Parse commandline arguments into project, command and arguments
 	 * @param Array $args 
	 * @return Array Containing (0) Project name, (1) Cmd name, (2) Arguments
 	 */
	protected function _parseArgs(array $args) {
		if (empty($args)) {
			return array(null, 'sys', array('help'));
		}

		$project = $cmd = null;
		$cmdArgs = array();

		// Pwd is not in a project: project needs to be index 0
		// Example: golem grrr.nl admin add
		$projectIndex = 0;
		$cmdIndex = 1;
		$isSysCommand = array_key_exists(0, $args) && $this->isSysCommand($args[0]);
		if (($project = $this->getCurrentProject()) || $isSysCommand) {
			// Pwd is in a project: project is irrelevant and cmd needs to be index 0.
			// Sys commands also do not require a project because they act upon the entire workspace.
			// Example: golem admin add
			$projectIndex = -1;
			$cmdIndex = 0;
		}

		if (empty($args[$cmdIndex])) {
			$this->_throwException('InvalidArgs', 'No command can be extracted from the given arguments.');
		}

		if ($projectIndex !== -1) {
			$project = $args[$projectIndex];
		}
		$cmd     = $args[$cmdIndex];
		$cmdArgs = array_slice($args, $cmdIndex+1);
		return array($project, $cmd, $cmdArgs);
	}


	/**
 	 * Load command class
 	 * @param String $cmd Suffix of the command
 	 * @return Garp_Cli_Command
 	 * @todo Should a sys command always come from GOLEM_APPLICATION_PATH?
 	 * @todo Another implication: in a project, you always execute the local version of the command. Shouldn't that be Golem's version?
 	 */
	public function getCommandClass($cmd) {
		$cmdClassName = 'Cli_Command_'.ucfirst(strtolower($cmd));
		$prefixes = array('App');
		// Only try the Golem prefix if the Golem dir is actually there.
		if (is_dir(APPLICATION_PATH.'/../library/Golem')) {
			$prefixes[] = 'Golem';
		}
		$prefixes[] = 'Garp';
		$garpLoader = Garp_Loader::getInstance();
		foreach ($prefixes as $prefix) {
			$fullCmdClassName = $prefix.'_'.$cmdClassName;
			if ($garpLoader->isLoadable($fullCmdClassName)) {
				$cmd = new $fullCmdClassName($this);
				if (!$cmd instanceof Garp_Cli_Command) {
					$this->_throwException('InvalidCmd', 'Command '.$cmd.' must be of type Garp_Cli_Command');
				}
				return $cmd;
			}
		}
		$this->_throwException('InvalidCmd', 'Command '.$cmd.' not found '.
			'in any of the available namespaces. ('.implode(',', $prefixes).')');
	}


	/**
 	 * Check if given command is sys command (a command that does not act upon a project).
 	 * @param String $cmd
 	 * @return Boolean
 	 */
	public function isSysCommand($cmd) {
		return in_array($cmd, self::$_sysCommands);
	}


	/**
 	 * Set which Golem_Rc to use.
 	 * @param Golem_Rc $golemRc
 	 * @return Void
 	 */
	public function setRc(Golem_Rc $golemRc) {
		$this->_rc = $golemRc;
		if (!$golemRc->isConfigurationComplete()) {
			// make sure we have a working setup
			$success = $this->executeCommand('sys', array('configure'));
			// @todo Is it right to exit here? If a developer was executing a command, he needs to do it again.
			Garp_Cli::halt($success);
		}
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
			if (!$fileInfo->isDir()) {
				continue;
			}
			$projectPath = $fileInfo->getPath().DIRECTORY_SEPARATOR.$fileInfo->getFilename();
			if ($this->_isGarpProject($projectPath)) {
				$projects[] = $fileInfo->getFilename();
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


	/**
 	 * Check if our current pwd is in a Garp project.
 	 * Right now only true if you're in the root of a project.
 	 * @todo Extend this, so you can execute golem from deep within a project directory?
 	 * @return String The name of the project, or Boolean if you're not inside a project dir.
 	 */
	public function getCurrentProject() {
		$pwd = getcwd();
		$currFolder = basename($pwd);
		$projects = $this->getProjects();
		if (in_array($currFolder, $projects)) {
			return $currFolder;
		}
		return false;
	}


	/**
 	 * Cd into the given project.
 	 * @param String $project
 	 * @return Void
 	 */
	public function enterProject($project) {
		$workspace = $this->_rc->getData(Golem_Rc::WORKSPACE);
		$projectPath = $workspace.DIRECTORY_SEPARATOR.$project;
		if (!is_dir($projectPath)) {
			$this->_throwException('InvalidProject', 'Unable to enter project '.$project);
		}
		chdir($projectPath);
	}


	/**
 	 * Figure out the current APPLICATION_ENV
 	 * @param Array $args The arguments as parsed by self::_parseArgs()
 	 * @return String The environment
 	 */
	protected function _determineApplicationEnv(&$args) {
		// Check if APPLICATION_ENV is passed along as an argument.
		foreach ($args as $key => $arg) {
			if ($key === 'APPLICATION_ENV' || $key === 'e') {
				$env = $args[$key];
				// Remove APPLICATION_ENV from the arguments list
				unset($args[$key]);
				return $env;
			}
		}
		// Not found as argument? Let's see if it's defined as environment variable
		if (getenv('APPLICATION_ENV')) {
			return getenv('APPLICATION_ENV');
		}

		// Still nothing? That's not right...
		return null;
	}


	/**
 	 * Throw an exception.
 	 * Use this method to throw exception, it will make sure the exception class is loaded.
 	 * (we cannot assume autoloading is setup at this point)
 	 * @param String $type Type of exception (And class suffix)
 	 * @param String $err Error message
 	 * @return Void 
 	 * @throws Golem_Exception
 	 */
	protected function _throwException($type, $err) {
		$className = 'Golem_Exception_'.$type;
		require_once(GOLEM_APPLICATION_PATH.'/../library/Golem/Exception/'.$type.'.php');
		throw new $className($err);
	}
}

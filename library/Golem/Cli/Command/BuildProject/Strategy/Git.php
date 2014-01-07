<?php
/**
 * Golem_Cli_Command_BuildProject_Git
 * Build project using Git
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.5.0
 * @package      Golem_Cli_Command_BuildProject
 */
class Golem_Cli_Command_BuildProject_Strategy_Git implements Golem_Cli_Command_BuildProject_Strategy_Interface {
	/**
	 * Garp3 repository URL
	 * @var String
	 */
	const GARP3_REPO = 'git@code.grrr.nl:grrr/garp3';

	/**
	 * Zend Framework repository
	 * @var String
	 */
	const ZEND_REPO = 'http://framework.zend.com/svn/framework/standard/trunk/library/Zend';

	/**
 	 * Project name
 	 * @var String
 	 */
	protected $_projectName;

	/**
 	 * Project repository
 	 * @var String
 	 */
	protected $_projectRepository;

	/**
 	 * Project root
 	 * @var String
 	 */
	protected $_projectRoot;

	/**
 	 * Class constructor
 	 * @param String $projectName
 	 * @param String $repository
 	 * @return Void
 	 */
	public function __construct($projectName, $repository = null) {
		$repository = $repository ?: 'git@code.grrr.nl:grrr/'.$projectName;

		$this->_projectName = $projectName;
		$this->_projectRepository = $repository;
		$this->_projectRoot = getcwd().'/'.$projectName;
	}

	/**
 	 * Build all the things
 	 * @return Void
 	 */
	public function build() {
		if (file_exists($this->_projectRoot)) {
			Garp_Cli::errorOut('The project folder already exists. I don\'t know what to do.');
			return false;
		}

		// sanity check: is the repository accessible?
		$checkCommand = 'if ( git ls-remote '.$this->_projectRepository.' &> /dev/null ); then echo \'accessible\'; fi';
		$checkResult  = trim(`$checkCommand`);
		if ('accessible' !== $checkResult) {
			Garp_Cli::errorOut('The repository you\'re trying to checkout either does not exist or you do not have access rights.');
			return false;
		}
		// start by checking out the project repo
		$this->_checkOutProjectRepository();

		chdir($this->_projectRoot);

		$this->_createScaffolding();
		$this->_setupGarp();

		Garp_Cli::lineOut('Project created successfully. Thanks for watching.', Garp_Cli::GREEN);
		return true;
	}

	/**
 	 * Check out project repo
 	 */
	protected function _checkOutProjectRepository() {
		Garp_Cli::lineOut(' # Cloning project repository: '.$this->_projectRepository, Garp_Cli::YELLOW);
		passthru('git clone '.$this->_projectRepository);
		Garp_Cli::lineOut('');
	}

	/**
 	 * Create project scaffolding
 	 */
	protected function _createScaffolding() {
		Garp_Cli::lineOut(' # Creating scaffolding', Garp_Cli::YELLOW);

		// Git subtree needs a commit in order to be able to merge trees.
		// Make that commit here
		passthru('touch .gitignore');
		passthru('git add .gitignore');
		passthru('git commit -m "Initial commit."');

		// Copy scaffold files
		$scaffold = new Golem_Scaffold(
			'git@code.grrr.nl:grrr/garp_scaffold',
			getcwd()
		);
		$scaffold->setup();

		// Commit scaffold
		passthru('git add .');
		passthru('git commit -m "Created scaffolding."');

		Garp_Cli::lineOut('');
	}

	/**
 	 * Setup Garp subtree
 	 */
	protected function _setupGarp() {
		Garp_Cli::lineOut(' # Adding Garp subtree', Garp_Cli::YELLOW);
		passthru('git subtree add -P garp --squash ' . self::GARP3_REPO . ' master');

		passthru('git add .');
		passthru('git commit -m "Added Garp."');

		Garp_Cli::lineOut('');
	}
}

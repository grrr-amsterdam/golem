<?php
/**
 * Golem_Scaffold
 * Create project scaffolding.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.5.0
 * @package      Golem
 */
class Golem_Scaffold {
	/**
 	 * Scaffold repo
 	 * @var String
 	 */
	protected $_scaffoldRepo;

	/**
 	 * Path to destination
 	 * @var String
 	 */
	protected $_target;

	/**
 	 * Class constructor
 	 * @param String $scaffoldRepo Scaffold repository
 	 * @param String $target Path to destination directory
 	 * @return Void
 	 */
	public function __construct($scaffoldRepo, $target) {
		$this->setScaffoldRepo($scaffoldRepo);
		$this->setTarget($target);
	}

	/**
 	 * Setup the scaffolding in the destination directory.
 	 * @return Void
 	 */
	public function setup() {
		// First, add the scaffolding as a subtree in the "scaffold" folder
		passthru('git subtree add -P scaffold --squash ' . $this->_scaffoldRepo . ' master');

		// Then, iterate over the scaffold folder and take the parts you need
		Garp_Cli::lineOut('Moving scaffold files into place', Garp_Cli::BLUE);
		$scaffoldDir = $this->_target . DIRECTORY_SEPARATOR . 'scaffold';
		$dir = new DirectoryIterator($scaffoldDir);
		foreach ($dir as $node) {
			$this->_processNode($node);
		}

		// We're done with the 'scaffold' folder, remove it
		Garp_Cli::lineOut('Removing temp dir \'scaffold\'', Garp_Cli::BLUE);
		passthru('git rm -rq scaffold');
		passthru('git commit -m "Removed scaffold folder."');
	}

	/**
 	 * Process a node in the scaffold dir
 	 */
	protected function _processNode($node) {
		if ($node->isDot()) {
			return;
		}

		/**
 		 * Unforunately, we have to ignore Garp and clone it separately.
 		 * Git subtree will get confused later on if we try to use this 
 		 * folder as a subtree without going through the proper channels.
 		 */
		if ($node->getBasename() == 'garp') {
			return;
		}

		passthru('cp -R ' . $node->getPathname() . ' ' . $this->_target.DIRECTORY_SEPARATOR);
	}

	/**
 	 * Set scaffold repo
 	 * @param String $repo
 	 * @return Void
 	 */
	public function setScaffoldRepo($repo) {
		$this->_scaffoldRepo = $repo;
	}

	/**
 	 * Get scaffold repo
 	 * @return String
 	 */
	public function getScaffoldRepo() {
		return $this->_scaffoldRepo;
	}

	/**
 	 * Set target directory
 	 * @param String $target
 	 * @return Void
 	 */
	public function setTarget($target) {
		$this->_target = rtrim($target, DIRECTORY_SEPARATOR);
	}

	/**
 	 * Get target directory
	 * @return String
	 */
	public function getTarget() {
		return $this->_target;
	} 
}

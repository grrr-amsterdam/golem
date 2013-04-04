<?php
/**
 * Golem_Scaffold
 * Create project scaffolding
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Scaffold
 */
class Golem_Scaffold {
	/**
 	 * Path to template
 	 * @var String
 	 */
	protected $_from;

	/**
 	 * Path to destination
 	 * @var String
 	 */
	protected $_to;

	/**
 	 * Class constructor
 	 * @param String $from Path to scaffold directory
 	 * @param String $to Path to destination directory
 	 * @return Void
 	 */
	public function __construct($from, $to) {
		$this->setFrom($from);
		$this->setTo($to);
	}

	/**
 	 * Setup the scaffolding in the destination directory.
 	 * @return Void
 	 */
	public function setup() {
		$dir = new DirectoryIterator($this->_from);
		foreach ($dir as $node) {
			if (!$node->isDot()) {
				passthru('cp -R '.$node->getPathname().' '.$this->_to.DIRECTORY_SEPARATOR);
			}
		}
	}

	/**
 	 * Set from directory
 	 * @param String $from
 	 * @return Void
 	 */
	public function setFrom($from) {
		$this->_from = rtrim($from, DIRECTORY_SEPARATOR);
	}

	/**
 	 * Get from directory
 	 * @return String
 	 */
	public function getFrom() {
		return $this->_from;
	}

	/**
 	 * Set to directory
 	 * @param String $to
 	 * @return Void
 	 */
	public function setTo($to) {
		$this->_to = rtrim($to, DIRECTORY_SEPARATOR);
	}

	/**
 	 * Get to directory
	 * @return String
	 */
	public function getTo() {
		return $this->_to;
	} 
}

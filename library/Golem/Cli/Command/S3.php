<?php
/**
 * Golem_Cli_Command_S3
 * Wrapper around awscmd, specific to s3 and s3api commands.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Golem_Cli_Command
 */
class Golem_Cli_Command_S3 extends Golem_Cli_Command_Aws {

	/**
 	 * Create a new bucket on S3.
 	 * This defaults to the configured bucket.
 	 */
	public function makeBucket(array $args = array()) {
		$bucket = isset($args[0]) ? $args[0] : null;
		if (is_null($bucket)) {
			$config = Zend_Registry::get('config');
			$bucket = isset($config->cdn->s3->bucket) ? $config->cdn->s3->bucket : null;
		}
		if (is_null($bucket)) {
			Garp_Cli::errorOut('No bucket configured');
			return false;
		}
		$args = array(
			's3://' . $bucket
		);
		return $this->s3('mb', $args);
	}

	/** Alias for makeBucket */
	public function mb(array $args = array()) {
		return $this->makeBucket($args);
	}

	public function ls(array $args = array()) {
		if (empty(Zend_Registry::get('config')->cdn->s3->bucket)) {
			Garp_Cli::errorOut('No bucket configured');
			return false;
		}
		$path = rtrim('s3://' . Zend_Registry::get('config')->cdn->s3->bucket, DIRECTORY_SEPARATOR);
		if (isset($args[0])) {
			$path .= DIRECTORY_SEPARATOR . $args[0];
		}
		$args = array($path);
		return $this->s3('ls', $args);
	}

	/**
 	 * Set CORS settings on bucket
 	 * @todo Make CORS options configurable? Do you ever want full-fletched control over these?
 	 */
	public function setCors() {
		$corsConfig = array(
			'CORSRules' => array(array(
				'AllowedHeaders' => array('*'),
				'AllowedMethods' => array('GET'),
				'AllowedOrigins' => array('*'),
			))
		);
		$args = array(
			'--bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
			'--cors-configuration' => "'" . json_encode($corsConfig) . "'"
		);
		return $this->s3api('put-bucket-cors', $args); 
	}

	public function getCors() {
		$args = array(
			'--bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
		);
		return $this->s3api('get-bucket-cors', $args);
	}
}

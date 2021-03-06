<?php
/**
 * Mock models used by SluggableTest
 * These test generation of slugs in multilingual models.
 * 
 * @note: These models mimic what is generated by the spawner, just like
 * the SQL generated by the setUp method of Garp_Model_Behavior_SluggableTest.
 * If the spawner changes its output, it's a good idea to change these
 * mocks as well.
 */
class Mocks_Model_Sluggable2TestI18n extends Garp_Model_Db {
	protected $_name = '_sluggable_test_2_i18n';
	protected $_unilingualModel = 'Mocks_Model_Sluggable2Test';

	protected $_referenceMap = array(
		'Event' => array(
			'refTableClass' => 'Mocks_Model_Sluggable2Test',
			'columns' => '_sluggable_test_2_id',
			'refColumns' => 'id'
		)
	);

	public function init() {
		$this->registerObserver(
			new Garp_Model_Behavior_Sluggable(array(
				'baseField' => 'name', 'slugField' => 'slug'
			))
		);
	}

	public function isMultilingual() {
		return true;
	}
}


<?php
class MockTest extends PHPUnit_Framework_TestCase {
	public function testSomething() {
		$this->assertNotEquals(
			'This is only here',
			'to satisfy phpunit.');
	}
}

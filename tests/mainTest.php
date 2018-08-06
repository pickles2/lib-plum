<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{
	public function setup(){
		mb_internal_encoding('UTF-8');
	}


	/**
	 * TEST
	 */
	public function testStandard(){
		$this->assertEquals( 1, 1 );
	}

}

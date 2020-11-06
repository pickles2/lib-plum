<?php
/**
 * test for Plum
 */
class mainTest extends PHPUnit_Framework_TestCase{
	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
		$this->options = array(
			'_POST' => array(),
			'_GET' => array(),
			'temporary_data_dir' => __DIR__.'/testdata/temporary_data_dir/',
			'preview_server' => array(
				array(
					'name' => 'preview1',
					'path' => __DIR__.'/testdata/repos/preview1/',
					'url' => 'http://example.com/repos/preview1/',
				),
				array(
					'name' => 'preview2',
					'path' => __DIR__.'/testdata/repos/preview2/',
					'url' => 'http://example.com/repos/preview2/',
				),
				array(
					'name' => 'preview3',
					'path' => __DIR__.'/testdata/repos/preview3/',
					'url' => 'http://example.com/repos/preview3/',
				)
			),
			'git' => array(
				'url' => __DIR__.'/testdata/remote',
			),
		);
	}


	/**
	 * GPI: get_condition
	 */
	public function testGpiGetCondition(){

		// Plum
		$options = $this->options;
		$plum = new hk\plum\main( $options );
		$result = $plum->gpi( array(
			'api' => 'get_condition',
		) );
		// var_dump($result);
		$this->assertFalse( $result['is_local_master_available'] );
		$this->assertSame( count($result['preview_server']), 3 );
		$this->assertTrue( is_null($result['remote_branch_list']) );

	}

	/**
	 * GPI: init_staging_env
	 */
	public function testGpiInitializeMasterDataDir(){

		// Plum
		$options = $this->options;
		$plum = new hk\plum\main( $options );
		$result = $plum->gpi( array(
			'api' => 'init_staging_env',
			'index' => null,
		) );
		// var_dump($result);
		$this->assertTrue( $result['status'] );
		$this->assertTrue( is_dir( __DIR__.'/testdata/temporary_data_dir/local_master/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' )), 'main' );

		$result = $plum->gpi( array(
			'api' => 'get_condition',
		) );
		// var_dump($result);
		$this->assertTrue( $result['is_local_master_available'] );
		$this->assertSame( count($result['preview_server']), 3 );
		$this->assertTrue( is_null($result['preview_server'][0]['current_branch']) );
		$this->assertSame( count($result['remote_branch_list']), 5 );

	}

}

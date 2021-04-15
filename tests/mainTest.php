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
			'temporary_data_dir' => __DIR__.'/testdata/temporary_data_dir/',
			'staging_server' => array(
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
		$this->assertSame( count($result['staging_server']), 3 );
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

		// ↓(2021-04-15) マスターデータは `git init` だけして、 `git pull` はしないようにした。なので、これらのファイルは存在しないのが正しい。
		$this->assertFalse( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/test_data.html' ) );
		$this->assertFalse( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' ) );

		$result = $plum->gpi( array(
			'api' => 'get_condition',
		) );
		// var_dump($result);
		$this->assertTrue( $result['is_local_master_available'] );
		$this->assertSame( count($result['staging_server']), 3 );
		$this->assertTrue( is_null($result['staging_server'][0]['current_branch']) );
		$this->assertSame( count($result['remote_branch_list']), 5 );

	}

}

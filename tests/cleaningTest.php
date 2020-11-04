<?php
/**
 * test for Plum
 */
class cleaningTest extends PHPUnit_Framework_TestCase{
	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
	}


	/**
	 * Cleaning
	 */
	public function testCleaning(){
		$this->fs->chmod_r(__DIR__.'/testdata/remote' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/remote/') ){
			var_dump('Failed to cleaning test remote directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/remote/');
		touch(__DIR__.'/testdata/remote/.gitkeep');
		$this->assertTrue( is_file(__DIR__.'/testdata/remote/.gitkeep') );


		$this->fs->chmod_r(__DIR__.'/testdata/temporary_data_dir' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/temporary_data_dir/') ){
			var_dump('Failed to cleaning test remote directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/temporary_data_dir/');
		touch(__DIR__.'/testdata/temporary_data_dir/.gitkeep');
		$this->assertTrue( is_file(__DIR__.'/testdata/temporary_data_dir/.gitkeep') );

	}

}

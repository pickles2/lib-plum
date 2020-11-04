<?php
/**
 * test for Plum
 */
class resetdataTest extends PHPUnit_Framework_TestCase{
	private $options = array();
	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$this->fs = new tomk79\filesystem();
	}


	/**
	 * テストデータを初期化する
	 */
	public function testResetData(){
		// --------------------------------------
		// リモートリポジトリを初期化
		$this->fs->chmod_r(__DIR__.'/testdata/remote' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/remote/') ){
			var_dump('Failed to cleaning test remote directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/remote/');
		touch(__DIR__.'/testdata/remote/.gitkeep');

		$current_dir = realpath('.');
		chdir(__DIR__.'/testdata/remote/');
		exec('git init');

		// master
		$this->fs->copy_r(
			__DIR__.'/testdata/remote_data/main',
			__DIR__.'/testdata/remote'
		);

		exec('git add ./');
		exec('git commit -m "initial commit";');
		exec('git checkout -b "master";');

		// main
		exec('git checkout -b "main";');

		// branches
		for( $i = 1; $i <= 3; $i ++ ){
			exec('git checkout "main";');
			exec('git checkout -b "tests/branch_00'.$i.'";');
			$this->fs->copy_r(
				__DIR__.'/testdata/remote_data/branch_00'.$i.'',
				__DIR__.'/testdata/remote'
			);
			exec('git add ./');
			exec('git commit -m "commit to branch_00'.$i.'";');
		}

		chdir($current_dir);

		// --------------------------------------
		// ローカルリポジトリを削除
		$this->fs->chmod_r(__DIR__.'/testdata/repos' , 0777);
		if( !$this->fs->rm(__DIR__.'/testdata/repos/') ){
			var_dump('Failed to cleaning test data directory.');
		}
		clearstatcache();
		$this->fs->mkdir_r(__DIR__.'/testdata/repos/');
		touch(__DIR__.'/testdata/repos/.gitkeep');
		clearstatcache();

	}

}

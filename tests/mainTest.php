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
			'additional_params' => array(
				'test1' => 'test1val',
				'test2' => 'test2val',
			),
		);
	}


	/**
	 * テストデータを初期化する
	 */
	private function reset_git_repos(){
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


	/**
	 * Initialize
	 */
	public function testInitialize(){
		$this->reset_git_repos();

		// Plum
		$options = $this->options;
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);

		$this->assertTrue( strpos($stdout, 'Initializeを実行してください。') !== false );

		$options = $this->options;
		$options['_POST'] = array('init' => 1);
		$plum = new hk\plum\main( $options );

		$this->assertEquals( $plum->get_additional_params(), '<input type="hidden" name="test1" value="test1val" /><input type="hidden" name="test2" value="test2val" />' );
		$this->assertEquals( $plum->get_additional_params('query_string'), 'test1=test1val&test2=test2val' );

		$stdout = $plum->run();
		// var_dump($stdout);

		$this->assertTrue( is_dir( __DIR__.'/testdata/temporary_data_dir/local_master/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' )), 'main' );

	}

	/**
	 * Change Branch
	 */
	public function testChangeBranch(){

		// preview1 を tests/branch_001 に。
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview1',
			'branch_form_list' => 'origin/tests/branch_001',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertTrue( is_dir( __DIR__.'/testdata/temporary_data_dir/local_master/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/temporary_data_dir/local_master/branchname.txt' )), 'main' );

		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/preview1/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview1/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview1/branchname.txt' )), 'branch_001' );

		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/preview2/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview2/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview2/branchname.txt' )), 'main' );

		$this->assertTrue( is_dir( __DIR__.'/testdata/repos/preview3/.git/' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/test_data.html' ) );
		$this->assertTrue( is_file( __DIR__.'/testdata/repos/preview3/branchname.txt' ) );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview3/branchname.txt' )), 'main' );



		// preview2 を tests/branch_002 に。
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview2',
			'branch_form_list' => 'origin/tests/branch_002',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview1/branchname.txt' )), 'branch_001' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview2/branchname.txt' )), 'branch_002' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview3/branchname.txt' )), 'main' );


		// preview3 を tests/branch_003 に。
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview3',
			'branch_form_list' => 'origin/tests/branch_003',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview1/branchname.txt' )), 'branch_001' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview2/branchname.txt' )), 'branch_002' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview3/branchname.txt' )), 'branch_003' );


		// preview2 を tests/branch_001 に。
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview2',
			'branch_form_list' => 'origin/tests/branch_001',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview1/branchname.txt' )), 'branch_001' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview2/branchname.txt' )), 'branch_001' );
		$this->assertSame( trim(file_get_contents( __DIR__.'/testdata/repos/preview3/branchname.txt' )), 'branch_003' );

	}


	/**
	 * OS Command Injection Countermeasure
	 */
	public function testOsCommandInjectionCountermeasure(){

		// 外部の攻撃者が任意のコマンドを実行してみるテスト
		$options = $this->options;
		$options['_POST'] = array(
			'reflect' => 1,
			'preview_server_name' => 'preview3',
			'branch_form_list' => 'origin/tests/branch_001; touch OsCommandInjectionCountermeasure.txt;',
		);
		$plum = new hk\plum\main( $options );
		$stdout = $plum->run();
		// var_dump($stdout);
		$this->assertFalse( is_file( __DIR__.'/testdata/repos/preview3/OsCommandInjectionCountermeasure.txt' ) );

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

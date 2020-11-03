<?php

namespace hk\plum;

class git {
	/** $main */
	private $main;

	/** Gitのカレントディレクトリ */
	private $realpath_git_root;

	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main, $realpath_git_root) {
		$this->main = $main;
		$this->realpath_git_root = $main->fs()->get_realpath($realpath_git_root.'/');
	}


	/**
	 * Gitコマンドを実行する
	 *
	 * @param array $git_sub_command Gitコマンドオプション
	 * @return array 実行結果
	 */
	public function git( $git_sub_command ){

		if( !is_array($git_sub_command) ){
			return array(
				'stdout' => '',
				'stderr' => 'Internal Error: Invalid arguments are given.',
				'return' => 1,
			);
		}

		if( !$this->is_valid_command($git_sub_command) ){
			return array(
				'stdout' => '',
				'stderr' => 'Internal Error: Command not permitted.',
				'return' => 1,
			);
		}

		foreach($git_sub_command as $idx=>$git_sub_command_row){
			$git_sub_command[$idx] = escapeshellarg($git_sub_command_row);
		}
		$cmd = implode(' ', $git_sub_command);

		$realpath_git_root = $this->realpath_git_root;

		$current_dir = realpath('.');
		chdir($realpath_git_root);

		ob_start();
		$proc = proc_open('git '.$cmd, array(
			0 => array('pipe','r'),
			1 => array('pipe','w'),
			2 => array('pipe','w'),
		), $pipes);

		$io = array();
		foreach($pipes as $idx=>$pipe){
			if($idx){
				$io[$idx] = stream_get_contents($pipe);
			}
			fclose($pipe);
		}
		$return_var = proc_close($proc);
		ob_get_clean();

		chdir($current_dir);

		$rtn = array(
			'stdout' => $this->conceal_confidentials($io[1]),
			'stderr' => $this->conceal_confidentials($io[2]),
			'return' => $return_var,
		);

		return $rtn;
	}

	/**
	 * Gitコマンドに不正がないか確認する
	 */
	private function is_valid_command( $git_sub_command ){

		if( !is_array($git_sub_command) ){
			// 配列で受け取る
			return false;
		}

		// 許可されたコマンド
		switch( $git_sub_command[0] ){
			case 'init':
			case 'clone':
			case 'config':
			case 'status':
			case 'branch':
			case 'log':
			case 'diff':
			case 'show':
			case 'remote':
			case 'fetch':
			case 'checkout':
			case 'add':
			case 'rm':
			case 'reset':
			case 'clean':
			case 'commit':
			case 'merge':
			case 'push':
			case 'pull':
				break;
			default:
				return false;
				break;
		}

		// 不正なオプション
		foreach( $git_sub_command as $git_sub_command_row ){
			if( preg_match( '/^\-\-output(?:\=.*)?$/', $git_sub_command_row ) ){
				return false;
			}
		}

		return true;
	}

	/**
	 * gitコマンドの結果から、秘匿な情報を隠蔽する
	 * @param string $str 出力テキスト
	 * @return string 秘匿情報を隠蔽加工したテキスト
	 */
	private function conceal_confidentials($str){

		// gitリモートリポジトリのURLに含まれるパスワードを隠蔽
		// ただし、アカウント名は残す。
		$str = preg_replace('/((?:[a-zA-Z\-\_]+))\:\/\/([^\s\/\\\\]*?\:)([^\s\/\\\\]*)\@/si', '$1://$2********@', $str);

		return $str;
	}

}

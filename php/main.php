<?php

namespace hk\plum;

class main
{
	/**
	 * オプション
	 * 
	 * _GET,
	 * _POST,
	 * _COOKIE,
	 * _SESSION,
	 * preview_server = array(
	 * 	// プレビューサーバの数だけ用意する
	 * 	array(
	 * 		string 'name':
	 * 			- プレビューサーバ名(任意)
	 * 		string 'path':
	 * 			- プレビューサーバ(デプロイ先)のパス
	 * 		string 'url':
	 * 			- プレビューサーバのURL
	 * 			  Webサーバのvirtual host等で設定したURL
	 * 	)
	 * ),
	 * git = array(
	 * 	string 'repository':
	 * 		- ウェブプロジェクトのリポジトリパス
	 * 		  例) ./../repos/master/
	 * 	string 'protocol':
	 * 		- https
	 * 		  ※現状はhttpsのみ対応
	 * 	string 'host':
	 * 		- Gitリポジトリのhost
	 * 		  例) github.com
	 * 	string 'url':
	 * 		- Gitリポジトリのurl
	 * 		  例) github.com/hk-r/px2-sample-project.git
	 * 	string 'username':
	 * 		- Gitリポジトリのユーザ名
	 * 		  例) hoge
	 * 	string 'password':
	 * 		- Gitリポジトリのパスワード
	 * 		  例) fuga
	 * )
	 */
	public $options;

	/** hk\plum\plum_deployのインスタンス */
	private $deploy;



	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {
		$this->options = json_decode(json_encode($options));
		$this->deploy = new plum_deploy($this);
		$this->git = new plum_git($this);
	}

	/**
	 * initialize
	 */
	private function init() {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$server_list = $this->options->preview_server;
		array_push($server_list, json_decode(json_encode(array(
			'name'=>'master',
			'path'=>$this->options->git->repository,
		))));

		set_time_limit(0);

		foreach ( $server_list as $preview_server ) {
			chdir($current_dir);

			try {

				if ( strlen($preview_server->path) ) {

					// デプロイ先のディレクトリが無い場合は作成
					if ( !file_exists( $preview_server->path) ) {
						// 存在しない場合

						// ディレクトリ作成
						if ( !mkdir( $preview_server->path, 0777) ) {
							// ディレクトリが作成できない場合

							// エラー処理
							throw new \Exception('Creation of preview server directory failed.');
						}
					}

					// 「.git」フォルダが存在すれば初期化済みと判定
					if ( !file_exists( $preview_server->path . "/.git") ) {
						// 存在しない場合

						// ディレクトリ移動
						if ( chdir( $preview_server->path ) ) {

							// git セットアップ
							exec('git init', $output);

							// git urlのセット
							$url = $this->options->git->protocol . "://";
							if( strlen(@$this->options->git->username) ){
								// ID/PW が設定されていない場合は、認証情報なしでアクセスする。
								$url .= urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@";
							}
							$url .= $this->options->git->url;
							exec('git remote add origin ' . $url, $output);

							// git fetch
							exec( 'git fetch origin', $output);

							// git pull
							exec( 'git pull origin master', $output);

							chdir($current_dir);
						} else {
							// プレビューサーバのディレクトリが存在しない場合

							// エラー処理
							throw new \Exception('Preview server directory not found.');
						}
					}
				}

			} catch (\Exception $e) {
				set_time_limit(30);

				$result['status'] = false;
				$result['message'] = $e->getMessage();

				chdir($current_dir);
				return json_encode($result);
			}

		}
		set_time_limit(30);

		$result['status'] = true;

		return json_encode($result);
	}

	/**
	 * initalizeの状態取得
	 */
	private function get_initialize_status() {

		$output = "";
		$result = array('status' => false,
						'message' => '');

		$server_list = $this->options->preview_server;
		
		// 初期値設定
		$result['status'] = true;
		$result['already_init'] = true;

		foreach ( $server_list as $preview_server ) {

			try {

				if ( strlen($preview_server->path) ) {

					// デプロイ先のディレクトリが存在しない、または「.git」フォルダが存在しない場合
					if ( !file_exists( $preview_server->path) ||
						 !file_exists( $preview_server->path . "/.git")) {
						
						$result['already_init'] = false;
						break;
					}
				}

			} catch (\Exception $e) {

				$result['status'] = false;
				$result['already_init'] = false;
				$result['message'] = $e->getMessage();

				return json_encode($result);
			}

		}

		return json_encode($result);

	}

	/**
	 * ブランチリストを取得
	 */
	private function get_parent_branch_list() {

		$current_dir = realpath('.');

		$output_array = array();
		$result = array('status' => true,
						'message' => '');

		try {

			if ( chdir( $this->options->git->repository )) {

				// fetch
				exec( 'git fetch', $output );

				// ブランチの一覧取得
				exec( 'git branch -r', $output );

				foreach ($output as $key => $value) {
					if( strpos($value, '/HEAD') !== false ){
						continue;
					}
					$output_array[] = trim($value);
				}

				$result['branch_list'] = $output_array;

			} else {
				// プレビューサーバのディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Preview server directory not found.');
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);
		return json_encode($result);

	}

	/**
	 * 現在のブランチと比較する
	 * @param string $branch 比較対象のブランチ
	 * @return string
	 *  一致する場合：checked(文字列)
	 *  一致しない場合：空白
	 */
	private function compare_to_current_branch($path, $branch) {

		$current = json_decode($this->get_child_current_branch($path));
		
		if(str_replace("origin/", "", $branch) == $current->current_branch) {
			return "selected";
		} else {
			return "";
		}

		return "";
	}

	/**
	 * 現在のブランチを取得する
	 */
	private function get_child_current_branch($path) {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		try {

			if ( chdir( $path ) ) {

				// ブランチ一覧取得
				exec( 'git branch', $output );

				$now_branch;
				foreach ( $output as $value ) {
					// 「*」の付いてるブランチを現在のブランチと判定
					if ( strpos($value, '*') !== false ) {
						$value = str_replace("* ", "", $value);
						$now_branch = $value;
					}
				}

				$ret = str_replace("* ", "", $now_branch);
				$result['current_branch'] = trim($ret);

			} else {
				// プレビューサーバのディレクトリが存在しない場合

				// エラー処理
				throw new \Exception('Preview server directory not found.');
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = $e->getMessage();

			chdir($current_dir);
			return json_encode($result);
		}

		$result['status'] = true;

		chdir($current_dir);
		return json_encode($result);
	}

	private function disp_before_initialize() {
		$ret = "";

		// 初期化前の画面表示
		$ret = '<div class="panel panel-warning" style="margin-top:20px;">'
				. '<div class="panel-heading">'
				. '<p class="panel-title">Initializeが実行されていないプレビューが存在します。<br>Initializeを実行してください。</p>'
				. '<form method="post" style="margin-top:20px;">'
				. '<input type="submit" id="init_btn" name="init" class="btn btn-default btn-block" value="Initialize" />'
				. '</form>'
				. '</div>'
				. '</div>';

		return $ret;
	}

	private function disp_after_initialize() {
		$ret = "";
		$row = "";

		// 初期化後の画面表示
		// Gitリポジトリ取得
		$get_branch_ret = json_decode($this->get_parent_branch_list());
		$branch_list = array();
		$branch_list = $get_branch_ret->branch_list;

		$ret = '<table class="table table-bordered">'
				. '<thead>'
				. '<tr>'
				. '<th>server</th><th>状態</th><th>branch</th><th>反映</th><th>プレビュー</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		foreach ($this->options->preview_server as $key => $prev_row) {
		
			// デプロイ先のディレクトリが存在する場合
			if ( file_exists( $prev_row->path)) {

				$row .= '<tr>'
						. '<td scope="row">' . htmlspecialchars($prev_row->name) . '</td>'
						. '<td class="p-center"><form id="state_submit_' . htmlspecialchars($prev_row->name) . '" method="post"><input type="submit" id="state_' . htmlspecialchars($prev_row->name) . '" class="state btn btn-default btn-block" value="状態" name="state"><input type="hidden" name="preview_server_name" value="' . htmlspecialchars($prev_row->name) . '"></form></td>'
						. '<td><select id="branch_list_' . htmlspecialchars($prev_row->name) . '" class="form-control" name="branch_form_list" form="reflect_submit_' . htmlspecialchars($prev_row->name) . '">';

				foreach ($branch_list as $branch) {
					$row .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_current_branch($prev_row->path, $branch) . '>' . htmlspecialchars($branch) . '</option>';
				}

				$row .= '</select>'
						. '</td>'
						. '<td class="p-center"><form id="reflect_submit_' . htmlspecialchars($prev_row->name) . '" method="post"><input type="submit" id="reflect_' . htmlspecialchars($prev_row->name) . '" class="reflect btn btn-default btn-block" value="反映" name="reflect"><input type="hidden" name="preview_server_name" value="' . htmlspecialchars($prev_row->name) . '"></form></td>'
						. '<td class="p-center"><a href="' . htmlspecialchars($prev_row->url) . '" class="btn btn-default btn-block" target="_blank">プレビュー</a></td>'
						. '</tr>';
			}
		}

		$ret .= $row;
		$ret .= '</tbody></table>';

		return $ret;
	}

	private function disp_status($status) {
		$ret = "";
		$list = "";
		$list_group = "";

		if ( $status->changes ) {
			foreach ( $status->changes as $change ) {
				$list .= '<li class="list-group-item">[' . $this->file_status_constants($change) . '] '. $change->file . '</li>';
			}

			$list_group = '<ul class="list-group">'.$list.'</ul>';
		}
		
		$ret = '<div class="dialog" id="status_dialog"><div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			 . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			 . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			 . '<div class="dialog_box">'
			 . '<h1>状態</h1>'
			 . '<div>'
			 . $list_group
			 . '</div>'
			 . '<div class="dialog-buttons">'
			 . '<button type="submit" id="close_btn" class="px2-btn px2-btn--primary btn btn-secondary">閉じる</button>'
			 . '</div>'
			 . '</div>'
			 . '</div>'
			 . '</div></div>';

		return $ret;
	}

	private function file_status_constants($status) {

		if($status->work_tree == '?' && $status->index == '?'){
			return 'untracked';
		}else if($status->work_tree == 'A' || $status->index == 'A'){
			return 'added';
		}else if($status->work_tree == 'M' || $status->index == 'M'){
			return 'modified';
		}else if($status->work_tree == 'D' || $status->index == 'D'){
			return 'deleted';
		}else if($status->work_tree == 'R' || $status->index == 'R'){
			return 'renamed';
		}
		return 'unknown';

	}

	/**
	 * マスタブランチの存在チェック
	 *	 
	 * @return boolean
	 *  存在する場合：true
	 *  存在しない場合：false
	 */
	private function exists_master_branch() {

		$ret = true;
		
		// デプロイ先のマスタブランチが無い場合はfalseを返す
		if ( !file_exists( $this->options->git->repository ) ) {
			$ret = false;
		}

		return $ret;

	}


	/**
	 * 実行する
	 */
	public function run() {

		// エラーメッセージ
		$error_msg = '';  

		// 初期化ボタンが押下された場合
		if (isset($this->options->_POST->init)) {

			// initialize処理
			$init_ret = $this->init();
			$init_ret = json_decode($init_ret);

			if ( !$init_ret->status ) {
				// 初期化失敗

				// エラーメッセージ
				$error_msg = '
				<script type="text/javascript">
					console.error("' . $init_ret->message . '");
					alert("initialize faild");
				</script>';
			}
		} 

		// マスタブランチの存在チェック
		$exist_master_flg = $this->exists_master_branch();

		// 画面表示
		$disp = '';
		
		if (!$exist_master_flg) {

			$disp = $this->disp_before_initialize();

		} else {

			$already_init_ret = $this->get_initialize_status();
			$already_init_ret = json_decode($already_init_ret);
			
			// 状態の表示
			$state_ret = '';

			// initializaされていないプレビューが存在する場合
			if (!$already_init_ret->already_init) {

				$disp = $this->disp_before_initialize();
			}

			// 反映ボタンの押下
			if (isset($this->options->_POST->reflect)) {

				// deploy処理
				$deploy_ret = $this->deploy->set_deploy($this->options->_POST->preview_server_name, $this->options->_POST->branch_form_list);
				
				$deploy_ret = json_decode($deploy_ret);

				if ( !$deploy_ret->status ) {
					// デプロイ失敗

					// エラーメッセージ
					$error_msg = '
					<script type="text/javascript">
						console.error("' . $deploy_ret->message . '");
						alert("deploy faild");
					</script>';
				}

			// 状態ボタンの押下
			} else if (isset($this->options->_POST->state)) {

				// git status取得
				$status = $this->git->status($this->options->_POST->preview_server_name);
				$status = json_decode(json_encode($status));
				
				$state_ret = $this->disp_status($status);
			}

			$disp .= $this->disp_after_initialize() . $state_ret;

		}

		// 画面ロック用
		$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';
		
		// 画面表示
		return $disp . $disp_lock . $error_msg;
	}
	
}

<?php

namespace hk\plum;

class fncs
{
	/** オプション */
	public $options;

	/** hk\plum\plum_deployのインスタンス */
	private $deploy;

	/** tomk79/filesystem */
	private $fs;

	/**
	 * コンストラクタ
	 * @param object $main Plumメインオブジェクト
	 * @param array $options オプション
	 */
	public function __construct($main, $options) {
		$this->main = $main;
		$this->options = $options;
	}

	/**
	 * initialize all GIT repositories
	 * 
	 * Gitリポジトリをクローンし、ローカル環境を整えます。
	 * 
	 * @return array result
	 * - $result['status'] boolean 初期化に成功した場合に true
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	public function init_all_staging_envs() {

		$result = array(
			'status' => true,
			'message' => '',
		);


		set_time_limit(0);

		$tmp_result = $this->init_staging_env(null);
		if( !$tmp_result['status'] ){
			$result['status'] = false;
			return $result;
		}

		$server_list = $this->options->preview_server;
		foreach ( $server_list as $idx=>$preview_server ) {
			$tmp_result = $this->init_staging_env($idx);
			if( !$tmp_result['status'] ){
				$result['status'] = false;
				return $result;
			}
		}
		set_time_limit(30);

		$result['status'] = true;
		return $result;
	}

	/**
	 * Initialize GIT Repository
	 * 
	 * Gitリポジトリをクローンし、ローカル環境を整えます。
	 * 
	 * @return array result
	 * - $result['status'] boolean 初期化に成功した場合に true
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	public function init_staging_env( $staging_server_index ) {
		$result = array();

		if( strlen($staging_server_index) && array_key_exists( $staging_server_index,  $this->options->preview_server ) ){
			$preview_server = $this->options->preview_server[$staging_server_index];
		}else{
			$preview_server = json_decode(json_encode(array(
				'name'=>'master',
				'path'=>$this->options->temporary_data_dir.'/local_master/',
			)));
		}

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

					// .git はないがディレクトリは存在する。
					if ( is_dir( $preview_server->path ) ) {

						$git = $this->main->git($preview_server->path);

						// git セットアップ
						$git->git(array('init'));

						// git urlのセット
						$url_git_remote = $this->get_url_git_remote(true);

						// set remote as origin
						$git->git(array(
							'remote',
							'add',
							'origin',
							$url_git_remote
						));
						$git->git(array(
							'remote',
							'set-url',
							'origin',
							$url_git_remote
						));

						// git fetch
						$git->git(array(
							'fetch',
						));

						// git pull
						$git->git(array(
							'pull',
							'origin',
							'master',
						));

						$git->git(array(
							'remote',
							'rm',
							'origin',
						));

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
			return $result;
		}

		$result['status'] = true;
		return $result;
	}

	/**
	 * initalizeの状態取得
	 * 
	 * @return array result
	 * - $result['status'] boolean 状態確認に成功した場合に true
	 * - $result['already_init'] boolean 初期化済みのとき true
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	public function get_initialize_status() {

		$output = "";
		$result = array(
			'status' => false,
			'message' => '',
		);

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

				return $result;
			}

		}

		return $result;

	}

	/**
	 * ブランチリストを取得
	 * 
	 * @return array result
	 * - $result['status'] boolean リスト取得に成功した場合に true
	 * - $result['branch_list'] array 取得された一覧を格納
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	private function get_parent_branch_list() {

		$output_array = array();
		$result = array(
			'status' => true,
			'message' => '',
		);
		$base_dir = $this->main->fs()->get_realpath( $this->options->temporary_data_dir.'/local_master/' );

		if ( !is_dir( $base_dir ) || !is_dir( $base_dir.'.git/' ) ) {
			$result['status'] = false;
			$result['message'] = 'Preview server directory not found.';
			return $result;
		}

		try {

			if ( is_dir( $base_dir ) && is_dir( $base_dir.'.git/' ) ) {

				$git = $this->main->git($base_dir);

				// git urlのセット
				$url_git_remote = $this->get_url_git_remote(true);

				// set remote as origin
				$git->git(array(
					'remote',
					'add',
					'origin',
					$url_git_remote,
				));
				$git->git(array(
					'remote',
					'set-url',
					'origin',
					$url_git_remote,
				));

				// fetch
				$git->git(array(
					'fetch',
				));

				// ブランチの一覧取得
				$cmdresult = $git->git(array(
					'branch',
					'-r',
				));
				$output = preg_split( '/\r\n|\r|\n/', trim($cmdresult['stdout']) );

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

			$git->git(array(
				'remote',
				'rm',
				'origin',
			));

			$result['status'] = false;
			$result['message'] = $e->getMessage();
			return $result;
		}

		$git->git(array(
			'remote',
			'rm',
			'origin',
		));

		$result['status'] = true;
		return $result;

	}

	/**
	 * 現在のブランチを取得する
	 * 
	 * @return array result
	 * - $result['status'] boolean 取得に成功した場合に true
	 * - $result['current_branch'] string ブランチ名を格納
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	private function get_child_current_branch($path) {

		$output = "";
		$result = array(
			'status' => true,
			'message' => '',
			'current_branch' => null,
		);

		try {

			if ( is_dir( $path ) ) {

				$git = $this->main->git($path);

				// ブランチ一覧取得
				$cmdresult = $git->git(array(
					'branch',
				));
				$output = preg_split( '/\r\n|\r|\n/', trim($cmdresult['stdout']) );

				$now_branch = null;
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
			return $result;
		}

		$result['status'] = true;
		return $result;
	}

	/**
	 * 初期化前の画面を表示する
	 */
	public function mk_html_before_initialize() {
		$ret = "";

		// 初期化前の画面表示
		$ret = '<div class="panel panel-warning" style="margin-top:20px;">'
				. '<div class="panel-heading">'
				. '<p class="panel-title">Initializeが実行されていないプレビューが存在します。<br>Initializeを実行してください。</p>'
				. '<form method="post" style="margin-top:20px;">'
				. '<input type="submit" id="init_btn" name="init" class="px2-btn px2-btn--primary px2-btn--block" value="Initialize" />'
				. '</form>'
				. '</div>'
				. '</div>';

		return $ret;
	}

	/**
	 * 初期化後の画面を表示する
	 */
	public function mk_html_after_initialize() {
		$ret = "";
		$row = "";

		// 初期化後の画面表示
		// Gitリポジトリ取得
		$get_branch_ret = $this->get_parent_branch_list();
		$branch_list = null;
		if( array_key_exists( 'branch_list', $get_branch_ret ) && is_array($get_branch_ret['branch_list']) ){
			$branch_list = $get_branch_ret['branch_list'];
		}

		$ret = '<table class="px2-table">'
				. '<thead>'
				. '<tr>'
				. '<th>server</th><th>branch</th><th>反映</th><th>プレビュー</th>'
				. '</tr>'
				. '</thead>'
				. '<tbody>';

		foreach ($this->options->preview_server as $key => $prev_row) {
		
			// デプロイ先のディレクトリが存在する場合
			if ( file_exists( $prev_row->path)) {

				$row .= '<tr>'
						. '<td scope="row">' . htmlspecialchars($prev_row->name) . '</td>'
						. '<td><select id="branch_list_' . htmlspecialchars($prev_row->name) . '" class="px2-input" name="branch_form_list" form="reflect_submit_' . htmlspecialchars($prev_row->name) . '">';

				if( is_array($branch_list) ){
					foreach ($branch_list as $branch) {
						$tmp_current_buranch_info = $this->get_child_current_branch( $prev_row->path );
						$row .= '<option value="' . htmlspecialchars($branch) . '" ' . ($branch == "origin/".$tmp_current_buranch_info['current_branch'] ? 'selected' : '') . '>' . htmlspecialchars($branch) . '</option>';
					}
				}

				$row .= '</select>'
						. '</td>'
						. '<td class="px2-text-align-center"><form id="reflect_submit_' . htmlspecialchars($prev_row->name) . '" method="post"><input type="submit" id="reflect_' . htmlspecialchars($prev_row->name) . '" class="reflect px2-btn px2-btn--primary px2-btn--block" value="反映" name="reflect"><input type="hidden" name="preview_server_name" value="' . htmlspecialchars($prev_row->name) . '"></form></td>'
						. '<td class="px2-text-align-center"><a href="' . htmlspecialchars($prev_row->url) . '" class="px2-btn px2-btn--block" target="_blank">プレビュー</a></td>'
						. '</tr>';
			}
		}

		$ret .= $row;
		$ret .= '</tbody></table>';

		return $ret;
	}


	/**
	 * ファイルごとの状態の名称を得る
	 */
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
	 * ローカルマスタブランチの状態チェック
	 *	 
	 * @return boolean
	 * - 利用可能な場合：true
	 * - 利用可能ではない場合：false
	 */
	public function is_local_master_available() {
		if ( !is_dir( $this->options->temporary_data_dir.'/local_master/' ) ) {
			return false;
		}
		if ( !is_dir( $this->options->temporary_data_dir.'/local_master/.git/' ) ) {
			return false;
		}
		return true;

	}

	/**
	 * git remote のURLを得る
	 *	 
	 * @return string Gitリモートサーバーの完全なURL
	 */
	public function get_url_git_remote( $include_credentials = false ) {

		$parsed_url = parse_url( $this->options->git->url );

		if( property_exists( $this->options->git, 'protocol' ) ){
			trigger_error('Option git->protocol is deprecated.');
		}
		if( property_exists( $this->options->git, 'host' ) ){
			trigger_error('Option git->host is deprecated.');
		}
		if( property_exists( $this->options->git, 'username' ) ){
			$parsed_url['user'] = $this->options->git->username;
		}
		if( property_exists( $this->options->git, 'password' ) ){
			$parsed_url['pass'] = $this->options->git->password;
		}

		// リモートがローカルディスクにある場合
		if( array_key_exists('scheme', $parsed_url) && strlen($parsed_url['scheme']) ){
		}elseif( array_key_exists('host', $parsed_url) && strlen($parsed_url['host']) ){
		}elseif( is_dir( $this->options->git->url.'/.git/' ) ){
			return $this->options->git->url;
		}

		// git urlのセット
		$url = '';
		if( array_key_exists('scheme', $parsed_url) && strlen($parsed_url['scheme']) ){
			$url .= $parsed_url['scheme'];
		}else{
			$url .= 'https';
		}
		$url .= "://";
		if( $include_credentials ){
			if( array_key_exists('user', $parsed_url) && strlen($parsed_url['user']) ){
				// ID/PW が設定されていない場合は、認証情報なしでアクセスする。
				$url .= urlencode($parsed_url['user']);
				if( array_key_exists('pass', $parsed_url) && strlen($parsed_url['pass']) ){
					$url .= ":" . urlencode($this->options->git->password);
				}
				$url .= "@";
			}
		}
		if( array_key_exists('host', $parsed_url) && strlen($parsed_url['host']) ){
			$url .= $parsed_url['host'];
		}
		if( array_key_exists('port', $parsed_url) && strlen($parsed_url['port']) ){
			$url .= ':'.$parsed_url['port'];
		}
		$url .= $parsed_url['path'];
		if( array_key_exists('query', $parsed_url) && strlen($parsed_url['query']) ){
			$url .= '?'.$parsed_url['query'];
		}
		return $url;

	}

}

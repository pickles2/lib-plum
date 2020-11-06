<?php

namespace hk\plum;

class fncs
{

	/** hk\plum\main のインスタンス */
	private $main;

	/** オプション */
	public $options;

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
	 * @param int $staging_server_index 操作対象のステージング環境のインデックス番号 (省略時: マスターデータを対象とする)
	 * @param string $branch_name 初期化するリモートブランチ名
	 * @return array result
	 * - $result['status'] boolean 初期化に成功した場合に true
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	public function init_staging_env( $staging_server_index = null, $branch_name = null ) {
		$result = array();

		if( strlen($staging_server_index) && array_key_exists( $staging_server_index, $this->options->preview_server ) ){
			$preview_server = $this->options->preview_server[$staging_server_index];
		}else{
			$preview_server = json_decode(json_encode(array(
				'name'=>'master',
				'path'=>$this->options->temporary_data_dir.'/local_master/',
			)));
			$staging_server_index = null;
			$branch_name = null; // マスターデータはデフォルトブランチでのみチェックアウト可
		}

		if( !property_exists($preview_server, 'path') || !strlen($preview_server->path) ){
			$result['status'] = false;
			$result['message'] = 'Local directory path is not set.';
			return $result;
		}
		if( !$this->main->fs()->is_dir( $preview_server->path ) ){
			$this->main->fs()->mkdir_r( $preview_server->path );
			clearstatcache();
		}
		if( !$this->main->fs()->is_dir( $preview_server->path ) ){
			$result['status'] = false;
			$result['message'] = 'Failed to make Local directory.';
			return $result;
		}

		// 状態を確認する
		$condition = $this->check_staging_env_condition( $staging_server_index );

		try {

			$git = $this->main->git($preview_server->path);
			$url_git_remote = $this->get_url_git_remote(true);
			$local_branch_name = preg_replace( '/^origin\//', '', $branch_name );

			if ( $condition['is_dir'] && !$condition['is_git_dir'] ) {
				// git 初期化
				$git->git(array('init'));

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
					'-f',
					'origin',
					'master',
				));

				$git->git(array(
					'remote',
					'rm',
					'origin',
				));

				$condition = $this->check_staging_env_condition( $staging_server_index );
			}


			if ( $condition['is_dir'] && $condition['is_git_dir'] && strlen($condition['current_branch_name']) && strlen($branch_name) ) {

				if( $condition['current_branch_name'] == $local_branch_name ){
					// すでに同じブランチがチェックアウトされているので、
					// 更新する
				}else{
					// 違うブランチがチェックアウトされているので、
					// 切り替える

					$git->git(array(
						'checkout',
						'-b',
						$local_branch_name,
					));
				}


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
					'-f',
					'origin',
					$local_branch_name.':'.$local_branch_name,
				));

				$git->git(array(
					'remote',
					'rm',
					'origin',
				));
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
	 * 単環境の状態を調べる
	 * 
	 * @return array result
	 * - $result['is_dir'] boolean 作業ディレクトリが作成されているか
	 * - $result['is_git_dir'] boolean .git ディレクトリが作成されているか
	 * - $result['current_branch_name'] string 現在のブランチ名
	 */
	private function check_staging_env_condition( $staging_server_index = null ) {

		if( strlen($staging_server_index) && array_key_exists( $staging_server_index, $this->options->preview_server ) ){
			$preview_server = $this->options->preview_server[$staging_server_index];
		}else{
			$preview_server = json_decode(json_encode(array(
				'name'=>'master',
				'path'=>$this->options->temporary_data_dir.'/local_master/',
			)));
			$staging_server_index = null;
		}

		$result = array(
			'is_dir' => false,
			'is_git_dir' => false,
			'current_branch_name' => null,
		);

		if( $this->main->fs()->is_dir( $preview_server->path ) ){
			$result['is_dir'] = true;
		}
		if( $this->main->fs()->is_dir( $preview_server->path.'/.git' ) ){
			$result['is_git_dir'] = true;
		}
		if( $result['is_git_dir'] ){
			$tmp_current_branch = $this->get_current_branch( $preview_server->path );
			if( $tmp_current_branch['status'] ){
				$result['current_branch_name'] = $tmp_current_branch['current_branch'];
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
	public function get_remote_branch_list() {

		$output_array = array();
		$result = array(
			'status' => true,
			'message' => '',
			'branch_list' => array(),
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

		} catch (\Exception $e) {
			$result['status'] = false;
			$result['message'] = $e->getMessage();
			return $result;
		}

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
	public function get_current_branch( $path ) {

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
		$get_branch_ret = $this->get_remote_branch_list();
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
						$tmp_current_buranch_info = $this->get_current_branch( $prev_row->path );
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

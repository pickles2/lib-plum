<?php

namespace hk\plum;

class main
{
	/** オプション 
		_GET,
		_POST,
		_COOKIE,
		_SESSION,
		preview_server = array(
			// プレビューサーバの数だけ用意する
			array(
				string 'name':
					- プレビューサーバ名(任意)
				string 'path':
					- プレビューサーバ(デプロイ先)のパス
				string 'url':
					- プレビューサーバのURL
					  Webサーバのvirtual host等で設定したURL
			)
		),
		git = array(
			string 'repository':
				- ウェブプロジェクトのリポジトリパス
				  例) ./../repos/master/
			string 'protocol':
				- https
				  ※現状はhttpsのみ対応
			string 'host':
				- Gitリポジトリのhost
				  例) github.com
			string 'url':
				- Gitリポジトリのurl
				  例) github.com/hk-r/px2-sample-project.git
			string 'username':
				- Gitリポジトリのユーザ名
				  例) hoge
			string 'password':
				- Gitリポジトリのパスワード
				  例) fuga
		)
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
							throw new Exception('Creation of preview server directory failed.');
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
							$url = $this->options->git->protocol . "://" . urlencode($this->options->git->username) . ":" . urlencode($this->options->git->password) . "@" . $this->options->git->url;
							exec('git remote add origin ' . $url, $output);

							// git fetch
							exec( 'git fetch origin', $output);

							// git pull
							exec( 'git pull origin master', $output);

							chdir($current_dir);
						} else {
							// プレビューサーバのディレクトリが存在しない場合

							// エラー処理
							throw new Exception('Preview server directory not found.');
						}
					}
				}

			} catch (Exception $e) {

				$result['status'] = false;
				$result['message'] = $e->getMessage();

				chdir($current_dir);
				return json_encode($result);
			}

		}

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
		
		foreach ( $server_list as $preview_server ) {

			try {

				if ( strlen($preview_server->path) ) {

					// デプロイ先のディレクトリが存在するかチェック
					if ( file_exists( $preview_server->path) ) {
						// 存在する場合

						// 「.git」フォルダが存在すれば初期化済みと判定
						if ( file_exists( $preview_server->path . "/.git") ) {
							// 存在する場合

							$result['status'] = true;
							$result['already_init'] = true;
							return json_encode($result);
						}
					}
				}

			} catch (Exception $e) {

				$result['status'] = false;
				$result['message'] = $e->getMessage();

				return json_encode($result);
			}

		}

		$result['status'] = true;
		$result['already_init'] = false;
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
				throw new Exception('Preview server directory not found.');
			}

		} catch (Exception $e) {

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
	 * @param $branch=比較対象のブランチ
	 * @return 
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
				throw new Exception('Preview server directory not found.');
			}

		} catch (Exception $e) {

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
		$ret;

		// 初期化前の画面表示
		$ret = '<div class="panel panel-warning">'
				. '<div class="panel-heading">'
				. '<p class="panel-title">Initializeを実行してください</p>'
				. '</div>'
				. '<form action="" method="post">'
				. '<input type="submit" name="init" value="initialize" />'
				. '</form>'
				. '</div>';

		return $ret;
	}

	private function disp_after_initialize() {
		$ret;
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
			$row .= '<tr>'
					. '<td scope="row">' . htmlspecialchars($prev_row->name) . '</td>'
					. '<td class="p-center"><button type="button" id="state_' . htmlspecialchars($prev_row->name) . '" class="btn btn-default btn-block" value="状態" name="state">状態</button></td>'
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

		$ret .= $row;
		$ret .= '</tbody></table>';

		return $ret;
	}

	/**
	 * 
	 */
	public function run() {

		if( isset($this->options->_POST->reflect) ) {

			// deploy処理
			$ret = $this->deploy->set_deploy($this->options->_POST->preview_server_name, $this->options->_POST->branch_form_list);
			
			// 初期化済みの表示
			return $this->disp_after_initialize();

		} else {

			$already_init_ret = $this->get_initialize_status();
			$already_init_ret = json_decode($already_init_ret);

			// 既に初期化済みかのチェック
			if ($already_init_ret->already_init) {

				// 初期化済みの表示
				return $this->disp_after_initialize();

			} else {

				if (isset($this->options->_POST->init)) {
					// initialize処理
					
					// プレビューサーバの初期化処理
					$init_ret = $this->init();
					$init_ret = json_decode($init_ret);

					if ( $init_ret->status ) {
						
						// 初期化済みの表示
						return $this->disp_after_initialize();

					} else {
						// エラー処理
						$ret = '
							<script type="text/javascript">
								console.error("' . $init_ret->message . '");
								alert("initialize faild");
							</script>';

						// 初期化前の表示
						return $this->disp_before_initialize() . $ret;
					}
					
				} else {
					// 初期化前の表示
					return $this->disp_before_initialize();
				}
			}	
		}

	}
	
}

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
			string 'name':
				- プレビューサーバ名(任意)
			string 'path':
				- プレビューサーバ(デプロイ先)のパス
			string 'url':
				- プレビューサーバのURL
				  Webサーバのvirtual host等で設定したURL
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
	private $options;

	/** hk\plum\plum_deployのインスタンス */
	private $deploy;

	/**
	 * コンストラクタ
	 * @param $options = オプション
	 */
	public function __construct($options) {
		$this->options = $options;
		$this->deploy = new plum_deploy($this);
	}

	/**
	 * initialize
	 */
	private function init() {

	}

	/**
	 * initalizeの状態取得
	 */
	private function get_initialize_status() {

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

			if ( chdir( $this->options["git"]["repository"] )) {

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
	private function compare_to_current_branch($branch) {

		$current = $this->get_child_current_branch();

		if(str_replace("origin/", "", $branch) == $current->current_branch) {
			return "checked";
		} else {
			return "";
		}

		return "";
	}

	/**
	 * 現在のブランチを取得する
	 */
	private function get_child_current_branch() {

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

	/**
	 * deploy実行
	 */
	public function deploy() {

	}

	/**
	 * 
	 */
	public function run() {

		$ret;

		$already_init_ret = $this->get_initialize_status();
		$already_init_ret = json_decode($already_init_ret);

		// 既に初期化済みかのチェック
		if ($already_init_ret->already_init) {
			// 初期化前の画面表示

			$ret = '<div class="panel panel-warning">'
					. '<div class="panel-heading">'
					. '<p class="panel-title">Initializeを実行してください</p>'
					. '</div>'
					. '</div>'

			return $ret;

		} else {
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
				$row = '<tr>'
						. '<td scope="row">' . htmlspecialchars($prev_row->name) . '</td>'
						. '<td class="p-center"><button type="button" id="state_' . htmlspecialchars($prev_row->name) . '" class="btn btn-default btn-block" value="状態" name="state">状態</button></td>'
						. '<select id="branch_list_' . htmlspecialchars($prev_row->name) . '" class="form-control" name="branch_form_list">';

				foreach ($branch_list as $branch) {
					$row .= '<option value="' . htmlspecialchars($branch) . '" ' . $this->compare_to_current_branch($branch) '>' . htmlspecialchars($branch) . '</option>';
				}

				$row .= '</select>'
						. '</td>'
						. '<td class="p-center"><button type="button" id="reflect_' . htmlspecialchars($prev_row->name) . '" class="reflect btn btn-default btn-block" value="反映" name="reflect">反映</button></td>'
						. '<td class="p-center"><a href="' . htmlspecialchars($prev_row->url) . '" class="btn btn-default btn-block" target="_blank">プレビュー</a></td>'
						. '</tr>';
			}

			$ret .= $row;
			$ret .= '</tbody></table>';

		}

	}
	
}

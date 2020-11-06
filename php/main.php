<?php

namespace hk\plum;

class main
{
	/**
	 * オプション
	 * 
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
	 * 
	 * temporary_data_dir = '/path/to/temporary_data_dir/'
	 * 
	 * git = array(
	 * 	string 'url':
	 * 		- Gitリポジトリのurl
	 * 		  例) github.com/hk-r/px2-sample-project.git
	 * 		- または、完全なURLとしてまとめて設定ができます
	 * 		  例) https://user:passwd@github.com/hk-r/px2-sample-project.git
	 * 	string 'username':
	 * 		- Gitリポジトリのユーザ名
	 * 		  例) hoge
	 * 	string 'password':
	 * 		- Gitリポジトリのパスワード
	 * 		  例) fuga
	 * )
	 */
	public $options;

	/** hk\plum\plum_deploy のインスタンス */
	private $deploy;

	/** hk\plum\fncs のインスタンス */
	private $fncs;

	/** tomk79/filesystem */
	private $fs;

	/**
	 * コンストラクタ
	 * @param array $options オプション
	 */
	public function __construct($options) {
		$this->fs = new \tomk79\filesystem();
		$this->options = json_decode(json_encode($options));

		// TODO: 削除する
		if( !property_exists($this->options, '_POST') ){
			$this->options->_POST = json_decode(json_encode($_POST));
		}
		// TODO: 削除する
		if( !property_exists($this->options, '_GET') ){
			$this->options->_GET = json_decode(json_encode($_GET));
		}

		if( !property_exists($this->options, 'temporary_data_dir') || !strlen($this->options->temporary_data_dir) ){
			trigger_error('Option `temporary_data_dir` is required.');
			return;
		}elseif( !is_dir($this->options->temporary_data_dir) || !is_writable($this->options->temporary_data_dir) ){
			trigger_error('Option `temporary_data_dir` has to be a writable directory path.');
			return;
		}
		if( !is_dir($this->options->temporary_data_dir.'/local_master/') ){
			$this->fs->mkdir($this->options->temporary_data_dir.'/local_master/');
		}
		$this->fncs = new fncs($this, $this->options);
		$this->deploy = new plum_deploy($this, $this->fncs);
	}

	/**
	 * $fs
	 */
	public function fs(){
		return $this->fs;
	}

	/**
	 * $git
	 */
	public function git($realpath_git_root){
		$git = new git($this, $realpath_git_root);
		return $git;
	}


	/**
	 * 実行する
	 * @return string HTMLソースコード
	 */
	public function run() {

		// 画面表示
		$html_fin = '';

		// エラーメッセージ
		$html_error_msg = '';


		// 初期化ボタンが押下された場合
		if (isset($this->options->_POST->init)) {

			// initialize処理
			$init_ret = $this->fncs->init_all_staging_envs();

			if ( !$init_ret['status'] ) {
				// 初期化失敗

				// エラーメッセージ
				$html_error_msg .= '
				<script type="text/javascript">
					console.error("' . $init_ret['message'] . '");
					alert("initialize faild");
				</script>';
			}
		} 

		// マスタブランチの存在チェック
		$exist_master_flg = $this->fncs->is_local_master_available();

		if (!$exist_master_flg) {

			$html_fin .= $this->fncs->mk_html_before_initialize();

		} else {

			$already_init_ret = $this->fncs->get_initialize_status();

			// initializaされていないプレビューが存在する場合
			if (!$already_init_ret['already_init']) {
				$html_fin .= $this->fncs->mk_html_before_initialize();
			}

			// 反映ボタンの押下
			if (isset($this->options->_POST->reflect)) {

				// deploy処理
				$deploy_ret = $this->deploy->set_deploy($this->options->_POST->preview_server_name, $this->options->_POST->branch_form_list);

				if ( !$deploy_ret['status'] ) {
					// デプロイ失敗

					// エラーメッセージ
					$html_error_msg = '
					<script type="text/javascript">
						console.error("' . $deploy_ret['message'] . '");
						alert("deploy faild");
					</script>';
				}

			}

			$html_fin .= $this->fncs->mk_html_after_initialize();

		}


		// エラーメッセージ
		$html_fin .= $html_error_msg;

		// 画面表示
		return '<div class="plum">'.$html_fin.'</div>';
	}

	/**
	 * 汎用API
	 *
	 * @param  array|object $options GPI実行オプション
	 * @return mixed 実行したAPIの返却値
	 */
	public function gpi($options){
		$gpi = new gpi($this, $this->fncs);
		$rtn = $gpi->gpi( $options );
		return $rtn;
	}

}

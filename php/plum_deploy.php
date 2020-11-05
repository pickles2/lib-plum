<?php

namespace hk\plum;

class plum_deploy
{
	/** hk\plum\main のインスタンス */
	private $main;
	
	/** hk\plum\fncs のインスタンス */
	private $fncs;
	
	/**
	 * コンストラクタ
	 * @param object $main mainのインスタンス
	 * @param object $fncs fncsのインスタンス
	 */
	public function __construct($main, $fncs) {
		$this->main = $main;
		$this->fncs = $fncs;
	}

	/**
	 * deploy
	 * 
	 * @return array result
	 * - $result['status'] boolean deployに成功した場合に true
	 * - $result['current_branch'] string ブランチ名を格納
	 * - $result['message'] string エラー発生時にエラーメッセージが格納される
	 */
	public function set_deploy($preview_server_name, $to_branch) {

		$output = "";
		$result = array(
			'status' => true,
			'message' => ''
		);

		$server_list = $this->main->options->preview_server;
		array_push($server_list, json_decode(json_encode(array(
			'name'=>'master',
			'path'=>$this->main->options->temporary_data_dir.'/local_master/',
		))));

		set_time_limit(0);
		
		foreach ( $server_list as $preview_server ) {

			try {

				if ( trim($preview_server->name) == trim($preview_server_name) ) {

					$to_branch_rep = trim(str_replace("origin/", "", $to_branch));

					// ディレクトリ移動
					if ( is_dir( $preview_server->path ) ) {

						$git = $this->main->git($preview_server->path);

						// 現在のブランチ取得
						$cmdresult = $git->git(array(
							'branch',
						));
						$output = preg_split( '/\r\n|\r|\n/', trim($cmdresult['stdout']) );

						$now_branch = null;
						$already_local_branch_exists = false;
						foreach ( $output as $value ) {

							// 「*」の付いてるブランチを現在のブランチと判定
							if ( strpos($value, '*') !== false ) {

								$value = trim(str_replace("* ", "", $value));
								$now_branch = $value;

							} else {

								$value = trim($value);

							}

							// 選択された(切り替える)ブランチがブランチの一覧に含まれているか判定
							if ( $value == $to_branch_rep ) {
								$already_local_branch_exists = true;
							}
						}

						$url_git_remote = $this->fncs->get_url_git_remote(true);

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

						// git fetch
						$git->git(array(
							'fetch',
						));

						// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
						if ( $now_branch !== $to_branch_rep ) {

							if ($already_local_branch_exists) {
								// 選択された(切り替える)ブランチが既にチェックアウト済みの場合

								$git->git(array(
									'checkout',
									$to_branch_rep,
								));

							} else {
								// 選択された(切り替える)ブランチがまだチェックアウトされてない場合

								$git->git(array(
									'checkout',
									'-b',
									$to_branch_rep,
									$to_branch,
								));

							}
						}

						// git pull
						$git->git(array(
							'pull',
							'-f',
							'origin',
							$to_branch_rep,
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

			} catch (\Exception $e) {
				set_time_limit(30);
				$result['status'] = false;
				$result['message'] = $e->getMessage();
				return $result;
			}

		}
		set_time_limit(30);
		$result['status'] = true;
		return $result;
		
	}
}

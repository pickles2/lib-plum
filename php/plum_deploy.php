<?php

namespace hk\plum;

class plum_deploy
{
	/** hk\plum\mainのインスタンス */
	private $main;
	
	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main) {
		$this->main = $main;
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

		$current_dir = realpath('.');

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
			chdir($current_dir);

			try {

				if ( trim($preview_server->name) == trim($preview_server_name) ) {

					$to_branch_rep = trim(str_replace("origin/", "", $to_branch));

					// ディレクトリ移動
					if ( chdir( $preview_server->path ) ) {

						// 現在のブランチ取得
						exec( 'git branch', $output);

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

						$url_git_remote = $this->main->get_url_git_remote(true);

						// set remote as origin
						exec( 'git remote add origin '.escapeshellarg($url_git_remote), $output );
						exec( 'git remote set-url origin '.escapeshellarg($url_git_remote), $output );

						// git fetch
						exec( 'git fetch', $output );

						// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
						if ( $now_branch !== $to_branch_rep ) {

							if ($already_local_branch_exists) {
								// 選択された(切り替える)ブランチが既にチェックアウト済みの場合

								exec( 'git checkout ' . escapeshellarg($to_branch_rep), $output);

							} else {
								// 選択された(切り替える)ブランチがまだチェックアウトされてない場合

								exec( 'git checkout -b ' . escapeshellarg($to_branch_rep) . ' ' . escapeshellarg($to_branch), $output);
							}
						}

						// git pull
						exec( 'git pull origin ' . escapeshellarg($to_branch_rep), $output );

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

				$url_git_remote = $this->main->get_url_git_remote(false);
				exec( 'git remote set-url origin '.escapeshellarg($url_git_remote), $output );

				chdir($current_dir);
				return $result;
			}

		}
		set_time_limit(30);

		$result['status'] = true;

		$url_git_remote = $this->main->get_url_git_remote(false);
		exec( 'git remote set-url origin '.escapeshellarg($url_git_remote), $output );

		chdir($current_dir);
		return $result;
		
	}
}

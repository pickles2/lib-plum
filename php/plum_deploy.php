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
	 */
	public function set_deploy($preview_server_name, $to_branch) {

		$current_dir = realpath('.');

		$output = "";
		$result = array('status' => true,
						'message' => '');

		$server_list = $this->main->options->preview_server;
		array_push($server_list, json_decode(json_encode(array(
			'name'=>'master',
			'path'=>$this->main->options->git->repository,
		))));

		foreach ( $server_list as $preview_server ) {
			chdir($current_dir);

			try {

				if ( trim($preview_server->name) == trim($preview_server_name) ) {

					$to_branch_rep = trim(str_replace("origin/", "", $to_branch));

					// ディレクトリ移動
					if ( chdir( $preview_server->path ) ) {

						// 現在のブランチ取得
						exec( 'git branch', $output);

						$now_branch;
						$already_branch_checkout = false;
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
								$already_branch_checkout = true;
							}
						}

						// 現在のブランチと選択されたブランチが異なる場合は、ブランチを切り替える
						if ( $now_branch !== $to_branch_rep ) {

							if ($already_branch_checkout) {
								// 選択された(切り替える)ブランチが既にチェックアウト済みの場合

								exec( 'git checkout ' . $to_branch_rep, $output);

							} else {
								// 選択された(切り替える)ブランチがまだチェックアウトされてない場合

								exec( 'git checkout -b ' . $to_branch_rep . ' ' . $to_branch, $output);
							}
						}

						// git fetch
						exec( 'git fetch origin', $output );

						// git pull
						exec( 'git pull origin ' . $to_branch_rep, $output );

					} else {
						// プレビューサーバのディレクトリが存在しない場合

						// エラー処理
						throw new Exception('Preview server directory not found.');
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

		chdir($current_dir);
		return json_encode($result);
		
	}
}

<?php

require_once(__DIR__.'/../vendor/autoload.php');

namespace hk\plum;

class plum_git
{
	/** hk\plum\mainのインスタンス */
	private $main;

	/** PHPGit\Gitのインスタンス */
	private $git;
	
	/**
	 * コンストラクタ
	 * @param $main = mainのインスタンス
	 */
	public function __construct($main) {
		$this->main = $main;
		$this->git = new PHPGit\Git();
	}

	/**
	 * status
	 */
	public function get_status($preview_server_name) {

		foreach ( $server_list as $preview_server ) {

			try {

				if ( trim($preview_server->name) == trim($preview_server_name) ) {

					$ret = $this->git->status($preview_server->path);
					return $ret;

				}

			} catch (Exception $e) {
				
			}
		}

		return 0;
		
	}
}

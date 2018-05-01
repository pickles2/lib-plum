<?php
namespace hk\plum;

require_once(__DIR__.'/../../../../vendor/autoload.php');

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
		$this->git = new \PHPGit\Git();
	}

	/**
	 * status
	 */
	public function status($preview_server_name) {

		$server_list = $this->main->options->preview_server;

		foreach ( $server_list as $preview_server ) {

			if ( trim($preview_server->name) == trim($preview_server_name) ) {

				$this->git->setRepository(realpath($preview_server->path));
				$this->git->fetch('origin');
				$ret = $this->git->remote();
				
				return $ret;

			}
		}

		return "";
		
	}
}

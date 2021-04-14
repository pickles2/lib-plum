<?php

namespace hk\plum;

class main
{
	/**
	 * オプション
	 * 
	 * staging_server = array(
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
	 * data_dir = '/path/to/data_dir/'
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

		if(
			(!property_exists($this->options, 'data_dir')
			|| !strlen($this->options->data_dir))
			&& property_exists($this->options, 'temporary_data_dir')
		){
			$this->options->data_dir = $this->options->temporary_data_dir;
		}
		if( !property_exists($this->options, 'data_dir') || !strlen($this->options->data_dir) ){
			trigger_error('Option `data_dir` is required.');
			return;
		}elseif( !is_dir($this->options->data_dir) || !is_writable($this->options->data_dir) ){
			trigger_error('Option `data_dir` has to be a writable directory path.');
			return;
		}
		if( !is_dir($this->options->data_dir.'/local_master/') ){
			$this->fs->mkdir($this->options->data_dir.'/local_master/');
		}
		$this->fncs = new fncs($this, $this->options);
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

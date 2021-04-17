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
	 *
	 * async = function( $params ){}
	 *
	 * broadcast = function( $message ){}
	 */
	private $options;

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
		$this->options = (object) $options;

		if(
			(!property_exists($this->options, 'data_dir')
			|| !strlen($this->options->data_dir))
			&& property_exists($this->options, 'temporary_data_dir')
		){
			// `temporary_data_dir` は、`v0.2.0` までの古い名前。
			// 互換性のために残してある。
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

		if( property_exists($this->options, 'git') && (is_object($this->options->git) || is_array($this->options->git)) ){
			$this->options->git = (object) $this->options->git;
		}else{
			$this->options->git = null;
		}

		if( !property_exists($this->options, 'async') || !is_callable($this->options->async) ){
			$this->options->async = null;
		}
		if( !property_exists($this->options, 'broadcast') || !is_callable($this->options->broadcast) ){
			$this->options->broadcast = null;
		}
		if( !is_callable($this->options->async) || !is_callable($this->options->broadcast) ){
			// async() と broadcast() の両方が利用可能である必要がある。
			$this->options->async = null;
			$this->options->broadcast = null;
		}

		$this->fncs = new fncs($this);
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
	 * Options
	 */
	public function get_options(){
		return $this->options;
	}

	/**
	 * 汎用API
	 *
	 * @param  array|object $params GPI実行パラメータ
	 * @return mixed 実行したAPIの返却値
	 */
	public function gpi($params){
		$gpi = new gpi($this, $this->fncs);
		$rtn = $gpi->gpi( $params );
		return $rtn;
	}

	/**
	 * Async API
	 *
	 * @param  array|object $params 非同期実行パラメータ
	 * @return mixed 実行したAPIの返却値
	 */
	public function async( $params ){
		$async = new async($this, $this->fncs);
		$rtn = $async->async( $params );
		return $rtn;
	}
}

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
	 * htpasswd_hash_algorithm = 'bcrypt'|'md5'|'sha1'|'crypt'|'plain'
	 */
	private $options;

	/** hk\plum\fncs のインスタンス */
	private $fncs;

	/** tomk79/filesystem */
	private $fs;

	/** 非同期コールバック関数 */
	private $callback_async;
	private $callback_broadcast;


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

		$this->options->staging_server = json_decode( json_encode( $this->options->staging_server ) );

		if( property_exists($this->options, 'git') && (is_object($this->options->git) || is_array($this->options->git)) ){
			$this->options->git = (object) $this->options->git;
		}else{
			$this->options->git = null;
		}

		if( property_exists($this->options, 'htpasswd_hash_algorithm') && (is_object($this->options->htpasswd_hash_algorithm) || is_array($this->options->htpasswd_hash_algorithm)) ){
			$this->options->htpasswd_hash_algorithm = (object) $this->options->htpasswd_hash_algorithm;
		}else{
			$this->options->htpasswd_hash_algorithm = null;
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
	 * 非同期関数を登録する
	 */
	public function set_async_callbacks( $fncs ){
		$fncs = (object) $fncs;

		if( !property_exists($fncs, 'async') || !is_callable($fncs->async) ){
			$fncs->async = null;
		}
		if( !property_exists($fncs, 'broadcast') || !is_callable($fncs->broadcast) ){
			$fncs->broadcast = null;
		}
		if( !is_callable($fncs->async) || !is_callable($fncs->broadcast) ){
			// async() と broadcast() の両方が利用可能である必要がある。
			$fncs->async = null;
			$fncs->broadcast = null;
		}

		$this->callback_async = $fncs->async;
		$this->callback_broadcast = $fncs->broadcast;
		return;
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

	/**
	 * Async API が利用可能か調べる
	 */
	public function is_async_available(){
		if( !is_callable($this->callback_async) ){
			return false;
		}
		if( !is_callable($this->callback_broadcast) ){
			return false;
		}
		return true;
	}

	/**
	 * 非同期コールバックを呼び出す
	 */
	public function call_async_callback( $params ){
		if( !$this->is_async_available() ){
			return false;
		}
		call_user_func($this->callback_async, $params );
		return true;
	}

	/**
	 * ブロードキャストコールバックを呼び出す
	 */
	public function call_broadcast_callback( $params ){
		if( !$this->is_async_available() ){
			return false;
		}
		call_user_func($this->callback_broadcast, $params );
		return true;
	}

}

<?php

namespace hk\plum;

class main
{
	/** オプション */
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
	public function init() {

	}

	/**
	 * initalizeの状態取得
	 */
	public function get_initialize_status() {

	}

	/**
	 * ブランチリストを取得
	 */
	public function get_parent_branch_list() {

	}

	/**
	 * 現在のブランチを取得する
	 */
	public function get_child_current_branch() {

	}

	/**
	 * deploy実行
	 */
	public function deploy() {

	}

	
}

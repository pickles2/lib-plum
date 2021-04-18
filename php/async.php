<?php
/**
 * Plum/AsyncAPI
 */
namespace hk\plum;


/**
 * async.php (General Purpose Interface)
 */
class async{

	/** $main */
	private $main;

	/** $fncs */
	private $fncs;

	/**
	 * Constructor
	 */
	public function __construct($main, $fncs){
		$this->main = $main;
		$this->fncs = $fncs;
	}


	/**
	 * Async API
	 */
	public function async($params){
		$params = (array) $params;
		if( !array_key_exists('api', $params) ){
			$params['api'] = null;
		}
		if( !array_key_exists('lang', $params) ){
			$params['lang'] = null;
		}
		if( !strlen($params['lang']) ){
			$params['lang'] = 'en';
		}
		if( !array_key_exists('broadcast_callback_id', $params) ){
			$params['broadcast_callback_id'] = null;
		}

		// $this->main->lb()->setLang( $params['lang'] );

		$result = array();
		$result['api'] = $params['api'];
		$result['broadcast_callback_id'] = $params['broadcast_callback_id'];
		$result['status'] = true;
		$result['message'] = 'OK';

		switch($params['api']){

			case "init_staging_env":
				$staging_index = null;
				if( array_key_exists('index', $params) && strlen($params['index']) ){
					$staging_index = $params['index'];
				}
				$staging_branch_name = null;
				if( array_key_exists('branch_name', $params) && strlen($params['branch_name']) ){
					$staging_branch_name = $params['branch_name'];
				}
				$result = array_merge($result, $this->fncs->init_staging_env( $staging_index, $staging_branch_name ));
				$this->main->call_broadcast_callback($result);
				return $result;

		}

		$result['status'] = false;
		$result['message'] = 'Unknown API.';
		return $result;
	}

}

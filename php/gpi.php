<?php
/**
 * Plum/GPI
 */
namespace hk\plum;


/**
 * gpi.php (General Purpose Interface)
 */
class gpi{

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
	 * General Purpose Interface
	 */
	public function gpi($params){
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

		// $this->main->lb()->setLang( $params['lang'] );

		$result = array();
		$result['status'] = true;
		$result['message'] = 'OK';

		switch($params['api']){

			case "get_condition":
				$result['is_local_master_available'] = $this->fncs->is_local_master_available();
				$result['staging_server'] = $this->main->get_options()->staging_server;
				$htpasswd = new htpassword($this->main, $this->fncs);
				foreach( $result['staging_server'] as $idx=>$row ){
					$row = (array) $row;
					$row['index'] = $idx;

					$row['current_branch'] = null;
					$tmp_current_branch = $this->fncs->get_current_branch($row['path']);
					if( $tmp_current_branch['status'] ){
						$row['current_branch'] = $tmp_current_branch['current_branch'];
					}

					if( array_key_exists('path', $row) ){
						// 不要な情報はフロントへ送らない
						unset( $row['path'] );
					}

					$row['htpasswd'] = $htpasswd->get($idx);

					$result['staging_server'][$idx] = $row;
				}
				$branchlist = $this->fncs->get_remote_branch_list();
				$result['remote_branch_list'] = null;
				if( $branchlist['status'] ){
					$result['remote_branch_list'] = $branchlist['branch_list'];
				}
				return $result;

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
				return $result;

			case "update_htpassword":
				$staging_index = null;
				if( array_key_exists('index', $params) && strlen($params['index']) ){
					$staging_index = $params['index'];
				}
				$username = null;
				if( array_key_exists('user_name', $params) && strlen($params['user_name']) ){
					$username = $params['user_name'];
				}
				$password = null;
				if( array_key_exists('user_password', $params) && strlen($params['user_password']) ){
					$password = $params['user_password'];
				}

				$htpasswd = new htpassword($this->main, $this->fncs);
				$result = array_merge($result, $htpasswd->update($staging_index, $username, $password));

				return $result;

		}

		$result['status'] = false;
		$result['message'] = 'Unknown API.';
		return $result;
	}

}

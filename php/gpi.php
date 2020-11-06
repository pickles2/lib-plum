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
	public function gpi($options){
		$options = (array) $options;
		if( !array_key_exists('api', $options) ){
			$options['api'] = null;
		}
		if( !array_key_exists('lang', $options) ){
			$options['lang'] = null;
		}
		if( !strlen($options['lang']) ){
			$options['lang'] = 'en';
		}

		// $this->main->lb()->setLang( $options['lang'] );

		switch($options['api']){

			case "get_condition":
				$result = array();
				$result['is_local_master_available'] = $this->fncs->is_local_master_available();
				$result['preview_server'] = $this->main->options->preview_server;
				foreach( $result['preview_server'] as $idx=>$row ){
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

					$result['preview_server'][$idx] = $row;
				}
				$branchlist = $this->fncs->get_remote_branch_list();
				$result['remote_branch_list'] = null;
				if( $branchlist['status'] ){
					$result['remote_branch_list'] = $branchlist['branch_list'];
				}
				return $result;

			case "init_staging_env":
				$staging_index = null;
				if( array_key_exists('index', $options) && strlen($options['index']) ){
					$staging_index = $options['index'];
				}
				$staging_branch_name = null;
				if( array_key_exists('branch_name', $options) && strlen($options['branch_name']) ){
					$staging_branch_name = $options['branch_name'];
				}
				$result = $this->fncs->init_staging_env( $staging_index, $staging_branch_name );
				return $result;

			default:
				return false;
		}

		return true;
	}

}

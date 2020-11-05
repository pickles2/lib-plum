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

	/**
	 * Constructor
	 */
	public function __construct($main){
		$this->main = $main;
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
				return $result;

			default:
				return true;
		}

		return true;
	}

}

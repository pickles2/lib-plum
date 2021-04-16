<?php
/**
 * Plum/htpasswd
 */
namespace hk\plum;


/**
 * gpi.php (General Purpose Interface)
 */
class htpassword{

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
	 * htpasswd ファイルを更新する
	 */
	public function update($staging_index, $username, $password){
		$result = array();

		$options = $this->main->get_options();
		if( !$this->main->fs()->is_dir($options->data_dir) ){
			$result['status'] = false;
			$result['message'] = '[ERROR] `data_dir` is not a directory.';
			return $result;
		}

		$realpath_dir_htpasswds = $this->main->fs()->get_realpath($options->data_dir.'/htpasswds/');
		if( !$this->main->fs()->is_dir($realpath_dir_htpasswds) ){
			if( !$this->main->fs()->mkdir($realpath_dir_htpasswds) ){
				$result['status'] = false;
				$result['message'] = '[ERROR] Failed to mkdir `htpasswds`.';
				return $result;
			}
		}

		$filename = 'stg'.urlencode($staging_index).'.htpasswd';

		if( strlen($username) ){
			// --------------------------------------
			// パスワードを保存する
			$hashed_passwd = md5($password);

			$src = '';
			$src .= $username.':'.$hashed_passwd."\n";
			if( !$this->main->fs()->save_file($realpath_dir_htpasswds.$filename, $src) ){
				$result['status'] = false;
				$result['message'] = '[ERROR] Failed to save .htpasswd';
				return $result;
			}

		}else{
			// --------------------------------------
			// パスワードを解除する
			if( $this->main->fs()->is_file($realpath_dir_htpasswds.$filename) && !$this->main->fs()->rm($realpath_dir_htpasswds.$filename) ){
				$result['status'] = false;
				$result['message'] = '[ERROR] Failed to remove .htpasswd';
				return $result;
			}
		}

		return $result;
	}
}

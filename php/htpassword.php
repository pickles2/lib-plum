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
	 * htpasswd の情報を取得する
	 *
	 * @param int $staging_index ステージングのインデックス番号
	 * @return array 実行結果
	 */
	public function get($staging_index){
		$result = array();
		$options = $this->main->get_options();
		if( !$this->main->fs()->is_dir($options->data_dir) ){
			return false;
		}

		$realpath_dir_htpasswds = $this->main->fs()->get_realpath($options->data_dir.'/htpasswds/');
		$filename = 'stg'.urlencode($staging_index).'.htpasswd';

		$result['auth_required'] = false;
		$result['user_name'] = null;
		if( $this->main->fs()->is_file($realpath_dir_htpasswds.$filename) ){
			$result['auth_required'] = true;
			$bin = $this->main->fs()->read_file($realpath_dir_htpasswds.$filename);
			$htpasswd_ary = explode(':', $bin, 2);
			$result['user_name'] = $htpasswd_ary[0];
		}

		return $result;
	}

	/**
	 * htpasswd ファイルを更新する
	 *
	 * @param int $staging_index ステージングのインデックス番号
	 * @param string $username 基本認証のID
	 * @param string $password 基本認証のパスワード
	 * @return array 実行結果
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

			// パスワードハッシュを生成する
			$hashed_passwd = $password;
			$hash_algorithm = $this->main->get_options()->htpasswd_hash_algorithm;
			switch( $hash_algorithm ){
				case 'bcrypt':
					$hashed_passwd = password_hash($password, PASSWORD_BCRYPT);
					break;

				case 'md5':
					$hashed_passwd = md5($password);
					break;

				case 'sha1':
					$hashed_passwd = sha1($password);
					break;

				case 'plain':
					$hashed_passwd = $password;
					break;

				case 'crypt':
				default:
					$hashed_passwd = crypt($password, substr(trim($username), -2));
					break;
			}

			$src = '';
			$src .= trim($username).':'.$hashed_passwd."\n";
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

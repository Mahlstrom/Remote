<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 12/05/14
 * Time: 08:18
 */

namespace mahlstrom\Remote;

use Net_SFTP;

class SFTP implements remoteInterface{
	private $sftp = false;
	public function __construct($url, $user, $password, $port = 22) {
		$this->sftp = new Net_SFTP($url);
		if(!$this->sftp->login($user, $password)){
			exit('Login Failed');
		}
	}

	public function nlist($path = '.') {
		return $this->sftp->nlist($path);
	}

	public function mkdir($dir, $mode = -1, $recursive = false) {
		if($this->sftp->mkdir($dir, $mode, $recursive)){
			return $dir;
		}else{
			return false;
		}
	}

	public function put( $remote_file,$local_file, $mode = NET_SFTP_LOCAL_FILE, $start = -1, $local_start = -1) {
		return $this->sftp->put($remote_file,$local_file,$mode,$start,$local_start);
	}

	public function chdir($dir) {
		return $this->sftp->chdir($dir);
	}

	public function chmod($mode, $filename, $recursive = false) {
		// TODO: Implement chmod() method.
	}

	public function chown($filename, $uid, $recursive = false) {
		// TODO: Implement chown() method.
	}

	public function chgrp($filename, $gid, $recursive = false) {
		// TODO: Implement chgrp() method.
	}

	public function close() {
	}

	public function delete($path) {
		return $this->sftp->delete($path);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($remote_file, $local_file, $offset = 0) {
		return $this->sftp->get($remote_file,$local_file,$offset);
	}

	public function is_connected() {
		if($this->sftp->isConnected()){
			return true;
		}else{
			return false;
		}
	}

	public function isTimeout() {
		// TODO: Implement isTimeout() method.
	}

	public function pwd() {
		return $this->sftp->pwd();
	}

	public function rawlist($path) {
		// TODO: Implement rawlist() method.
	}

	public function rename($old_name, $new_name) {
		// TODO: Implement rename() method.
	}

	public function rmdir($path) {
		return $this->sftp->rmdir($path);
	}

	public function size($filename) {
		// TODO: Implement size() method.
	}

	public function stat($filename) {
		// TODO: Implement stat() method.
	}

	public function isConnected() {
		return $this->is_connected();
	}
}

<?php
/**
 * Created by Mahlstrom.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */
namespace mahlstrom\Remote;

use DateInterval;
use DateTime;

/**
 * Class FTP
 * This class is used for connecting to FTP servers.
 *
 * @package mahlstrom\Remote
 * @author Mahlstrom
 */
class FTP implements RemoteInterface {

	/**
	 * Hostname param is only used for error reporting
	 *
	 * @var string
	 */
	private $hostname = '';
	/**
	 * Holds the ftp connect resource.
	 *
	 * @var bool|resource
	 */
	private $conn_id = false;
	/**
	 * Tells the system type.
	 *
	 * @var bool|string
	 */
	private $system = false;

	/**
	 * Coonn
	 *
	 * @param string $hostName The hostname can be both the domain and the IP
	 * @param string $user
	 * @param string $password
	 * @param int $port
	 * @param int $timeout
	 * @throws RemoteConnectException
	 */
	public function __construct($hostName, $user, $password, $port = 21, $timeout = 10) {

		$this->hostname = $hostName;
		$this->conn_id = ftp_connect($hostName, $port, $timeout);
		if(!$this->conn_id) {
			unset($this);
			throw new RemoteConnectException($hostName . ' is dead');
		}
		ftp_login($this->conn_id, $user, $password);
		$this->system = ftp_systype($this->conn_id);

		return true;
	}

	/**
	 * Returns an array of directories or bool if directory not found
	 *
	 * @param string $path
	 * @return array|bool
	 * @throws RemoteNListException
	 */
	public function readDir($path = '.') {

		if(!@ftp_chdir($this->conn_id, $path)) {
			return false;
		}
		$rawfiles = ftp_rawlist($this->conn_id, $path);
		if($rawfiles == false) {
			throw new RemoteNListException('"' . $path . '" @ ' . $this->hostname);
		}
		$structure = array();
		$arraypointer = & $structure;
		switch($this->system) {
			case 'UNIX':
				foreach($rawfiles as $rawfile) {
					if($rawfile[0] == '/') {
						$paths = array_slice(explode('/', str_replace(':', '', $rawfile)), 1);
						$arraypointer = & $structure;
						foreach($paths as $path) {
							foreach($arraypointer as $i => $file) {
								if($file['text'] == $path) {
									$arraypointer = & $arraypointer[$i]['children'];
									break;
								}
							}
						}
					} elseif(!empty($rawfile)) {
						$info = preg_split("/[\s]+/", $rawfile, 9);
						$name = $info[8];
						$arraypointer[$name] = (object)array(
							'name'   => $name,
							'is_dir' => $info[0]{0} == 'd',
							'size'   => $info[4],
							'Hsize'  => $this->byteConvert($info[4]),
							'chmod'  => $this->chModNum($info[0]),
							'date'   => $this->fileDateToTime($info[5] . ' ' . $info[6] . ' ' . $info[7])
						);
					}
				}
				break;
			case 'Windows_NT':
				$i = 0;
				foreach($rawfiles as $current) {
					preg_match("/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/", $current, $split);
					if(is_array($split)) {
						$parsed = array();
						if($split[3] < 70) {
							$split[3] += 2000;
						} else {
							$split[3] += 1900;
						} // 4digit year fix

						$parsed['is_dir'] = ($split[7] == '<' . 'DIR>');
						$parsed['size'] = $split[7];
						$parsed['month'] = $split[1];
						$parsed['day'] = $split[2];
						$parsed['time/year'] = $split[3];
						$parsed['name'] = $split[8];
						$parsed['hour'] = $split[4];
						$parsed['min'] = $split[5];
						$parsed['date'] = $this->fileDateToTime($split[3] . '-' . $split[1] . '-' . $split[2] . '-' . $split[4] . ':' . $split[5]);
						$i++;
						$structure[] = (object)$parsed;
					}
				}
#                print_r($parsed);
				break;
			default:
		}

		return $structure;
	}

	/**
	 * Get nlist rawdata
	 *
	 * @param string $remoteDirectory
	 * @return array
	 */
	public function nlist($remoteDirectory = '.') {

		$arr = ftp_nlist($this->conn_id, $remoteDirectory);

		return $arr;
	}

	/**
	 * Download a file from remote server
	 *
	 * @param string $localFile Path to the local file
	 * @param string $remoteFile Path to the remote file
	 * @param int $offset
	 * @return bool
	 */
	public function get($localFile, $remoteFile, $offset = 0) {

		$pathInfo = pathinfo($localFile);
		if(!is_dir($pathInfo['dirname'])) {
			mkdir($pathInfo['dirname'], 0775, true);
		}

		return ftp_get($this->conn_id, $localFile, $remoteFile, FTP_BINARY, $offset);
	}

	/**
	 * Create a new folder
	 *
	 * @param $dir
	 * @param $mode
	 * @param bool $recursive
	 * @return string
	 */
	public function mkdir($dir, $mode = -1, $recursive = false) {

		return ftp_mkdir($this->conn_id, $dir);
	}

	/**
	 * Get the server type (Unix/Windows)
	 *
	 * @return string
	 */
	public function getServerType() {

		return $this->system;
	}

	/**
	 * Close the connection
	 *
	 * @return bool
	 */
	public function close() {

		$closed = false;
		if($this->conn_id) {
			$closed = ftp_close($this->conn_id);
			$this->conn_id = false;
		}

		return $closed;
	}

	/**
	 * Check if connection to ftp is done/not closed
	 *
	 * @return bool
	 */
	public function is_connected() {

		if($this->conn_id) {
			return true;
		}

		return false;
	}

	/**
	 * At dectruction calls close
	 */
	public function __destruct() {

		$this->close();
	}

	/**
	 * @param $bytes
	 * @return int|string
	 * @ignore
	 */
	private function byteConvert($bytes) {

		if($bytes) {
			$symbol = array(' B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$exp = (int)floor(log($bytes) / log(1024));

			return sprintf('%.2f ' . $symbol[$exp], ($bytes / pow(1024, floor($exp))));
		} else {
			return 0;
		}
	}

	/**
	 * @param $chmod
	 * @return string
	 * @ignore
	 */
	private function chModNum($chmod) {

		$trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
		$chmod = substr(strtr($chmod, $trans), 1);
		$array = str_split($chmod, 3);

		return array_sum(str_split($array[0])) . array_sum(str_split($array[1])) . array_sum(str_split($array[2]));
	}

	/**
	 * @param $dateString
	 * @return string
	 * @ignore
	 */
	function fileDateToTime($dateString) {

		try {
			$date = new DateTime($dateString);
		} catch(\Exception $e) {
			echo $e->getMessage();
			exit(1);
		}
		$now = new DateTime();
		if($date > $now) {
			$date->sub(new DateInterval('P1Y'));
		}

		return $date->format('U');
	}

	/**
	 * Upload a file to remote server
	 *
	 * @param string $localFile
	 * @param string $remoteFile
	 * @param int $mode
	 */
	public function put($localFile, $remoteFile,$mode=0755) {
		// TODO: Implement put() method.
	}


	/**
	 * @param $dir
	 */
	public function chdir($dir) {
		// TODO: Implement chdir() method.
	}

	/**
	 * @param $mode
	 * @param $filename
	 * @param bool $recursive
	 */
	public function chmod($mode, $filename, $recursive = false) {
		// TODO: Implement chmod() method.
	}

	/**
	 * @param $filename
	 * @param $uid
	 * @param bool $recursive
	 */
	public function chown($filename, $uid, $recursive = false) {
		// TODO: Implement chown() method.
	}

	/**
	 * @param $filename
	 * @param $gid
	 * @param bool $recursive
	 */
	public function chgrp($filename, $gid, $recursive = false) {
		// TODO: Implement chgrp() method.
	}

	/**
	 * @param $path
	 * @param bool $recursive
	 */
	public function delete($path, $recursive = false) {
		// TODO: Implement delete() method.
	}

	/**
	 *
	 */
	public function isConnected() {
		// TODO: Implement isConnected() method.
	}

	/**
	 *
	 */
	public function isTimeout() {
		// TODO: Implement isTimeout() method.
	}

	/**
	 *
	 */
	public function pwd() {
		// TODO: Implement pwd() method.
	}

	/**
	 * @param $path
	 */
	public function rawlist($path) {
		// TODO: Implement rawlist() method.
	}

	/**
	 * @param $old_name
	 * @param $new_name
	 */
	public function rename($old_name, $new_name) {
		// TODO: Implement rename() method.
	}

	/**
	 * @param $path
	 */
	public function rmdir($path) {
		// TODO: Implement rmdir() method.
	}

	/**
	 * @param $filename
	 */
	public function size($filename) {
		// TODO: Implement size() method.
	}

	/**
	 * @param $filename
	 */
	public function stat($filename) {
		// TODO: Implement stat() method.
	}
}


/**
 * Class RemoteConnectException
 *
 * @package mahlstrom\Remote
 */
class RemoteConnectException extends \Exception {

}


/**
 * Class RemoteNListException
 *
 * @package mahlstrom\Remote
 */
class RemoteNListException extends \Exception {

}

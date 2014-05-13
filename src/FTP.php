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
use mahlstrom\Remote\Exceptions\RemoteLoginException;

/**
 * Class FTP
 * This class is used for connecting to FTP servers.
 *
 * @package mahlstrom\Remote
 * @author Mahlstrom
 */
class FTP extends RemoteHelper implements RemoteInterface {

	/**
	 * Hostname param is only used for error reporting
	 *
	 * @var string
	 */
	private $hostname = '';
	private $user = '';
	private $password = '';

	/**
	 * Holds the ftp connect resource.
	 *
	 * @var bool|resource
	 */
	public $conn_id = false;
	/**
	 * Tells the system type.
	 *
	 * @var bool|string
	 */
	private $system = false;

	/**
	 * @param $hostName
	 * @param $user
	 * @param $password
	 * @param int $port
	 * @param int $timeout
	 * @throws Exceptions\RemoteConnectException
	 * @throws Exceptions\RemoteLoginException
	 */
	public function __construct($hostName, $user, $password, $port = 21, $timeout = 10) {

		$this->hostname = $hostName;
		$this->user = $user;
		$this->password = $password;
		$this->conn_id = ftp_connect($hostName, $port, $timeout);
		if(!$this->conn_id) {
			unset($this);
			throw new Exceptions\RemoteConnectException($hostName . ' is dead');
		}

		$loginResult = @ftp_login($this->conn_id, $user, $password);
		if(!$loginResult) {
			throw new RemoteLoginException();
		}
		$this->system = ftp_systype($this->conn_id);
		$this->userRoot = $this->pwd();
		if(substr($this->userRoot, -1, 1) != '/') {
			$this->userRoot .= '/';
		}

		return true;
	}

	/**
	 * Get nlist raw data
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

		$this->_checkSoLocalDirExists($localFile);

		return ftp_get($this->conn_id, $localFile, $remoteFile, FTP_BINARY, $offset);
	}

	/**
	 * {@inheritDoc}
	 */
	public function mkdir($dir, $mode = -1, $recursive = false) {

		if(@ftp_mkdir($this->conn_id, $dir)) {
			return $dir;
		}

		return false;
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
	 * @deprecated
	 * @codeCoverageIgnore
	 */
	public function is_connected() {

		return $this->isConnected();
	}

	/**
	 * At destruction calls close
	 *
	 * @codeCoverageIgnore
	 */
	public function __destruct() {

		$this->close();
	}

	/**
	 * @param $dateString
	 * @return string
	 * @codeCoverageIgnore
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
	 * @return bool
	 */
	public function put($localFile, $remoteFile, $mode = FTP_BINARY) {

		return ftp_put($this->conn_id, $remoteFile, $localFile, $mode);
	}


	/**
	 * Remove a file
	 *
	 * @param string $path
	 * @return bool
	 */
	public function delete($path) {

		return ftp_delete($this->conn_id, $path);
	}


	/**
	 * @param string $dir
	 * @return bool
	 */
	public function chdir($dir) {

		return ftp_chdir($this->conn_id, $dir);
	}

	/**
	 * @returns bool
	 */
	public function isConnected() {

		if($this->conn_id) {
			return true;
		}

		return false;
	}

	/**
	 *
	 */
	public function pwd() {

		return ftp_pwd($this->conn_id);
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function rawlist($path = '.') {

		return ftp_rawlist($this->conn_id, $path);
	}

	public function readDir($path = '.') {

		if(!@ftp_chdir($this->conn_id, $path)) {
			return false;
		}
		$rawFiles = $this->rawlist($path);
		if($rawFiles == false) {
			// @codeCoverageIgnoreStart
			throw new Exceptions\RemoteNListException('"' . $path . '" @ ' . $this->hostname);
			// @codeCoverageIgnoreEnd
		}
		$structure = array();
		$arrayPointer = & $structure;
		switch($this->system) {
			case 'UNIX':
				foreach($rawFiles as $rawFile) {
					if($rawFile[0] == '/') {
						// @codeCoverageIgnoreStart
						$paths = array_slice(explode('/', str_replace(':', '', $rawFile)), 1);
						$arrayPointer = & $structure;
						foreach($paths as $path) {
							foreach($arrayPointer as $i => $file) {
								if($file['text'] == $path) {
									$arrayPointer = & $arrayPointer[$i]['children'];
									break;
								}
							}
						}
						// @codeCoverageIgnoreEnd
					} elseif(!empty($rawFile)) {
						$info = preg_split("/[\s]+/", $rawFile, 9);
						if(count($info) >= 8) {
							$name = $info[8];
							$arrayPointer[$name] = new RemoteFile();

							$arrayPointer[$name]->name = $name;
							$arrayPointer[$name]->is_dir = $info[0]{0} == 'd';
							$arrayPointer[$name]->size = $info[4];
							$arrayPointer[$name]->Hsize = $this->byteConvert($info[4]);
							$arrayPointer[$name]->mode = $this->chModNum($info[0]);
							$arrayPointer[$name]->date = $this->fileDateToTime($info[5] . ' ' . $info[6] . ' ' . $info[7]);
						}
					}
				}
				break;
		}

		return $structure;
	}

	public function rmdir($path) {

		return ftp_rmdir($this->conn_id, $path);
	}

	public function chmod($mode, $filename) {

		return @ftp_chmod($this->conn_id, $mode, $filename);
	}

	public function rename($oldName, $newName) {

		if(@ftp_rename($this->conn_id, $oldName, $newName)) {
			return true;
		}

		return false;
	}

	public function size($filename) {

		$result = ftp_size($this->conn_id, $filename);
		if($result == -1) {
			return false;
		}

		return $result;
	}

	public function stat($filename) {

		$pathInfo = pathinfo($filename);
		$result = $this->readDir($pathInfo['dirname']);
		if(array_key_exists($filename, $result)) {
			return $result[$filename];
		} else {
			return false;
		}
	}
}

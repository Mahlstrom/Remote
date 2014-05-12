<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 10/05/14
 * Time: 10:23
 */
namespace mahlstrom\Remote;

interface remoteInterface {

	public function chdir($dir);

	public function chmod($mode, $filename, $recursive = false);

	public function chown($filename, $uid, $recursive = false);

	public function chgrp($filename, $gid, $recursive = false);

	public function close();

	public function delete($path);

	/**
	 * Fetch a file from remote and write to local
	 *
	 * @param $remote_file
	 * @param $local_file
	 * @param int $offset
	 * @return mixed
	 */
	public function get($remote_file, $local_file, $offset = 0);

	/**
	 * @param $local_file
	 * @param $remote_file
	 * @param int $mode
	 * @return bool
	 */
	public function put($local_file, $remote_file, $mode = 0755);

	public function isConnected();

	public function isTimeout();

	public function mkdir($dir, $mode = -1, $recursive = false);

	public function nlist($dir = '.');

	public function pwd();

	public function rawlist($path);

	public function rename($old_name, $new_name);

	public function rmdir($path);

	public function size($filename);

	public function stat($filename);
}
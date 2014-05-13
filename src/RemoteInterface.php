<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 10/05/14
 * Time: 10:23
 */
namespace mahlstrom\Remote;

interface remoteInterface {

	/**
	 * Changes the current directory on the server
	 *
	 * @param string $directory <p>
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function chdir($directory);

	/**
	 * Set permissions on a file on the server
	 *
	 * @param int $mode <p>
	 * The new permissions, given as an octal value.
	 * @param string $filename <p>
	 * The remote file.
	 * @return int the new file permissions on success or <b>FALSE</b> on error.
	 */
	public function chmod($mode, $filename);

	/**
	 * Deletes a file on the server
	 *
	 * @param string $path
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function delete($path);

	/**
	 * Fetch a file from remote and write to local
	 *
	 * @param string $remote_file
	 * @param string $local_file
	 * @return mixed
	 */
	public function get($remote_file, $local_file);

	/**
	 * Uploads a file to the server
	 *
	 * @param string $local_file
	 * @param string $remote_file
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function put($local_file, $remote_file);

	/**
	 * Is the connection still active?
	 *
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	public function isConnected();

	/**
	 * Creates a directory
	 *
	 * @param string $dir
	 * @param bool $recursive
	 * @return string|bool the newly created directory name on success or <b>FALSE</b> on error.
	 */
	public function mkdir($dir, $recursive = false);

	/**
	 * Returns a list of files in the given directory
	 *
	 * @param string $dir
	 * @return array An array of file names from the specified directory on success or
	 * <b>FALSE</b> on error.
	 */
	public function nlist($dir = '.');

	/**
	 * Returns the current directory name
	 *
	 * @return string the current directory name or <b>FALSE</b> on error.
	 */
	public function pwd();

	/**
	 * Returns a detailed list of files in the given directory
	 *
	 * @param string $dir
	 * @return array An array with the corresponding output of rawlist for each protocol.
	 */
	public function rawlist($dir = '.');

	/**
	 * Returns an array of directories or bool if directory not found
	 *
	 * @param string $dir
	 * @return array|bool
	 * @throws \mahlstrom\Remote\Exceptions\RemoteNListException
	 */
	public function readDir($dir = '.');

	public function rename($oldName, $newName);

	public function rmdir($path);

	public function size($filename);

	public function stat($filename);

	public function close();
}
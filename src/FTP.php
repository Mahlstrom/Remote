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
class FTP extends RemoteHelper implements RemoteInterface
{

    /**
     * Holds the ftp connect resource.
     *
     * @var bool|resource
     */
    public $conn_id = false;
    /**
     * Hostname param is only used for error reporting
     *
     * @var string
     */
    private $hostname = '';
    private $user = '';
    private $password = '';
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
    public function __construct($hostName, $user, $password, $port = 21, $timeout = 10)
    {

        $this->hostname = $hostName;
        $this->user = $user;
        $this->password = $password;
        $this->conn_id = ftp_connect($hostName, $port, $timeout);
        if (!$this->conn_id) {
            unset($this);
            throw new Exceptions\RemoteConnectException($hostName . ' is dead');
        }

        $loginResult = @ftp_login($this->conn_id, $user, $password);
        if (!$loginResult) {
            throw new RemoteLoginException();
        }
        $this->system = ftp_systype($this->conn_id);
        $this->userRoot = $this->pwd();
        if (substr($this->userRoot, -1, 1) != '/') {
            $this->userRoot .= '/';
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function pwd()
    {

        return ftp_pwd($this->conn_id);
    }

    /**
     * Get nlist raw data
     *
     * @param string $remoteDirectory
     * @return array
     */
    public function nlist($remoteDirectory = '.')
    {

        $arr = ftp_nlist($this->conn_id, $remoteDirectory);

        return $arr;
    }

    /**
     * {@inheritDoc}
     */
    public function get($localFile, $remoteFile, $offset = 0)
    {

        $this->checkSoLocalDirExists($localFile);

        return ftp_get($this->conn_id, $localFile, $remoteFile, FTP_BINARY, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($dir, $mode = -1, $recursive = false)
    {

        if (@ftp_mkdir($this->conn_id, $dir)) {
            return $dir;
        }

        return false;
    }

    /**
     * Get the server type (Unix/Windows)
     *
     * @return string
     */
    public function getServerType()
    {

        return $this->system;
    }

    // @codingStandardsIgnoreStart

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function is_connected()
    {

        return $this->isConnected();
    }

    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {

        if ($this->conn_id) {
            return true;
        }

        return false;
    }

    /**
     * At destruction calls close
     *
     * @codeCoverageIgnore
     */
    public function __destruct()
    {

        $this->close();
    }

    /**
     * Close the connection
     *
     * @return bool
     */
    public function close()
    {

        $closed = false;
        if ($this->conn_id) {
            $closed = ftp_close($this->conn_id);
            $this->conn_id = false;
        }

        return $closed;
    }

    /**
     * Upload a file to remote server
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param int $mode
     * @return bool
     */
    public function put($localFile, $remoteFile, $mode = FTP_BINARY)
    {

        if (@ftp_put($this->conn_id, $remoteFile, $localFile, $mode)) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path)
    {

        return ftp_delete($this->conn_id, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function chdir($dir)
    {

        return ftp_chdir($this->conn_id, $dir);
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {

        return ftp_rmdir($this->conn_id, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($mode, $filename)
    {

        return @ftp_chmod($this->conn_id, $mode, $filename);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($oldName, $newName)
    {

        if (@ftp_rename($this->conn_id, $oldName, $newName)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function size($filename)
    {

        $result = ftp_size($this->conn_id, $filename);
        if ($result == -1) {
            return false;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function stat($filename)
    {

        $pathInfo = pathinfo($filename);
        $result = $this->readDir($pathInfo['dirname']);
        if (array_key_exists($filename, $result)) {
            return $result[$filename];
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readDir($path = '.')
    {

        if (!@ftp_chdir($this->conn_id, $path)) {
            return false;
        }
        $rawFiles = $this->rawlist($path);
        if ($rawFiles == false) {
            // @codeCoverageIgnoreStart
            throw new Exceptions\RemoteNListException('"' . $path . '" @ ' . $this->hostname);
            // @codeCoverageIgnoreEnd
        }
        $structure = array();
        $arrayPointer = & $structure;
        switch ($this->system) {
            case 'UNIX':
                foreach ($rawFiles as $rawFile) {
                    if ($rawFile[0] == '/') {
                        // @codeCoverageIgnoreStart
                        $paths = array_slice(explode('/', str_replace(':', '', $rawFile)), 1);
                        $arrayPointer = & $structure;
                        foreach ($paths as $path) {
                            foreach ($arrayPointer as $i => $file) {
                                if ($file['text'] == $path) {
                                    $arrayPointer = & $arrayPointer[$i]['children'];
                                    break;
                                }
                            }
                        }
                        // @codeCoverageIgnoreEnd
                    } elseif (!empty($rawFile)) {
                        $info = preg_split("/[\s]+/", $rawFile, 9);
                        if (count($info) >= 8) {
                            $name = $info[8];
                            $arrayPointer[$name] = new RemoteFile();

                            $arrayPointer[$name]->name = $name;
                            $arrayPointer[$name]->is_dir = $info[0]{0} == 'd';
                            $arrayPointer[$name]->size = $info[4];
                            $arrayPointer[$name]->Hsize = $this->byteConvert($info[4]);
                            $arrayPointer[$name]->mode = $this->chModNum($info[0]);
                            $arrayPointer[$name]->date = $this->fileDateToTime(
                                $info[5] . ' ' . $info[6] . ' ' . $info[7]
                            );
                        }
                    }
                }
                break;
        }

        return $structure;
    }

    /**
     * {@inheritDoc}
     */
    public function rawlist($path = '.')
    {

        return ftp_rawlist($this->conn_id, $path);
    }

    /**
     * @param $dateString
     * @return string
     * @codeCoverageIgnore
     */
    private function fileDateToTime($dateString)
    {

        try {
            $date = new DateTime($dateString);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit(1);
        }
        $now = new DateTime();
        if ($date > $now) {
            $date->sub(new DateInterval('P1Y'));
        }

        return $date->format('U');
    }
}

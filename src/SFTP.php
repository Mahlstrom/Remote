<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 12/05/14
 * Time: 08:18
 */

namespace mahlstrom\Remote;

use mahlstrom\Remote\Exceptions\RemoteConnectException;
use mahlstrom\Remote\Exceptions\RemoteLoginException;
use Net_SFTP;
use PHPUnit_Framework_Error_Notice;

class SFTP extends RemoteHelper implements RemoteInterface
{

    private $sftp = false;

    public function __construct($hostName, $user, $password, $port = 22, $timeout = 10)
    {

        try {
            $this->sftp = new Net_SFTP($hostName, $port, $timeout);
        } catch (PHPUnit_Framework_Error_Notice $e) {
            throw new RemoteConnectException();
        }

        if (!$this->sftp->login($user, $password)) {
            throw new RemoteLoginException;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function nlist($path = '.')
    {

        return $this->sftp->nlist($path);
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir($dir, $mode = -1, $recursive = false)
    {

        if ($this->sftp->mkdir($dir, $mode, $recursive)) {
            return $dir;
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function put($local_file, $remote_file, $mode = 0755, $offset = -1)
    {

        return $this->sftp->put($remote_file, $local_file, NET_SFTP_LOCAL_FILE, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function chdir($dir)
    {

        return $this->sftp->chdir($dir);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path)
    {

        return $this->sftp->delete($path);
    }

    /**
     * {@inheritDoc}
     */
    public function get($local_file, $remote_file, $offset = 0)
    {

        $this->checkSoLocalDirExists($local_file);
        return $this->sftp->get($remote_file, $local_file, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function readDir($dir = '.')
    {

        $data = $this->rawlist($dir);
        if (!$data) {
            return false;
        }
        $ret = array();
        foreach ($data as $filename => $fileData) {
            $ret[$filename] = $this->createRemoteFile($filename, $fileData);
        }
        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function rawlist($dir = '.')
    {

        return $this->sftp->rawlist($dir);
    }

    /**
     * Creates a new RemoteFile from filename and file data
     *
     * @param $filename
     * @param $fileData
     * @return RemoteFile
     */
    private function createRemoteFile($filename, $fileData)
    {

        $dir = new RemoteFile();
        $dir->name = $filename;
        $dir->is_dir = ($fileData['type'] === 2);
        $dir->size = $fileData['size'];
        $dir->mode = substr(decoct($fileData['mode']), -3, 3);
        $dir->date = $fileData['mtime'];

        return $dir;
    }

    /**
     * {@inheritDoc}
     */
    public function pwd()
    {

        return $this->sftp->pwd();
    }

    /**
     * {@inheritDoc}
     */
    public function rmdir($path)
    {

        return $this->sftp->rmdir($path);
    }

    /**
     * {@inheritDoc}
     */
    public function chmod($mode, $filename)
    {

        $result = $this->sftp->chmod($mode, $filename);
        if (is_bool($result)) {
            return $result;
        } else {
            $decOct = decoct($result);
            return octdec(substr($decOct, -3, 3));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename($oldName, $newName)
    {

        return $this->sftp->rename($oldName, $newName);
    }

    /**
     * {@inheritDoc}
     */
    public function size($filename)
    {

        return $this->sftp->size($filename);
    }

    /**
     * {@inheritDoc}
     */
    public function stat($filename)
    {

        $fileData = $this->sftp->stat($filename);
        if ($fileData === false) {
            return false;
        }

        return $this->createRemoteFile($filename, $fileData);
    }

    public function close()
    {

        $this->sftp->disconnect();

        return !$this->isConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {

        if ($this->sftp->isConnected()) {
            return true;
        } else {
            return false;
        }
    }
}

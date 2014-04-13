<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mahlstrom
 * Date: 2013-09-20
 * Time: 14:18
 * To change this template use File | Settings | File Templates.
 */
namespace mahlstrom\Remote;

use DateInterval;
use DateTime;
use mahlstrom\Remote\Exceptions\RemoteConnectException;
use mahlstrom\Remote\Exceptions\RemoteNListException;

class FTP
{
    private $hostname = '';
    private $conn_id = false;
    private $system = false;

    public function is_connected()
    {
        if ($this->conn_id) {
            return true;
        }
        return false;
    }

    public function __construct($hostName, $user, $password, $port=21,$timeout=10)
    {
        $this->hostname = $hostName;
        $this->conn_id = ftp_connect($hostName, $port, $timeout);
        if (!$this->conn_id) {
            unset($this);
            throw new RemoteConnectException($hostName . ' is dead');
        }
        ftp_login($this->conn_id, $user, $password);
        $this->system = ftp_systype($this->conn_id);
        return true;
    }

    public function readDir($path = '.')
    {
        if(!@ftp_chdir($this->conn_id, $path)){
            return false;
        }
        $rawfiles = ftp_rawlist($this->conn_id, $path);
        if ($rawfiles == false) {
            throw new RemoteNListException('"'.$path.'" @ '.$this->hostname);
        }
        $structure = array();
        $arraypointer = & $structure;
        switch ($this->system) {
            case 'UNIX':
                foreach ($rawfiles as $rawfile) {
                    if ($rawfile[0] == '/') {
                        $paths = array_slice(explode('/', str_replace(':', '', $rawfile)), 1);
                        $arraypointer = & $structure;
                        foreach ($paths as $path) {
                            foreach ($arraypointer as $i => $file) {
                                if ($file['text'] == $path) {
                                    $arraypointer = & $arraypointer[$i]['children'];
                                    break;
                                }
                            }
                        }
                    } elseif (!empty($rawfile)) {
                        $info = preg_split("/[\s]+/", $rawfile, 9);
                        $name = $info[8];
                        $arraypointer[$name] = (object)array(
                            'name' => $name,
                            'is_dir' => $info[0]{0} == 'd',
                            'size' => $info[4],
                            'Hsize' => $this->byteConvert($info[4]),
                            'chmod' => $this->chModNum($info[0]),
                            'date' => $this->fileDateToTime($info[5] . ' ' . $info[6] . ' ' . $info[7])
                        );
                    }
                }
                break;
            case 'Windows_NT':
                $i = 0;
                foreach ($rawfiles as $current) {
                    preg_match("/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/", $current, $split);
                    if (is_array($split)) {
                        $parsed = array();
                        if ($split[3] < 70) {
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

    public function close()
    {
        $closed=false;
        if ($this->conn_id) {
            $closed=ftp_close($this->conn_id);
            $this->conn_id = false;
        }
        return $closed;
    }

    public function __destruct()
    {
        $this->close();
    }

    private function byteConvert($bytes)
    {
        if ($bytes) {
            $symbol = array(' B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
            $exp = (int)floor(log($bytes) / log(1024));

            return sprintf('%.2f ' . $symbol[$exp], ($bytes / pow(1024, floor($exp))));
        } else {
            return 0;
        }
    }

    private function chModNum($chmod)
    {
        $trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
        $chmod = substr(strtr($chmod, $trans), 1);
        $array = str_split($chmod, 3);
        return array_sum(str_split($array[0])) . array_sum(str_split($array[1])) . array_sum(str_split($array[2]));
    }

    function fileDateToTime($dateString)
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

    public function quickDir($string)
    {
        $arr = ftp_nlist($this->conn_id, $string);
        return $arr;
    }

    public function get($localFile, $remoteFile)
    {
        $pathInfo = pathinfo($localFile);
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0775, true);
        }
        return ftp_get($this->conn_id, $localFile, $remoteFile, FTP_BINARY);
    }

    #TODO Check how we can test this
    public function mkdir($path)
    {
        return ftp_mkdir($this->conn_id, $path);
    }

    #TODO Check how we can test this
    public function chk_mkdir($path)
    {
        $pathParts = explode('/', $path);
        foreach ($pathParts as $part) {
            if (@!ftp_chdir($this->conn_id, $part)) {
                ftp_mkdir($this->conn_id, $part);
                ftp_chdir($this->conn_id, $part);
            }
        }
    }

    public function put($localFile, $remoteFile)
    {
        #TODO Check how we can test this
        // TODO: Implement put() method.
    }

    public function getServerType()
    {
        return $this->system;
    }
}

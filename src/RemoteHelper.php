<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/05/14
 * Time: 12:01
 */

namespace mahlstrom\Remote;

abstract class RemoteHelper
{

    /**
     * @param $bytes
     * @return int|string
     * @ignore
     */
    protected function byteConvert($bytes)
    {

        if ($bytes) {
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
    protected function chModNum($chmod)
    {

        $trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
        $chmod = substr(strtr($chmod, $trans), 1);
        $array = str_split($chmod, 3);

        return array_sum(str_split($array[0])) . array_sum(str_split($array[1])) . array_sum(str_split($array[2]));
    }

    /**
     * @param $local_file
     */
    protected function checkSoLocalDirExists($local_file)
    {

        $pathInfo = pathinfo($local_file);
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0755, true);
        }
    }
}

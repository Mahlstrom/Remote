<?php

namespace mahlstrom\Remote\Test;

use mahlstrom\Remote\RemoteFile;

/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/05/14
 * Time: 17:14
 */

class RemoteFileTest extends \PHPUnit_Framework_TestCase
{

    public function testRemoteFile()
    {

        $f = new RemoteFile();
        $f->mode = 775;
        $this->assertEquals(775, $f->mode);
    }

    // @codingStandardsIgnoreStart

    /**
     * @expectedException \Exception
     */
    public function testRemoteFileWrongProperty()
    {

        $f = new RemoteFile();
        /** @noinspection PhpUndefinedFieldInspection */
        $f->bode;
    }
    // @codingStandardsIgnoreEnd
}

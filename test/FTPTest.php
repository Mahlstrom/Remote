<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */

namespace mahlstrom\Remote\Test;

use mahlstrom\Remote\FTP;

class FTPTest extends AbstractRemoteTest
{

    /** @var  FTP */
    protected $server;

    public function setUp()
    {

        $this->server = new FTP('localhost', 'test', 'test');
    }

    public function testServerType()
    {

        $this->assertTrue(is_string($this->server->getServerType()));
    }

    /**
     * @expectedException \mahlstrom\Remote\Exceptions\RemoteLoginException
     */
    public function testFailLogin()
    {

        $this->server = new FTP('localhost', '', '');
    }

    /**
     * @expectedException \mahlstrom\Remote\Exceptions\RemoteConnectException
     */
    public function testFailConnection()
    {

        $this->server = new FTP('ftp.funet.fi', '', '', 984, 1);
    }

    public function tearDown()
    {

        $this->server->close();
    }
}

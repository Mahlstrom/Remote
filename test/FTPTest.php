<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */

use mahlstrom\Remote\FTP;

class FTPTest extends PHPUnit_Framework_TestCase
{
    /** @var FTP $ftp */
    private $ftp = false;

    public function setUp()
    {
        $this->ftp = new FTP('ftp.funet.fi', '', '');
    }

    /**
     * @expectedException mahlstrom\Remote\RemoteConnectException
     */
    public function testFailConnection(){
        $this->ftp = new FTP('ftp.funet.fi','','',984,1);
    }
    public function testIfConnected()
    {
        $this->assertTrue($this->ftp->is_connected());
    }
    public function testReadWrongDir(){
        $this->assertFalse($this->ftp->readDir('./Blutti'));
    }

    public function testReadRootDirectory()
    {
        $this->assertArrayHasKey('README', $this->ftp->readDir('.'));
    }

    public function testServerType()
    {
        $this->assertEquals('UNIX', $this->ftp->getServerType());
    }

    public function testQuickDir()
    {
        $this->assertTrue(is_array($this->ftp->nlist('.')));
    }

    public function testGetFile(){
        $this->assertTrue($this->ftp->get('/tmp/figaro/README','README'));
        unlink('/tmp/figaro/README');
        rmdir('/tmp/figaro');
    }

    public function testServerClose()
    {
        $this->assertTrue($this->ftp->close());
    }

    public function testServerIsClosed(){
        $this->ftp->close();
        $this->assertFalse($this->ftp->is_connected());
    }

}
 
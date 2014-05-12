<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */

use mahlstrom\Remote\FTP;

class FTPTest extends PHPUnit_Framework_TestCase {

	private $testDir='1234_test_ahl';
	/** @var FTP $ftp */
	private $ftp = false;

	public function setUp() {

		$this->ftp = new FTP('localhost', 'test', 'test');
	}

	/**
	 * @expectedException mahlstrom\Remote\RemoteConnectException
	 */
	public function testFailConnection() {

		$this->ftp = new FTP('ftp.funet.fi', '', '', 984, 1);
	}

	public function testIfConnected() {

		$this->assertTrue($this->ftp->is_connected());
	}

	public function testReadWrongDir() {

		$this->assertFalse($this->ftp->readDir('./Blutti'));
	}

	public function testServerType() {

		$this->assertEquals('UNIX', $this->ftp->getServerType());
	}

	public function testQuickDir() {

		$this->assertTrue(is_array($this->ftp->nlist('.')));
	}

	public function testPutFile() {

		$this->assertTrue(touch('/tmp/README'));
		$this->assertTrue($this->ftp->put('/tmp/README', 'README'));
	}

	public function testGetFile() {

		$this->assertTrue($this->ftp->get('/tmp/README2', 'README'));
		unlink('/tmp/README2');
	}

	public function testReadRootDirectory() {

		$this->assertArrayHasKey('README', $this->ftp->readDir('.'));
	}

	public function testDeleteFile() {

		$this->assertTrue($this->ftp->delete('README'));
	}

	public function testCreateDirectory() {

		$this->assertEquals($this->testDir,$this->ftp->mkdir($this->testDir));
	}

	public function testChdir() {

		$this->assertTrue($this->ftp->chdir($this->testDir));

	}

	public function testPwd() {
		$this->ftp->chdir($this->testDir);
		$this->assertEquals('/Users/test/'.$this->testDir, $this->ftp->pwd());
	}

	public function testRemoveDirectory(){
		$this->assertTrue($this->ftp->rmdir($this->testDir));
	}

	public function testServerClose() {

		$this->assertTrue($this->ftp->close());
	}

	public function testServerIsClosed() {

		$this->ftp->close();
		$this->assertFalse($this->ftp->is_connected());
	}
}
 
<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */

use mahlstrom\Remote\SFTP;

class SFTPTest extends PHPUnit_Framework_TestCase {

	private $testDir = '1234_test_ahl';
	/** @var SFTP $ftp */
	private $sftp = false;

	public function setUp() {

		$this->sftp = new SFTP('localhost', 'test', 'test');
	}

	public function testIfConnected() {

		$this->assertTrue($this->sftp->is_connected());
	}

//	public function testReadWrongDir() {
//
//		$this->assertFalse($this->sftp->readDir('./Blutti'));
//	}
//
//	public function testServerType() {
//
//		$this->assertEquals('UNIX', $this->sftp->getServerType());
//	}
//
	public function testQuickDir() {

		$this->assertTrue(is_array($this->sftp->nlist('.')));
	}

	public function testPutFile() {

		$this->assertTrue(touch('/tmp/README'));
		$this->assertTrue($this->sftp->put('README','/tmp/README'));
	}

//	public function testReadRootDirectory() {
//
//		$this->assertArrayHasKey('README', $this->sftp->readDir('.'));
//	}
//
	public function testGetFile() {
		$this->assertTrue($this->sftp->get('README','/tmp/README2'));
		unlink('/tmp/README2');
	}

	public function testDeleteFile() {

		$this->assertTrue($this->sftp->delete('README'));
	}

	public function testCreateDirectory() {

		$this->assertEquals($this->testDir, $this->sftp->mkdir($this->testDir));
	}

	public function testChdir() {

		$this->assertTrue($this->sftp->chdir($this->testDir));
	}

	public function testPwd() {
		$this->sftp->chdir($this->testDir);
		$this->assertEquals('/Users/test/' . $this->testDir, $this->sftp->pwd());
	}

	public function testRemoveDirectory() {
		$this->assertTrue($this->sftp->rmdir($this->testDir));
	}

//	public function testServerClose() {
//
//		$this->assertTrue($this->sftp->close());
//	}

//	public function testServerIsClosed() {
//
//		$this->sftp->close();
//		$this->assertFalse($this->sftp->is_connected());
//	}
}
 
<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 12/05/14
 * Time: 21:33
 */
namespace mahlstrom\Remote\Test;


use mahlstrom\Remote\remoteInterface;

abstract class abstractRemoteTest extends \PHPUnit_Framework_TestCase{
	protected $testDir='1234_test_ahl';
	/** @var RemoteInterface */
	protected $server=false;

	public function testIfConnected() {

		$this->assertTrue($this->server->isConnected());
	}

	public function testCreateDirectory() {

		$this->assertEquals($this->testDir,$this->server->mkdir($this->testDir));
	}

	public function testCreateFailedDirectory() {

		$this->assertFalse($this->server->mkdir($this->testDir.'/et/ea'));
	}

	public function testQuickDir() {

		$this->assertContains($this->testDir,$this->server->nlist('.'));

	}

	public function testSetStatOnFile(){
		touch('/tmp/README');
		$this->server->put('/tmp/README','chmodfile');
		$result= $this->server->stat('chmodfile');
		$this->assertEquals(644,$result->mode,'Mode before change');
		$this->assertEquals(0666,$this->server->chmod(0666,'chmodfile'),'CHange of moode');
		$result= $this->server->stat('chmodfile');
		$this->assertEquals(666,$result->mode,'Mode after change');
		$this->server->delete('chmodfile');
		$this->assertFalse($this->server->chmod(0666,'chmodfile'));
		$this->assertFalse( $this->server->stat('chmodfileq'));
	}


	public function testChdir() {

		$this->assertTrue($this->server->chdir($this->testDir));

	}

	public function testPwd() {
		$this->server->chdir($this->testDir);
		$this->assertEquals('/Users/test/'.$this->testDir, $this->server->pwd());
	}

	public function testRemoveDirectory(){
		$this->assertTrue($this->server->rmdir($this->testDir));
	}

	public function testPutFile() {
		$this->assertTrue(touch('/tmp/README'));
		$this->assertTrue($this->server->put('/tmp/README', 'README'));
		unlink('/tmp/README');
	}

	/**
	 * @depends testPutFile
	 */
	public function testGetFile() {

		$this->assertTrue($this->server->get('/tmp/test/README2', 'README'));
		unlink('/tmp/test/README2');
		rmdir('/tmp/test');
	}

	/**
	 * @depends testPutFile
	 */
	public function testDeleteFile() {

		$this->assertTrue($this->server->delete('README'));
	}

	public function testReadRootDirectory() {

		$this->assertTrue(is_array($this->server->readDir('.')));
	}

	public function testReadWrongDir() {

		$this->assertFalse($this->server->readDir('./Blutti'));
	}
	public function testRenameFileOnServer(){
		touch('/tmp/README');
		$this->assertTrue($this->server->put('/tmp/README','_rename_this_'));
		$this->assertTrue($this->server->rename('_rename_this_','_rename_this2_'));
		$this->assertTrue($this->server->rename('_rename_this2_','_rename_this_'));
		$this->assertFalse($this->server->rename('_rename_this2_','_rename_this_'));
		$this->assertTrue($this->server->delete('_rename_this_'));
	}

	public function testFileSize(){
		$this->assertEquals(12, $this->server->size('BBlit'));
	}

	public function testFileSizeOnNoExistingFile(){
		$this->assertFalse($this->server->size('BBlit123__'));
	}

	public function testCloseAndIsConnectedIsFalse(){
		$this->assertTrue($this->server->close());
		$this->assertFalse( $this->server->isConnected());
	}

	public function tearDown(){
		if($this->server){
		$this->server->delete('chmodfile');
		}
		parent::tearDown();
	}
}
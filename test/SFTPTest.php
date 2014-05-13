<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/04/14
 * Time: 20:26
 */

namespace mahlstrom\Remote\Test;

use mahlstrom\Remote\SFTP;

class SFTPTest extends abstractRemoteTest {

	/** @var  SFTP */
	protected $server;

	public function setUp() {

		$this->server = new SFTP('localhost', 'test', 'test');
	}

	/**
	 * @expectedException \mahlstrom\Remote\Exceptions\RemoteLoginException
	 */
	public function testFailLogin() {

		$this->server = new SFTP('localhost', '', '');
	}

	/**
	 * @expectedException \mahlstrom\Remote\Exceptions\RemoteConnectException
	 */
	public function testFailConnection() {

		$this->server = new SFTP('localhost', 'test', 'test', 15151);
		$this->assertFalse($this->server->isConnected());
	}

}
 
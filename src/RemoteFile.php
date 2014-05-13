<?php
/**
 * Created by PhpStorm.
 * User: mahlstrom
 * Date: 13/05/14
 * Time: 15:28
 */

namespace mahlstrom\Remote;

/**
 * Class RemoteFile
 *
 * @property string $name;
 * @property bool $is_dir;
 * @property int $size;
 * @property int $mode;
 * @property int $date;
 * @package mahlstrom\Remote
 */
class RemoteFile {

	private $name;
	private $is_dir;
	private $size;
	private $mode;
	private $date;

	public function __set($key, $val) {

		switch($key) {
			case 'size':
				$this->size = (int)$val;
				break;
			case 'mode':
				$this->mode = (int)$val;
				break;
			case 'date':
				$this->date = $this->_normalize_date($val);
				break;

			case 'name':
				$this->name = (string)$val;
				break;
			case 'is_dir':
				$this->is_dir = (bool)$val;
		}
	}

	public function __get($key) {

		if(array_key_exists($key, get_object_vars($this))) {
			return $this->$key;
		}
		throw new \Exception('Property ' . $key . ' does not exist');
	}

	private function _normalize_date($data) {

		if(is_integer($data)) {
			$b = new \DateTime();
			$b->setTimestamp($data);
			$hour = (int)$b->format('H');
			$minute = (int)$b->format('i');
			$b->setTime($hour, $minute, 0);
			$data = $b->getTimestamp();
		}

		return (int)$data;
	}
}
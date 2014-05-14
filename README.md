# Remote what ?

I have for a time been working with FTP and SFTP and was frustrated that I needed to remember
the differences between them.
So I built this (With help from [phpseclib](https://github.com/phpseclib/phpseclib)).

It is object oriented, Unittested with 100% code coverage.

These are the methods implemented so far.

	public function chdir($directory);
	public function chmod($mode, $filename);
	public function close();
	public function delete($path);
	public function get($remote_file, $local_file);
	public function put($local_file, $remote_file);
	public function isConnected();
	public function mkdir($dir, $recursive = false);
	public function nlist($dir = '.');
	public function pwd();
	public function rawlist($dir = '.');
	public function readDir($dir = '.');
	public function rename($oldName, $newName);
	public function rmdir($path);
	public function size($filename);
	public function stat($filename);

As soon as I am happy with what I have built I will put the code up on [packagist.org](http://packagist.org)

#Installation
I am using composer and have not built my own autoload so it is dependent on composer right now.

In composer.json add

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Mahlstrom/Remote.git"
        }
    ]
}
```

and

```json
{
    "require": {
        "mahlstrom/remote": "dev-master"
    }
}
```

That's all folks!

Good luck, have fun!
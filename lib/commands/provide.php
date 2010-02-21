<?php
/**
 * Provide
 *
 * @package Sprockets
 *
 * @subpackage commands
 */
class SprocketsCommandProvide extends SprocketsCommand {

	/**
	 * Command Exec
	 *
	 * @param string $param
	 * @param string $context
	 *
	 * @return void
	 */
	public function exec($param, $context) {
		preg_match('/\"([^\"]+)\"/', $param, $match);
		foreach(glob($context.'/'.$match[1].'/*') as $asset) {
			shell_exec('cp -r '.realpath($asset).' '.realpath($this->Sprockets->assetFolder));
		}
	}
}

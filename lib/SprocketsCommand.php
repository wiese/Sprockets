<?php
/**
 * SprocketsCommand Class
 *
 * @package Sprockets
 * @subpackage lib
 *
 * @author Kjell Bublitz
 */
class SprocketsCommand {

	public $Sprockets;	// Sprocket Object

	const DEFAULT_DIRECTORY = 'commands';
	const CLASS_NAME_PREFIX = 'SprocketsCommand';
	const FILE_EXTENSION = '.php';

	/**
	 * Command Constructor
	 *
	 * @param Sprockets $Sprockets
	 *
	 * @return void
	 */
	public function __construct(Sprockets & $Sprockets) {
		$this->Sprockets = $Sprockets;
	}

	/**
	 * Get the file name of the file
	 *
	 * @param string $context
	 * @param string $param
	 *
	 * @return string
	 */
	public function getFileName($context, $param) {
		return basename(
			$context . DIRECTORY_SEPARATOR . $param . '.' . $this->Sprockets->fileExt
		);
	}

	/**
	 * Get the file context
	 *
	 * @param string $context
	 * @param string $param
	 *
	 * @return string
	 */
	public function getFileContext($context, $param) {
		return dirname(
			$context . DIRECTORY_SEPARATOR . $param . '.' . $this->Sprockets->fileExt
		);
	}

	/**
	 * Get the class name of a sprockets command
	 *
	 * @param string $command Command name
	 *
	 * @return string
	 */
	public static function getClassName($command) {
		return self::CLASS_NAME_PREFIX . ucfirst($command);
	}

	/**
	 * Get the file name of the sprockets command with the given name
	 *
	 * @param string $command
	 *
	 * @return string
	 */
	public static function getCommandFileName($command) {
		return $command . self::FILE_EXTENSION;
	}

	/**
	 * Add default commands folder to include path
	 *
	 * @return void
	 */
	public static function provideDefaultCommands() {
		ini_set(	// current include path + default commands folder
			'include_path',
			get_include_path() .
			PATH_SEPARATOR .
			dirname(__FILE__) . DIRECTORY_SEPARATOR . self::DEFAULT_DIRECTORY
		);
	}

	/**
	 * Add default custom command folder to include path
	 *
	 * @param string $path
	 *
	 * @return void
	 */
	public static function provideCustomCommands($path) {
		if (!is_null($path)) {
			$realPath = realpath($path);
			if ($realPath && is_dir($realPaths)) {

				ini_set(	// current include path + custom commands folder
					'include_path',
					get_include_path() . PATH_SEPARATOR .	$realPath
				);
			}
			else {
				throw new SprocketsUnknownCustomCommandsPathException($path);
			}
		}
	}
}

/**
 * Exception thrown when trying to set non-existing custom commands path
 */
class SprocketsUnknownCustomCommandsPathException extends Exception {}

<?php
require_once dirname(__FILE__).'/SprocketsCommand.php';

// CSS Minify
define('MINIFY_CSS', 'cssmin-v1.0.1.b3.php');
// JS Minify
define('MINIFY_JS', 'jsmin-1.1.1.php');

/**
 * Sprockets Class
 *
 * @license Full credit goes to Stuart Loxton & Kjell Bublitz
 *
 * @package Sprockets
 *
 * @author Stuart Loxton (http://github.com/stuartloxton/php-sprockets)
 * @author Kjell Bublitz (http://github.com/m3nt0r/php-sprockets)
 * @author wiese
 */
class Sprockets
{
	// Base URI - Path to webroot
	public $baseUri = '';

	// Base JS - Relative location of the javascript folder
	public $baseFolder = '';

	// File Path - Current file to parse
	public $filePath = '';

	// File Extension
	public $fileExt = 'js';

	// Assets - Relative location of the assets folder
	public $assetFolder = '';

	// Debug Option
	public $debugMode = false;

	// Source Content Type
	public $contentType = 'application/x-javascript';

	// JS Source - Current Source
	protected $_parsedSource = '';

	// Scanned Const files
	protected $_constantsScanned = array();

	// Constants keys and values
	protected $_constants = array();

	// Source comes from cache
	protected $_fromCache = false;

	// Commands already loaded and instantiated (done only once per command)
	protected $_requiredCommands = array();

	protected $_currentScope;

	protected $_autoRender = true;

	/**
	 * Constructor
	 *
	 * @param string $file    Javascript file to use
	 * @param array  $options Sprockets settings
	 *
	 * @return void
	 */
	public function __construct($file, array $options = array()) {

		$options = array_merge(array(
			'baseUri' => '/php-sprockets',
			'baseFolder' => '/js',
			'assetFolder' => '..',
			'debugMode' => false,
			'autoRender' => false,
			'contentType' => 'application/x-javascript',
			'customCommandsPath' => null
		), $options);

		extract($options, EXTR_OVERWRITE);

		$this->setBaseUri($baseUri);
		$this->setBaseFolder($baseFolder);
		$this->setAssetFolder($assetFolder);
		$this->setDebugMode($debugMode);
		$this->setContentType($contentType);
		$this->setAutoRender($autoRender);

		$this->setFilePath($file);

		SprocketsCommand::provideDefaultCommands();
		SprocketsCommand::provideCustomCommands($customCommandsPath);

		if ($this->_autoRender) {
			$this->render();
		}
	}

	/**
	 * Assign the current file to parse.
	 *
	 * @param string $filePath Full Path to the JS file
	 *
	 * @return self
	 */
	public function setFilePath($filePath) {
		$this->filePath = str_replace($this->baseUri, '..', $filePath);
		$this->fileExt = array_pop(explode('.', $this->filePath));
		return $this;
	}

	/**
	 * Enable or Disable the debug mode.
	 * Debug mode prevents file caching.
	 *
	 * @param boolean $enabled
	 *
	 * @return self
	 */
	public function setDebugMode($enabled = true) {
		$this->debugMode = $enabled;
		return $this;
	}

	/**
	 * Set assetFolder
	 *
	 * @param string $assetFolder
	 *
	 * @return self
	 */
	public function setAssetFolder($assetFolder) {
		$this->assetFolder = $assetFolder;
		return $this;
	}

	/**
	 * Set baseFolder
	 *
	 * @param string $baseFolder
	 *
	 * @return self
	 */
	public function setBaseFolder($baseFolder) {
		$this->baseFolder = $baseFolder;
		return $this;
	}

	/**
	 * Set baseUri
	 *
	 * @param string $baseUri
	 *
	 * @return self
	 */
	public function setBaseUri($baseUri) {
		$this->baseUri = $baseUri;
		return $this;
	}

	/**
	 * Set contentType
	 *
	 * @param string $baseUri
	 *
	 * @return self
	 */
	public function setContentType($contentType) {
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * Set if Sprockets should auto-render
	 *
	 * @param bool $autoRender
	 *
	 * @return self
	 */
	public function setAutoRender($autoRender) {
		$this->_autoRender = (bool)$autoRender;
		return $this;
	}

	/**
	 * Get the current scope
	 *
	 * @return string
	 */
	public function getCurrentScope() {
		return $this->_currentScope;
	}

	/**
	 * Return rendered version of current js file
	 *
	 * @param bool $return Should the render result be returned instead of echoed?
	 *
	 * @return mixed void|string
	 */
	public function render($return = false) {
		if (!$this->debugMode) {
			if ($this->isCached()) {
				$this->_parsedSource = $this->readCache();
				$this->_fromCache = true;
			}
		}

		if (!$this->_fromCache) {
			$file = basename($this->filePath);
			$context = dirname($this->filePath);

			$this->_parsedSource = $this->parseFile($file, $context);
		}

		if (!$this->debugMode && !$this->_fromCache) {
			file_put_contents($this->filePath.'.cache', $this->_parsedSource);
		}

		if (!$return) {
			if ($this->contentType) {
				header ("Content-Type: {$this->contentType}");
			}
			echo $this->_parsedSource;
		}
		else {
			return $this->_parsedSource;
		}
	}

	/**
	 * Parse JS File
	 *
	 * - read and replace constants
	 * - parse and execute commands
	 * - strip comments
	 *
	 * @param string $file    Filepath
	 * @param string $context Directory
	 *
	 * @return string Sprocketized Source
	 */
	public function parseFile($file, $context) {

		if (!is_file(realpath($this->filePath))) {
			$this->fileNotFound($this->filePath);
		}

		$this->_currentScope = realpath($context . DIRECTORY_SEPARATOR . $file);

		if ($this->_currentScope === false) {
			$this->fileNotFound($context . DIRECTORY_SEPARATOR . $file);
		}

		$source = file_get_contents($this->_currentScope);

		// Parse Commands
		preg_match_all('/\/\/= ([a-z]+) ([^\n]+)/', $source, $matches);
		foreach($matches[0] as $key => $match) {
			$commandRaw = $matches[0][$key];
			$commandName = $matches[1][$key];

			if ($this->commandExists($commandName)) {
				$param = trim($matches[2][$key]);
				$command = $this->requireCommand($commandName);
				$commandResult = $command->exec($param, $context);
				if (is_string($commandResult)) {
					$source = str_replace($commandRaw, $commandResult, $source);
				}
			}
		}

		// Parse Constants
		$constFile = $context.'/'.str_replace(basename($file), '', $file). 'constants.ini';
		if (is_file($constFile)) {
			if(!isset($this->_constantsScanned[$constFile])) {
				$this->parseConstants($constFile);
			}
			if (count($this->_constants)) {
				$source = $this->replaceConstants($source);
			}
		}

		$source = $this->stripComments($source);

		return $source;
	}

	/**
	 * Parse constants.ini.
	 *
	 * Compared to original Sprockets i don't use YML.
	 * Why make things complicated?
	 *
	 * @param string $file Path to INI File
	 */
	protected function parseConstants($file) {
		$this->_constants = parse_ini_file($file);
		$this->_constantsScanned[$file] = true;
	}

	/**
	 * Replace Constant Tags in Source with values from constants file
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	protected function replaceConstants($source) {
		preg_match_all('/\<(\%|\?)\=\s*([^\s|\%|\?]+)\s*(\?|\%)\>/', $source, $matches);

		foreach($matches[0] as $key => $replace) {
			$source = str_replace($replace, $this->_constants[$matches[2][$key]], $source);
		}
		return $source;
	}

	/**
	 * Remove obsolete comments
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	protected function stripComments($source) {
		return preg_replace('/\/\/([^\n]+)/', '', $source);
	}

	/**
	 * Check if a command with the given name exists
	 *
	 * @param string $command
	 *
	 * @return bool
	 */
	protected function commandExists($command) {

		// class already defined (maybe because it was used earlier)
		if (class_exists(SprocketsCommand::getClassName($command))) {
			return true;
		}

		$commandFile = SprocketsCommand::getCommandFileName($command);
		$includePaths = explode(PATH_SEPARATOR, get_include_path());
		$exists = false;
		// check all include paths for existance of the command
		foreach ($includePaths AS $includePath) {
			if (is_file($includePath . DIRECTORY_SEPARATOR . $commandFile)) {
				$exists = true;
				break;
			}
		}

		return $exists;
	}

	/**
	 * Require and instantiate the command class.
	 *
	 * @example $this->requireCommand('require');
	 *
	 * @param string $command Name of the command
	 *
	 * @return SprocketsCommand
	 */
	protected function requireCommand($command) {
		if (!isset($this->_requiredCommands[$command])) {
			$commandClass = SprocketsCommand::getClassName($command);

			if (!class_exists($commandClass)) {
				require_once SprocketsCommand::getCommandFileName($command);
			}

			$commandObject = new $commandClass($this);
			$this->_requiredCommands[$command] = $commandObject;
			return $commandObject;
		}
		else {
			return $this->_requiredCommands[$command];
		}
	}

	/**
	 * Check if a cached version exists
	 *
	 * @return boolean
	 */
	protected function isCached() {
		return is_file($this->filePath.'.cache');
	}

	/**
	 * Read the cached version from filesystem
	 *
	 * @return string
	 */
	protected function readCache() {
		return file_get_contents($this->filePath.'.cache');
	}

	/**
	 * Write current parsedSource to filesystem (.cache file)
	 *
	 * @return boolean
	 */
	protected function writeCache() {
		return file_put_contents($this->filePath.'.cache', $this->_parsedSource);
	}

	/**
	 * File Not Found - Sends a 404 Header if the file does not exist.
	 * Just overwrite this if you want to do something else.
	 *
	 * @return void
	 */
	protected function fileNotFound($file) {
		if ($this->_autoRender) {
			header("HTTP/1.0 404 Not Found");
			echo '<h1>404 - File Not Found</h1>';
		}
		throw new SprocketsFileNotFoundException($file);
		exit;
	}
}

/**
 * Exception thrown when trying to read a non-existing file
 */
class SprocketsFileNotFoundException extends Exception {}

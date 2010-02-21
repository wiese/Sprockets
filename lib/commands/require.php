<?php
/**
 * Require
 *
 * @package Sprockets
 *
 * @subpackage commands
 */
class SprocketsCommandRequire extends SprocketsCommand {

	/**
	 * Command Exec
	 *
	 * @param string $param
	 * @param string $context
	 *
	 * @return string Parsed file source
	 */
	public function exec($param, $context) {
		$source = '';

		// parse require params
		if (preg_match('/\"([^\"]+)\" ([^\n]+)|\"([^\"]+)\"/', $param, $match)) { // "param"
			if (count($match) == 3) {
				$paramArg = $match[1];
				$optionArg = $match[2];
			}
			if (count($match) == 4) {
				$paramArg = $match[3];
			}

			$fileName = $this->getFileName($context, $paramArg);
			$fileContext = $this->getFileContext($context, $paramArg);

			if (	// avoid self-require
				$this->_getFilePathFromContextAndCommandParam($fileContext, $fileName)
				!=
				$this->Sprockets->getCurrentScope()
			) {
				$source = $this->Sprockets->parseFile($fileName, $fileContext);
			}

			// apply file options
			if (!empty($source) && isset($optionArg)) {
				$fileOptions = array_map('trim', explode(',', $optionArg));
				foreach ($fileOptions as $option) {
					$optionMethod = 'option'.ucfirst($option);
					$source = $this->{$optionMethod}($source, $fileContext, $fileName);
				}
			}
		} else if(preg_match('/\<([^\>]+)\>/', $param, $match)) { // <param>
			$fileName = $this->getFileName($context, $match[1]);
			$fileContext = $this->Sprockets->baseFolder;
			$source = $this->Sprockets->parseFile($fileName, $fileContext);
		}
		return $source;
	}

	/**
	 * Apply minification if possible
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	public function optionMinify($source, $context = null, $filename = null) {
		if ($this->Sprockets->fileExt == 'css') {
			if (!class_exists('cssmin')) {
				require_once realpath(dirname(__FILE__).'/../third-party/'.MINIFY_CSS);
			}
			$source = cssmin::minify($source, "preserve-urls");
		}

		if ($this->Sprockets->fileExt == 'js') {
			if (!class_exists('JSMin')) {
				require_once realpath(dirname(__FILE__).'/../third-party/'.MINIFY_JS);
			}
			$source = JSMin::minify($source);
		}
		return $source;
	}

	protected function _getFilePathFromContextAndCommandParam($context, $param) {
		$contextPlusParam = $context .	'/'. str_replace(array('"','<','>'), '', $param);
		$fileDir = substr($contextPlusParam,	0, strrpos($contextPlusParam, '/'));
		$fileName = substr($contextPlusParam,	strrpos($contextPlusParam, '/')+1);

		return realpath($fileDir) . '/' . $fileName;
	}
}

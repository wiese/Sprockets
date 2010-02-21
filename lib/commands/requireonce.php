<?php
require_once dirname(__FILE__).'/require.php';

/**
 * Requireonce
 *
 * @package Sprockets
 *
 * @subpackage commands
 */
class SprocketsCommandRequireonce extends SprocketsCommandRequire {

	protected $_alreadyRequiredFiles = array();

	/**
	 * Command Exec
	 *
	 * @param string $param
	 * @param string $context
	 *
	 * @return string Parsed file source
	 */
	public function exec($param, $context) {

		$filePath = $this->_getFilePathFromContextAndCommandParam($context, $param);

		if (!in_array($filePath, $this->_alreadyRequiredFiles)) {
			$this->_alreadyRequiredFiles[] = $filePath;
			return parent::exec($param, $context);
		}

		return '';
	}
}

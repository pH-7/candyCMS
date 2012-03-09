<?php

/**
 * PHP unit tests.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 *
 */

require_once PATH_STANDARD . '/app/controllers/Main.controller.php';
require_once PATH_STANDARD . '/app/models/Main.model.php';

abstract class CandyWebTest extends WebTestCase {

	public $oObject;

	public $aRequest;
	public $aSession;
	public $aFile;
	public $aCookie;

  public $iLastInsertId;

	function __construct() {
		parent::__construct();

		$this->aRequest	= array('section' => 'blog', 'clearcache' => 'true');
		$this->aFile			= array();
		$this->aCookie		= array();
		$this->aSession['userdata'] = array(
				'email' => '',
				'facebook_id' => '',
				'id' => 0,
				'name' => '',
				'surname' => '',
				'password' => '',
				'role' => 0,
				'full_name' => ''
		);
	}

	function createFile($sPath) {
		$sFile = PATH_STANDARD . '/' . $sPath . '/test_generated.log';
		$oFile = fopen($sFile, 'a');
		fwrite($oFile, 'Is writeable.' . "\n");
		fclose($oFile);

		return $sFile;
	}

	function removeFile($sPath) {
		return unlink(PATH_STANDARD . '/' . $sPath . '/test_generated.log');
	}
}
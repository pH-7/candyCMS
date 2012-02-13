<?php

/**
 * PHP unit tests
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 */

require_once('lib/simpletest/autorun.php');
require_once('app/controllers/Media.controller.php');

use \CandyCMS\Controller\Media as Media;
use \CandyCMS\Helper\I18n as I18n;

class TestOfMediaController extends WebTestCase {

  public $oMedia;

  function testConstructor() {

    $aRequest = array('section' => 'media');
    $aFile    = array();
    $aCookie  = array();
    $aSession['userdata'] = array(
      'email' => '',
      'facebook_id' => '',
      'id' => 0,
      'name' => '',
      'surname' => '',
      'password' => '',
      'role' => 0,
      'full_name' => ''
    );

    $this->oMedia = new Media($aRequest, $aSession, $aFile, $aCookie);
  }

  function testShow() {
    $this->assertTrue($this->get(WEBSITE_URL . '/media'));
    $this->assertText(I18n::get('lang.error.missing.permission')); # user has not enough permission
    $this->assertResponse('200');
  }

  function testCreate() {
    $this->assertTrue($this->get(WEBSITE_URL . '/media/create'));
    $this->assertText(I18n::get('lang.error.missing.permission')); # user has not enough permission
    $this->assertResponse('200');
  }

  function testUpdate() {
    $this->assertTrue($this->get(WEBSITE_URL . '/media/update'));
    $this->assertText(I18n::get('lang.error.missing.permission')); # user has not enough permission
    $this->assertResponse('200');
  }

  function testDestroy() {
    $this->assertTrue($this->get(WEBSITE_URL . '/media/destroy'));
    $this->assertText(I18n::get('lang.error.missing.permission')); # user has not enough permission
    $this->assertResponse('200');
  }

  function testDirIsWritable() {
    $oFile = fopen('upload/media/test.log', 'a');
    fwrite($oFile, 'Is writeable.' . "\n");
    fclose($oFile);

    $this->assertTrue(file_exists('upload/media/test.log'), 'File was created.');
    @unlink('upload/media/test.log');
  }
}
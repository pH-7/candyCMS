<?php

/**
 * Show blog headlines.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.5
 *
 */

namespace CandyCMS\Plugin\Controller;

use CandyCMS\Core\Helper\Helper as Helper;
use CandyCMS\Core\Helper\SmartySingleton as SmartySingleton;

final class Headlines {
  /**
   * Identifier for Template Replacements
   *
   * @var constant
   *
   */

  const IDENTIFIER = 'headlines';

  /**
   * Show the (cached) headlines.
   *
   * @access public
   * @param array $aRequest
   * @param array $aSession
   * @return string HTML
   *
   */
  public final function show(&$aRequest, &$aSession) {
    $sTemplateDir = Helper::getPluginTemplateDir('headlines', 'show');
    $sTemplateFile = Helper::getTemplateType($sTemplateDir, 'show');

    $oSmarty = SmartySingleton::getInstance();
    $oSmarty->setTemplateDir($sTemplateDir);
    $oSmarty->setCaching(SmartySingleton::CACHING_LIFETIME_SAVED);

    $sCacheId = WEBSITE_MODE . '|blogs|' . WEBSITE_LOCALE . '|headlines';
    if (!$oSmarty->isCached($sTemplateFile, $sCacheId)) {
      $sBlogsModel = \CandyCMS\Core\Model\Main::__autoload('Blogs');
      $oModel = & new $sBlogsModel($aRequest, $aSession);

      $oSmarty->assign('data', $oModel->getData('', false, PLUGIN_HEADLINES_LIMIT));
    }

    return $oSmarty->fetch($sTemplateFile, $sCacheId);
  }
}
<?php

/**
 * Print out sitemap as HTML or XML.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 *
 */

namespace CandyCMS\Core\Controllers;

use CandyCMS\Core\Helpers\Helper;

class Sitemaps extends Main {

  /**
   * Show the sitemap as XML.
   *
   * @access protected
   * @return string XML content
   *
   */
  protected function _showXML() {
    $sTemplateDir   = Helper::getTemplateDir($this->_aRequest['controller'], 'xml');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'xml');

    Header('Content-Type: text/xml');

    if (!$this->oSmarty->isCached($sTemplateFile, UNIQUE_ID)) {
      $this->oSmarty->assign('_website_landing_page_', WEBSITE_URL . '/' . WEBSITE_LANDING_PAGE);
      $this->_getSitemap();
    }

    $this->oSmarty->setTemplateDir($sTemplateDir);
    exit($this->oSmarty->display($sTemplateFile, UNIQUE_ID));
  }

  /**
   * Show the sitemap as HTML.
   *
   * @access protected
   * @return string HTML content
   *
   */
  protected function _show() {
    $sTemplateDir   = Helper::getTemplateDir($this->_aRequest['controller'], 'show');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'show');

    if (!$this->oSmarty->isCached($sTemplateFile, UNIQUE_ID))
      $this->_getSitemap();

    $this->oSmarty->setTemplateDir($sTemplateDir);
    return $this->oSmarty->fetch($sTemplateFile, UNIQUE_ID);
  }

  /**
   * Generate the sitemap. Query tables and build structure.
   *
   * @access private
   *
   */
  private function _getSitemap() {
    $sModel     = $this->__autoload('Blogs', true);
    $oBlogs     = new $sModel($this->_aRequest, $this->_aSession);

    $sModel     = $this->__autoload('Contents', true);
    $oContents  = new $sModel($this->_aRequest, $this->_aSession);

    $sModel     = $this->__autoload('Galleries', true);
    $oGalleries = new $sModel($this->_aRequest, $this->_aSession);

    $this->oSmarty->assign('blogs', $oBlogs->getData('', false, 1000));
    $this->oSmarty->assign('contents', $oContents->getData('', false, 1000));
    $this->oSmarty->assign('galleries', $oGalleries->getData('', false, false, 1000));
  }

  /**
   * There is no create action for the sitemaps controller
   *
   * @access public
   *
   */
  public function create() {
    return Helper::redirectTo('/errors/404');
  }

  /**
   * There is no update action for the sitemaps controller
   *
   * @access public
   *
   */
  public function update() {
    return Helper::redirectTo('/errors/404');
  }

  /**
   * There is no destroy action for the sitemaps controller
   *
   * @access public
   *
   */
  public function destroy() {
    return Helper::redirectTo('/errors/404');
  }
}
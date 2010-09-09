<?php

/*
 * This software is licensed under GPL <http://www.gnu.org/licenses/gpl.html>.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
*/

require_once 'app/models/Session.model.php';

class Session extends Main {
  #protected $_aRequest;
  #protected $_aSession;

  public function __init() {
    $this->_oModel = new Model_Session($this->_aRequest, $this->_aSession);
  }

  # @ Override
  public final function create() {
		if( isset($this->_aRequest['create_session']) )
			return $this->_create();
		else
			return $this->showCreateSessionTemplate();
	}

	private final function _create() {
		if(	!isset($this->_aRequest['email']) || empty($this->_aRequest['email']) )
			$this->_aError['email'] = LANG_ERROR_FORM_MISSING_EMAIL;

    if (Helper::checkEmailAddress($this->_aRequest['email']) == false)
      $this->_aError['email'] = LANG_ERROR_GLOBAL_WRONG_EMAIL_FORMAT;

		if(	!isset($this->_aRequest['password']) || empty($this->_aRequest['password']) )
			$this->_aError['password'] = LANG_ERROR_FORM_MISSING_PASSWORD;

		if (isset($this->_aError))
      return $this->showCreateSessionTemplate();

		elseif( $this->_oModel->create() === true )
			return Helper::successMessage(LANG_SESSION_CREATE_SUCCESSFUL, '/Start');

		else
			return Helper::errorMessage(LANG_ERROR_SESSION_CREATE, LANG_ERROR_GLOBAL_CREATE_SESSION_FIRST).
				$this->showCreateSessionTemplate();
	}

  public final function showCreateSessionTemplate() {
    $oSmarty = new Smarty();

    if (!empty($this->_aError)) {
      foreach ($this->_aError as $sField => $sMessage)
        $oSmarty->assign('error_' . $sField, $sMessage);
    }

    $oSmarty->assign('lang_email', LANG_GLOBAL_EMAIL);
    $oSmarty->assign('lang_login', LANG_GLOBAL_LOGIN);
    $oSmarty->assign('lang_lost_password', LANG_SESSION_PASSWORD_TITLE);
    $oSmarty->assign('lang_password', LANG_GLOBAL_PASSWORD);
    $oSmarty->assign('lang_resend_verification', LANG_SESSION_VERIFICATION_TITLE);

    $oSmarty->template_dir = Helper::getTemplateDir('sessions/createSession');
    return $oSmarty->fetch('sessions/createSession.tpl');
  }

  public final function createResendActions() {
    if (isset($this->_aRequest['email'])) {
      if (isset($this->_aRequest['email']) && ( Helper::checkEmailAddress($this->_aRequest['email']) == false ))
        $this->_aError['email'] = LANG_ERROR_GLOBAL_WRONG_EMAIL_FORMAT;

      if (!isset($this->_aRequest['email']) || empty($this->_aRequest['email']))
        $this->_aError['email'] = LANG_ERROR_FORM_MISSING_EMAIL;

      if (isset($this->_aError))
        return $this->_showCreateResendActionsTemplate();

      else {
        if ($this->_aRequest['action'] == 'resendpassword') {
          $sNewPasswordClean	= Helper::createRandomChar(10);
          $sNewPasswordSecure = md5(RANDOM_HASH . $sNewPasswordClean);

          if($this->_oModel->createResendActions($sNewPasswordSecure) === true) {
            $aData = $this->_oModel->getData();

            $sContent = str_replace('%u', $aData['name'], LANG_MAIL_SESSION_PASSWORD_BODY);
            $sContent = str_replace('%p',$sNewPasswordClean, $sContent);

            $bStatus = Mail::send(Helper::formatInput($this->_aRequest['email']),
                            LANG_MAIL_SESSION_PASSWORD_SUBJECT,
                            $sContent,
                            WEBSITE_MAIL_NOREPLY);

            if ($bStatus == true)
              return Helper::successMessage(LANG_SUCCESS_MAIL_SENT, '/Session/create');
            else
              Helper::errorMessage(LANG_ERROR_MAIL_ERROR) . $this->showCreateSessionTemplate();
          }
          else
            Helper::errorMessage(LANG_ERROR_SQL_QUERY);
        }
        elseif ($this->_aRequest['action'] == 'resendverification') {
          if($this->_oModel->createResendActions() === true) {
            $aData = $this->_oModel->getData();

            $sVerificationUrl = Helper::createLinkTo('/User/' . $aData['verification_code'] . '/verification');

            $sContent = str_replace('%u', $aData['name'], LANG_MAIL_SESSION_VERIFICATION_BODY);
            $sContent = str_replace('%v', $sVerificationUrl, $sContent);

            $bStatus = Mail::send(Helper::formatInput($this->_aRequest['email']),
                            LANG_MAIL_SESSION_VERIFICATION_SUBJECT,
                            $sContent,
                            WEBSITE_MAIL_NOREPLY);

            if ($bStatus == true)
              return Helper::successMessage(LANG_SUCCESS_MAIL_SENT, '/Session/create');
            else
              return $this->showCreateSessionTemplate();
          }
          else
            Helper::errorMessage(LANG_ERROR_SQL_QUERY);
        }
        else
          return Helper::errorMessage(LANG_ERROR_REQUEST_MISSING_ACTION);
      }
    }
    else
      return $this->_showCreateResendActionsTemplate();
  }

  private final function _showCreateResendActionsTemplate() {
    $oSmarty = new Smarty();

    if($this->_aRequest['action'] == 'resendpassword') {
      $this->_setTitle(LANG_SESSION_PASSWORD_TITLE);

      $oSmarty->assign('_action_url_', '/Session/resendpassword');

      # Language
      $oSmarty->assign('lang_headline', LANG_SESSION_PASSWORD_TITLE);
      $oSmarty->assign('lang_description', LANG_SESSION_PASSWORD_INFO);
      $oSmarty->assign('lang_submit', LANG_SESSION_PASSWORD_LABEL_SUBMIT);
    }
    else {
      $this->_setTitle(LANG_SESSION_VERIFICATION_TITLE);

      $oSmarty->assign('_action_url_', '/Session/resendverification');

      # Language
      $oSmarty->assign('lang_headline', LANG_SESSION_VERIFICATION_TITLE);
      $oSmarty->assign('lang_description', LANG_SESSION_VERIFICATION_INFO);
      $oSmarty->assign('lang_submit', LANG_SESSION_VERIFICATION_LABEL_SUBMIT);
    }

    if (!empty($this->_aError)) {
      foreach ($this->_aError as $sField => $sMessage)
        $oSmarty->assign('error_' . $sField, $sMessage);
    }

    $oSmarty->template_dir = Helper::getTemplateDir('sessions/createResendActions');
    return $oSmarty->fetch('sessions/createResendActions.tpl');
  }

  public final function destroy() {
    if ($oStatus = & $this->_oModel->destroy() === true) {
      return Helper::successMessage(LANG_SESSION_DESTROY_SUCCESSFUL, '/Start');
      unset($_SESSION);
    }
    else
      return Helper::errorMessage(LANG_ERROR_SQL_QUERY);
  }
}
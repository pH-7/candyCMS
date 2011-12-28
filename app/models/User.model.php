<?php

/**
 * Handle all user SQL requests.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 */

namespace CandyCMS\Model;

use CandyCMS\Helper\AdvancedException as AdvancedException;
use CandyCMS\Helper\Helper as Helper;
use CandyCMS\Helper\Page as Page;
use PDO;

require_once 'app/controllers/Session.controller.php';

class User extends Main {

  /**
   * Get user name, surname and email from user ID.
   *
	 * @static
   * @access public
   * @param integer $iId ID of the user
   * @return array data with user information
   *
   */
  public static function getUserNamesAndEmail($iId) {
    if (empty(parent::$_oDbStatic))
      parent::_connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("SELECT
                                                name, surname, email
                                              FROM
                                                " . SQL_PREFIX . "users
                                              WHERE
                                                id = :id
                                              LIMIT 1");

      $oQuery->bindParam('id', $iId, PDO::PARAM_INT);
      $oQuery->execute();

      return $oQuery->fetch(PDO::FETCH_ASSOC);
    }
    catch (AdvancedException $e) {
      parent::$_oDbStatic->rollBack();
    }
  }

  /**
   * Check, if the user already with given email address exists.
   *
	 * @static
   * @access public
   * @param string $sEmail email address of user.
   * @return boolean status of user check
   *
   */
  public static function getExistingUser($sEmail) {
    if (empty(parent::$_oDbStatic))
      parent::_connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("SELECT
                                                email
                                              FROM
                                                " . SQL_PREFIX . "users
                                              WHERE
                                                email = :email
                                              LIMIT 1");

      $oQuery->bindParam('email', $sEmail, PDO::PARAM_STR);
      $oQuery->execute();

      $aResult = $oQuery->fetch(PDO::FETCH_ASSOC);

      if (isset($aResult['email']) && !empty($aResult['email']))
        return true;
    }
    catch (AdvancedException $e) {
      parent::$_oDbStatic->rollBack();
    }
  }

	/**
	 * Get the verification data from an users email address.
	 *
	 * @access public
	 * @param string $sEmail email address to search user from.
	 * @return array user data.
	 * @see app/models/Session.model.php
	 *
	 */
	public static function getVerificationData($sEmail) {
		if (empty(parent::$_oDbStatic))
			parent::_connectToDatabase();

		try {
			$oQuery = parent::$_oDbStatic->prepare("SELECT
																								name,
																								verification_code
																							FROM
																								" . SQL_PREFIX . "users
																							WHERE
																								email = :email");

			$oQuery->bindParam(':email', Helper::formatInput($sEmail), PDO::PARAM_STR);
			$oQuery->execute();

			return $oQuery->fetch(PDO::FETCH_ASSOC);
		}
		catch (AdvancedException $e) {
			parent::$_oDbStatic->rollBack();
		}
	}

	public static function setPassword($sEmail, $sPassword) {
		if (empty(parent::$_oDbStatic))
			parent::_connectToDatabase();

		try {
			$oQuery = parent::$_oDbStatic->prepare("UPDATE
																				" . SQL_PREFIX . "users
																			SET
																				password = :password
																			WHERE
																				email = :email");

			$oQuery->bindParam(':password', $sPassword, PDO::PARAM_STR);
			$oQuery->bindParam(':email', Helper::formatInput($sEmail), PDO::PARAM_STR);

			return $oQuery->execute();
		}
		catch (AdvancedException $e) {
			parent::$_oDbStatic->rollBack();
		}
	}

  /**
   * Set user entry or user overview data.
   *
   * @access private
   * @param boolean $bUpdate prepare data for update
   * @param integer $iLimit blog post limit
   * @return array data
   *
   */
  private function _setData($bUpdate, $iLimit) {
    if (empty($this->_iId)) {
      try {
        $oQuery = $this->_oDb->prepare("SELECT
                                          id,
                                          name,
                                          email,
                                          surname,
                                          last_login,
                                          date,
                                          use_gravatar,
																					receive_newsletter,
                                          verification_code,
                                          role
                                        FROM
                                          " . SQL_PREFIX . "users
                                        ORDER BY
                                          id ASC
                                        LIMIT
                                          :limit");

        $oQuery->bindParam('limit', $iLimit, PDO::PARAM_INT);
        $oQuery->execute();

        $aResult = $oQuery->fetchAll(PDO::FETCH_ASSOC);
      }
      catch (AdvancedException $e) {
        $this->_oDb->rollBack();
      }

      foreach ($aResult as $aRow) {
        $iId = $aRow['id'];

        $this->_aData[$iId] = $this->_formatForOutput($aRow, 'user');
        $this->_aData[$iId]['last_login'] = $aRow['last_login'] > 0 ? Helper::formatTimestamp($aRow['last_login'], 1) : '-';
      }

    }
    else {
      try {
				$oQuery = $this->_oDb->prepare("SELECT
                                          *
                                        FROM
                                          " . SQL_PREFIX . "users
                                        WHERE
                                          id = :id
                                        LIMIT 1");

				$oQuery->bindParam('id', $this->_iId, PDO::PARAM_INT);
				$oQuery->execute();

				$aRow = & $oQuery->fetch(PDO::FETCH_ASSOC);
			}
			catch (AdvancedException $e) {
				$this->_oDb->rollBack();
			}

			if ($bUpdate == true)
				$this->_aData = $this->_formatForUpdate($aRow);

			else {
				$this->_aData[1] = $this->_formatForOutput($aRow, 'user');
				$this->_aData[1]['last_login'] = Helper::formatTimestamp($aRow['last_login'], true);
			}
    }

    return $this->_aData;
  }

  /**
   * Get user entry or user overview data. Depends on avaiable ID.
   *
   * @access public
   * @param integer $iId ID to load data from. If empty, show overview.
   * @param boolean $bForceNoId Override ID to show user overview
   * @param boolean $bUpdate prepare data for update
   * @param integer $iLimit user overview limit
   * @return array data from _setData
   *
   */
  public function getData($iId = '', $bForceNoId = false, $bUpdate = false, $iLimit = 1000) {
		if (!empty($iId))
			$this->_iId = (int) $iId;

		if ($bForceNoId == true)
			$this->_iId = '';

		return $this->_setData($bUpdate, $iLimit);
	}

  /**
   * Create a user.
   *
   * @access public
	 * @param integer $iVerificationCode verification code that was sent to the user.
   * @return boolean status of query
   * @override app/models/Main.model.php
   *
   */
  public function create($iVerificationCode) {
    if (empty($iVerificationCode))
      die('No verification code given.');

		try {
			$oQuery = $this->_oDb->prepare("INSERT INTO
                                        " . SQL_PREFIX . "users
																				(	name,
																					surname,
																					password,
																					email,
																					date,
																					verification_code,
                                          api_token)
                                      VALUES
                                        ( :name,
																					:surname,
																					:password,
																					:email,
																					:date,
																					:verification_code,
                                          :api_token)");

      $sApiToken = md5(RANDOM_HASH . $this->_aRequest['email']);
			$oQuery->bindParam('name', Helper::formatInput($this->_aRequest['name']), PDO::PARAM_STR);
			$oQuery->bindParam('surname', Helper::formatInput($this->_aRequest['surname']), PDO::PARAM_STR);
			$oQuery->bindParam('password', md5(RANDOM_HASH . $this->_aRequest['password']), PDO::PARAM_STR);
			$oQuery->bindParam('email', Helper::formatInput($this->_aRequest['email']), PDO::PARAM_STR);
			$oQuery->bindParam('date', time(), PDO::PARAM_INT);
			$oQuery->bindParam('verification_code', $iVerificationCode, PDO::PARAM_STR);
			$oQuery->bindParam('api_token', $sApiToken, PDO::PARAM_STR);

      $bReturn = $oQuery->execute();
      parent::$iLastInsertId = Helper::getLastEntry('users');

      return $bReturn;
    }
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
   * Get the encrypted password of a user. This is required for user update actions.
   *
   * @access public
   * @param integer $iId ID to get password from
   * @return string encrypted password
   *
   */
  private function _getPassword($iId) {
		try {
			$oQuery = $this->_oDb->prepare("SELECT
																				password
																			FROM
																				" . SQL_PREFIX . "users
																			WHERE
																				id = :id
																			LIMIT
																				1");

			$oQuery->bindParam('id', $iId, PDO::PARAM_INT);
			$oQuery->execute();

			$aResult = $oQuery->fetch(PDO::FETCH_ASSOC);
			return $aResult['password'];
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
	 * Update a user.
	 *
	 * @access public
	 * @param integer $iId ID to update
	 * @return boolean status of query
	 *
	 */
	public function update($iId) {
		$iReceiveNewsletter = isset($this->_aRequest['receive_newsletter']) ? 1 : 0;
    $iUseGravatar = isset($this->_aRequest['use_gravatar']) ? 1 : 0;

    # Set other peoples user roles
    if (($iId !== $this->_aSession['userdata']['id']) && $this->_aSession['userdata']['role'] == 4)
      $iUserRole = isset($this->_aRequest['role']) && !empty($this->_aRequest['role']) ?
              (int) $this->_aRequest['role'] :
              1;
    else
      $iUserRole = $this->_aSession['userdata']['role'];

    # Get my active password
    $sPassword = $this->_getPassword($iId);

    # Change my password
    if (isset($this->_aRequest['password_new']) && !empty($this->_aRequest['password_new']) &&
            isset($this->_aRequest['password_old']) && !empty($this->_aRequest['password_old']) &&
            $this->_aSession['userdata']['id'] === $iId)
      $sPassword = md5(RANDOM_HASH . $this->_aRequest['password_new']);

		try {
			$oQuery = $this->_oDb->prepare("UPDATE
                                        " . SQL_PREFIX . "users
                                      SET
                                        name = :name,
                                        surname = :surname,
                                        content = :content,
																				receive_newsletter = :receive_newsletter,
                                        use_gravatar = :use_gravatar,
                                        password = :password,
                                        role = :role
                                      WHERE
                                        id = :id");

			$oQuery->bindParam('name', Helper::formatInput($this->_aRequest['name']), PDO::PARAM_STR);
			$oQuery->bindParam('surname', Helper::formatInput($this->_aRequest['surname']), PDO::PARAM_STR);
			$oQuery->bindParam('content', Helper::formatInput($this->_aRequest['content']), PDO::PARAM_STR);
			$oQuery->bindParam('receive_newsletter', $iReceiveNewsletter, PDO::PARAM_INT);
			$oQuery->bindParam('use_gravatar', $iUseGravatar, PDO::PARAM_INT);
			$oQuery->bindParam('password', $sPassword, PDO::PARAM_STR);
			$oQuery->bindParam('role', $iUserRole, PDO::PARAM_INT);
			$oQuery->bindParam('id', $iId, PDO::PARAM_INT);

			return $oQuery->execute();
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
   * Destroy a user.
   *
   * @access public
   * @param integer $iId ID to update
   * @return boolean status of query
   *
   */
  public function destroy($iId) {
		try {
			$oQuery = $this->_oDb->prepare("DELETE FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        id = :id
                                      LIMIT
                                        1");

			$oQuery->bindParam('id', $iId, PDO::PARAM_INT);
			return $oQuery->execute();
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
   * Update a user account when verification link is clicked.
   *
   * @access public
   * @param string $sVerificationCode Code to remove.
   * @return boolean status of query
   */
  public function verifyEmail($sVerificationCode) {
		try {
			$oQuery = $this->_oDb->prepare("SELECT
                                        *
                                      FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        verification_code = :verification_code
                                      LIMIT 1");

			$oQuery->bindParam('verification_code', Helper::formatInput($sVerificationCode), PDO::PARAM_STR);
			$oQuery->execute();

			$this->_aData = $oQuery->fetch(PDO::FETCH_ASSOC);
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}

		if (!empty($this->_aData['id'])) {
			try {
				$oQuery = $this->_oDb->prepare("UPDATE
                                          " . SQL_PREFIX . "users
                                        SET
                                          verification_code = '',
                                          receive_newsletter = '1',
                                          date = :date
                                        WHERE
                                          id = :id");

				$oQuery->bindParam('id', $this->_aData['id'], PDO::PARAM_INT);
				$oQuery->bindParam('date', time(), PDO::PARAM_INT);

				# Prepare for first login
				$this->_aData['verification_code'] = '';
				Session::create($this->_aData);

				return $oQuery->execute();
			}
			catch (AdvancedException $e) {
				$this->_oDb->rollBack();
			}
		}
	}

	/**
	 * Return data from verification / activation.
	 *
	 * @access public
	 * @return array $this->_aData
	 */
	public function getActivationData() {
		return $this->_aData;
	}

	/**
	 * Return an array of user data if user exists.
	 *
	 * @access public
	 * @return array user data of login user
	 *
	 */
	public function getLoginData() {
		try {
			$oQuery = $this->_oDb->prepare("SELECT
                                        id, verification_code
                                      FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        email = :email
                                      AND
                                        password = :password
                                      LIMIT
                                        1");

			$sPassword = md5(RANDOM_HASH . Helper::formatInput($this->_aRequest['password']));
			$oQuery->bindParam('email', Helper::formatInput($this->_aRequest['email']), PDO::PARAM_STR);
			$oQuery->bindParam('password', $sPassword, PDO::PARAM_STR);
			$oQuery->execute();

			return $oQuery->fetch(PDO::FETCH_ASSOC);
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
   * Get the API token of a user.
   *
   * @access public
   * @return string token or null
   *
   */
	public function getToken() {
		try {
			$oQuery = $this->_oDb->prepare("SELECT
                                        api_token
                                      FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        email = :email
                                      AND
                                        password = :password
                                      LIMIT
                                        1");

			$sPassword = md5(RANDOM_HASH . Helper::formatInput($this->_aRequest['password']));
			$oQuery->bindParam('email', Helper::formatInput($this->_aRequest['email']), PDO::PARAM_STR);
			$oQuery->bindParam('password', $sPassword, PDO::PARAM_STR);
			$oQuery->execute();
      $aData = $oQuery->fetch(PDO::FETCH_ASSOC);

			return $aData['api_token'];
		}
		catch (AdvancedException $e) {
			$this->_oDb->rollBack();
		}
	}

  /**
   * Fetch all user data of active token.
   *
   * @static
   * @access public
   * @param string $sApiToken API token
   * @return array $aResult user data
   * @see app/controllers/Index.controller.php
	 *
   */
  public static function getUserDataByToken($sApiToken) {
    if (empty(parent::$_oDbStatic))
      parent::_connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("SELECT
                                                *
                                              FROM
                                                " . SQL_PREFIX . "users
                                              WHERE
                                                api_token = :api_token
                                              LIMIT
                                                1");

      $oQuery->bindParam('api_token', Helper::formatInput($sApiToken), PDO::PARAM_STR);
      $bReturn = $oQuery->execute();

      if ($bReturn == false)
        $this->destroy();

      return $oQuery->fetch(PDO::FETCH_ASSOC);
    }
    catch (AdvancedException $e) {
      parent::$_oDbStatic->rollBack();
    }
  }
}
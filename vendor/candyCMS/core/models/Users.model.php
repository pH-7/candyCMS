<?php

/**
 * Handle all user SQL requests.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 *
 */

namespace CandyCMS\Core\Models;

use CandyCMS\Core\Helpers\AdvancedException;
use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\Pagination;
use PDO;

class Users extends Main {

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
      parent::connectToDatabase();

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
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0077 - ' . $p->getMessage());
      exit('SQL error.');
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
      parent::connectToDatabase();

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
      AdvancedException::reportBoth('0078 - ' . $e->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Get the verification data from an users email address.
   *
   * @static
   * @access public
   * @param string $sEmail email address to search user from.
   * @return array user data.
   * @see vendor/candyCMS/core/models/Session.model.php
   *
   */
  public static function getVerificationData($sEmail) {
    if (empty(parent::$_oDbStatic))
      parent::connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("SELECT
                                                name,
                                                verification_code
                                              FROM
                                                " . SQL_PREFIX . "users
                                              WHERE
                                                email = :email
                                              AND
                                                verification_code != ''");

      $oQuery->bindParam(':email', Helper::formatInput($sEmail), PDO::PARAM_STR);
      $oQuery->execute();

      return $oQuery->fetch(PDO::FETCH_ASSOC);
    }
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0079 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Sets a users password.
   *
   * @static
   * @access public
   * @param string $sEmail
   * @param string $sPassword
   * @param boolean $bEncrypt
   * @return boolean status of query
   *
   */
  public static function setPassword($sEmail, $sPassword, $bEncrypt = false) {
    $oDB = parent::connectToDatabase();

    $sPassword = $bEncrypt == true ? md5(RANDOM_HASH . $sPassword) : $sPassword;

    try {
      $oQuery = $oDB->prepare("UPDATE
                                " . SQL_PREFIX . "users
                              SET
                                `password` = :password
                              WHERE
                                `email` = :email");

      $oQuery->bindParam(':password', $sPassword, PDO::PARAM_STR);
      $oQuery->bindParam(':email', Helper::formatInput($sEmail), PDO::PARAM_STR);

      return ($oQuery->execute() && $oQuery->rowCount() == 1);
    }
    catch (\PDOException $p) {
      try {
        parent::$_oDbStatic->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0080 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0081 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Get user entry or user overview data. Depends on available ID.
   *
   * @access public
   * @param integer $iId ID to load data from. If empty, show overview.
   * @param boolean $bForceNoId Override ID to show user overview
   * @param boolean $bUpdate prepare data for update
   * @param integer $iLimit user overview limit
   * @return array data from _setData
   * @todo pagination
   *
   */
  public function getData($iId = '', $bForceNoId = false, $bUpdate = false, $iLimit = 1000) {
    $aInts  = array('id', 'role');
    $aBools = array('use_gravatar', 'receive_newsletter');

    if ($bForceNoId === true)
      $iId = '';

    if (empty($iId)) {
      try {
        $oQuery = $this->_oDb->prepare("SELECT
                                          u.id,
                                          u.name,
                                          u.email,
                                          u.surname,
                                          u.date,
                                          u.use_gravatar,
                                          u.receive_newsletter,
                                          u.verification_code,
                                          u.role,
                                          s.date as last_login
                                        FROM
                                          " . SQL_PREFIX . "users as u
                                        LEFT JOIN
                                          " . SQL_PREFIX . "sessions as s
                                        ON
                                          s.user_id = u.id
                                        ORDER BY
                                          u.id ASC
                                        LIMIT
                                          :limit");

        $oQuery->bindParam('limit', $iLimit, PDO::PARAM_INT);
        $oQuery->execute();

        $aResult = $oQuery->fetchAll(PDO::FETCH_ASSOC);
      }
      catch (\PDOException $p) {
        AdvancedException::reportBoth('0082 - ' . $p->getMessage());
        exit('SQL error.');
      }

      foreach ($aResult as $aRow) {
        $iId = $aRow['id'];

        $this->_aData[$iId] = $this->_formatForUserOutput($aRow, $aInts, $aBools);
        $this->_aData[$iId]['last_login'] = Helper::formatTimestamp($aRow['last_login']);
      }
    }
    else {
      try {
        $oQuery = $this->_oDb->prepare("SELECT
                                          u.*,
                                          s.date as last_login
                                        FROM
                                          " . SQL_PREFIX . "users as u
                                        LEFT JOIN
                                          " . SQL_PREFIX . "sessions as s
                                        ON
                                          s.user_id = u.id
                                        WHERE
                                          u.id = :id
                                        ORDER BY
                                          s.date DESC
                                        LIMIT 1");

        $oQuery->bindParam('id', $iId, PDO::PARAM_INT);
        $oQuery->execute();

        $aRow = $oQuery->fetch(PDO::FETCH_ASSOC);
      }
      catch (\PDOException $p) {
        AdvancedException::reportBoth('0083 - ' . $p->getMessage());
        exit('SQL error.');
      }

      if ($bUpdate === true)
        $this->_aData = $this->_formatForUpdate($aRow);

      else {
        $this->_aData[1] = $this->_formatForUserOutput($aRow, $aInts, $aBools);
        $this->_aData[1]['last_login'] = Helper::formatTimestamp($aRow['last_login']);
      }
    }

    return $this->_aData;
  }

  /**
   * Create a user.
   *
   * @access public
   * @param integer $iVerificationCode verification code that was sent to the user.
   * @param integer $iRole role of new User
   * @return boolean status of query
   *
   */
  public function create($iVerificationCode = '', $iRole = 1) {
    try {
      $oQuery = $this->_oDb->prepare("INSERT INTO
                                        " . SQL_PREFIX . "users
                                        ( name,
                                          surname,
                                          password,
                                          email,
                                          date,
                                          role,
                                          verification_code,
                                          api_token)
                                      VALUES
                                        ( :name,
                                          :surname,
                                          :password,
                                          :email,
                                          :date,
                                          :role,
                                          :verification_code,
                                          :api_token)");

      $sApiToken = md5(RANDOM_HASH . $this->_aRequest['email']);
      $oQuery->bindParam('name', Helper::formatInput($this->_aRequest['name']), PDO::PARAM_STR);
      $oQuery->bindParam('surname', Helper::formatInput($this->_aRequest['surname']), PDO::PARAM_STR);
      $oQuery->bindParam('password', md5(RANDOM_HASH . $this->_aRequest['password']), PDO::PARAM_STR);
      $oQuery->bindParam('email', Helper::formatInput($this->_aRequest['email']), PDO::PARAM_STR);
      $oQuery->bindParam('date', time(), PDO::PARAM_INT);
      $oQuery->bindParam('role', $iRole, PDO::PARAM_INT);
      $oQuery->bindParam('verification_code', $iVerificationCode, PDO::PARAM_STR);
      $oQuery->bindParam('api_token', $sApiToken, PDO::PARAM_STR);

      $bReturn = $oQuery->execute();
      parent::$iLastInsertId = Helper::getLastEntry('users');

      return $bReturn;
    }
    catch (\PDOException $p) {
      try {
        $this->_oDb->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0084 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0085 - ' . $p->getMessage());
      exit('SQL error.');
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
    if ($iId!== $this->_aSession['user']['id'] && $this->_aSession['user']['role'] == 4)
      $iUserRole = isset($this->_aRequest['role']) && !empty($this->_aRequest['role']) ?
              (int) $this->_aRequest['role'] :
              1;
    else
      $iUserRole = & $this->_aSession['user']['role'];

    try {
      $oQuery = $this->_oDb->prepare("UPDATE
                                        " . SQL_PREFIX . "users
                                      SET
                                        name = :name,
                                        surname = :surname,
                                        content = :content,
                                        receive_newsletter = :receive_newsletter,
                                        use_gravatar = :use_gravatar,
                                        role = :role
                                      WHERE
                                        id = :id");

      $oQuery->bindParam('name', Helper::formatInput($this->_aRequest['name']), PDO::PARAM_STR);
      $oQuery->bindParam('surname', Helper::formatInput($this->_aRequest['surname']), PDO::PARAM_STR);
      $oQuery->bindParam('content', Helper::formatInput($this->_aRequest['content']), PDO::PARAM_STR);
      $oQuery->bindParam('receive_newsletter', $iReceiveNewsletter, PDO::PARAM_INT);
      $oQuery->bindParam('use_gravatar', $iUseGravatar, PDO::PARAM_INT);
      $oQuery->bindParam('role', $iUserRole, PDO::PARAM_INT);
      $oQuery->bindParam('id', $iId, PDO::PARAM_INT);

      return $oQuery->execute();
    }
    catch (\PDOException $p) {
      try {
        $this->_oDb->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0087 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0088 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Update an users password.
   *
   * @access public
   * @param integer $iId
   * @return boolean status of query
   *
   */
  public function updatePassword($iId) {
    try {
      $oQuery = $this->_oDb->prepare("UPDATE
                                        " . SQL_PREFIX . "users
                                      SET
                                        password = :password
                                      WHERE
                                        id = :id");

      $sPassword = md5(RANDOM_HASH . $this->_aRequest['password_new']);
      $oQuery->bindParam('password', $sPassword, PDO::PARAM_STR);
      $oQuery->bindParam('id', $iId, PDO::PARAM_INT);

      return $oQuery->execute();
    }
    catch (\PDOException $p) {
      try {
        $this->_oDb->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0097 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0098 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Update the Gravatar status of a user.
   *
   * This is needed if the user has chosen to use a Gravatar first and then wants to update his profile
   * with a custom avatar. If he'd upload an image and didn't save his changings to not use a Gravatar any
   * longer, the avatar wouldn't be shown. We now force the status to update if he uploads an image.
   *
   * @static
   * @access public
   * @param integer $iId user ID
   * @param integer $iUseGravatar do we want to use a Gravatar?
   * @return boolean status of query
   *
   */
  public static function updateGravatar($iId, $iUseGravatar = 0) {
    if (empty(parent::$_oDbStatic))
      parent::connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("UPDATE
                                                " . SQL_PREFIX . "users
                                              SET
                                                use_gravatar = :use_gravatar
                                              WHERE
                                                id = :id");

      $oQuery->bindParam('use_gravatar', $iUseGravatar, PDO::PARAM_INT);
      $oQuery->bindParam('id', $iId, PDO::PARAM_INT);

      return $oQuery->execute();
    }
    catch (\PDOException $p) {
      try {
        parent::$_oDbStatic->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0106 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0107 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Update a user account when verification link is clicked.
   *
   * @access public
   * @param string $sVerificationCode Code to remove.
   * @return boolean status of query
   *
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
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0091 - ' . $p->getMessage());
      exit('SQL error.');
    }

    if ($this->_aData['id']) {
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

        $sModel = $this->__autoload('Sessions');
        $sModel::create($this->_aData);

        return $oQuery->execute();
      }
      catch (\PDOException $p) {
        try {
          $this->_oDb->rollBack();
        }
        catch (\Exception $e) {
          AdvancedException::reportBoth('0092 - ' . $e->getMessage());
        }

        AdvancedException::reportBoth('0093 - ' . $p->getMessage());
        exit('SQL error.');
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
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0094 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Get the API token of a user.
   *
   * @access public
   * @return string the token or empty string
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

      return !empty($aData['api_token']) ? $aData['api_token'] : '';
    }
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0095 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Fetch all user data of active token.
   *
   * @static
   * @access public
   * @param string $sApiToken API token
   * @return array $aResult user data
   * @see vendor/candyCMS/core/controllers/Index.controller.php
   *
   */
  public static function getUserByToken($sApiToken) {
    if (empty(parent::$_oDbStatic))
      parent::connectToDatabase();

    try {
      $oQuery = parent::$_oDbStatic->prepare("SELECT
                                                *
                                              FROM
                                                " . SQL_PREFIX . "users
                                              WHERE
                                                api_token = :api_token
                                              LIMIT
                                                1");

      $oQuery->bindParam('api_token', $sApiToken, PDO::PARAM_STR);
      $oQuery->execute();

      return parent::_formatForUserOutput($oQuery->fetch(PDO::FETCH_ASSOC));
    }
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0096 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }
}
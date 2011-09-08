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

class User extends \CandyCMS\Model\Main {

  # Get user name and surname
  public static final function getUserNamesAndEmail($iId) {
    try {
      $oDb = new \PDO('mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB, SQL_USER, SQL_PASSWORD);
      $oDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $oQuery = $oDb->prepare("SELECT name, surname, email FROM " . SQL_PREFIX . "users WHERE id = :id LIMIT 1");

      $oQuery->bindParam('id', $iId);
      $oQuery->execute();

      $aResult = $oQuery->fetch(\PDO::FETCH_ASSOC);
      $oDb = null;

      return $aResult;
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $oDb->rollBack();
    }
  }

  public static function getExistingUser($sEmail) {
    try {
      $oDb = new \PDO('mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB, SQL_USER, SQL_PASSWORD);
      $oDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $oQuery = $oDb->prepare("SELECT email FROM " . SQL_PREFIX . "users WHERE email = :email LIMIT 1");

      $oQuery->bindParam('email', $sEmail);
      $oQuery->execute();

      $aResult = $oQuery->fetch(\PDO::FETCH_ASSOC);
      $oDb = null;

      if (isset($aResult['email']) && !empty($aResult['email']))
        return true;
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $oDb->rollBack();
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
                                          use_gravatar
                                        FROM
                                          " . SQL_PREFIX . "users
                                        ORDER BY
                                          id ASC
                                        LIMIT
                                          :limit");

        $oQuery->bindParam('limit', $iLimit, \PDO::PARAM_INT);
        $oQuery->execute();

        $aResult = $oQuery->fetchAll(\PDO::FETCH_ASSOC);
      }
      catch (\CandyCMS\Helper\AdvancedException $e) {
        $this->_oDb->rollBack();
      }

      foreach ($aResult as $aRow) {
        $iId = $aRow['id'];

        $this->_aData[$iId] = $this->_formatForOutput($aRow, 'user');
        $this->_aData[$iId]['last_login'] = \CandyCMS\Helper\Helper::formatTimestamp($aRow['last_login'], true);
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

        $oQuery->bindParam('id', $this->_iId, \PDO::PARAM_INT);
        $oQuery->execute();

        $aRow = & $oQuery->fetch(\PDO::FETCH_ASSOC);
      }
      catch (\CandyCMS\Helper\AdvancedException $e) {
        $this->_oDb->rollBack();
      }

      if ($bUpdate == true)
        $this->_aData = $this->_formatForUpdate($aRow);

      else {
        $this->_aData[1] = $this->_formatForOutput($aRow, 'user');
        $this->_aData[1]['last_login'] = \CandyCMS\Helper\Helper::formatTimestamp($aRow['last_login'], true);
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

  public function create($iVerificationCode) {
    try {
      $oQuery = $this->_oDb->prepare("INSERT INTO
                                        " . SQL_PREFIX . "users
                                          (name, surname, password, email, date, verification_code)
                                      VALUES
                                        ( :name, :surname, :password, :email, :date, :verification_code )");

      $oQuery->bindParam('name', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['name']));
      $oQuery->bindParam('surname', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['surname']));
      $oQuery->bindParam('password', md5(RANDOM_HASH . $this->_aRequest['password']));
      $oQuery->bindParam('email', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['email']));
      $oQuery->bindParam('date', time());
      $oQuery->bindParam('verification_code', $iVerificationCode);

      return $oQuery->execute();
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $this->_oDb->rollBack();
    }
  }

  private function _getPassword($iId) {
    try {
      $oQuery = $this->_oDb->prepare("SELECT password FROM " . SQL_PREFIX . "users WHERE id = :id LIMIT 1");
      $oQuery->bindParam('id', $iId);
      $oQuery->execute();

      $aResult = $oQuery->fetch(\PDO::FETCH_ASSOC);
      return $aResult['password'];
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $this->_oDb->rollBack();
    }
  }

  public function update($iId) {
    $iReceiveNewsletter = isset($this->_aRequest['receive_newsletter']) ? 1 : 0;
    $iUseGravatar = isset($this->_aRequest['use_gravatar']) ? 1 : 0;

    # Set other peoples user right
    if (($iId !== USER_ID) && USER_RIGHT === 4)
      $iUserRight = isset($this->_aRequest['user_right']) && !empty($this->_aRequest['user_right']) ?
              (int) $this->_aRequest['user_right'] :
              1;
    else
      $iUserRight = USER_RIGHT;

    # Get my active password
    $sPassword = $this->_getPassword($iId);

    # Change passwords
    if (isset($this->_aRequest['password_new']) && !empty($this->_aRequest['password_new']) &&
            isset($this->_aRequest['password_old']) && !empty($this->_aRequest['password_old']) &&
            USER_ID === $iId)
      $sPassword = md5(RANDOM_HASH . $this->_aRequest['password_new']);

    try {
      $oQuery = $this->_oDb->prepare("UPDATE
                                        " . SQL_PREFIX . "users
                                      SET
                                        name = :name,
                                        surname = :surname,
                                        email = :email,
                                        content = :content,
                                        receive_newsletter = :receive_newsletter,
                                        use_gravatar = :use_gravatar,
                                        password = :password,
                                        user_right = :user_right
                                      WHERE
                                        id = :id");

      $oQuery->bindParam('name', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['name']));
      $oQuery->bindParam('surname', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['surname']));
      $oQuery->bindParam('email', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['email']));
      $oQuery->bindParam('content', \CandyCMS\Helper\Helper::formatInput($this->_aRequest['content']));
      $oQuery->bindParam('receive_newsletter', $iReceiveNewsletter);
      $oQuery->bindParam('use_gravatar', $iUseGravatar);
      $oQuery->bindParam('password', $sPassword);
      $oQuery->bindParam('user_right', $iUserRight);
      $oQuery->bindParam('id', $iId);

      return $oQuery->execute();
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $this->_oDb->rollBack();
    }
  }

  public function destroy($iId) {
    # Delete avatars
    @unlink(PATH_UPLOAD . '/user/32/' . (int) $iId . '.jpg');
    @unlink(PATH_UPLOAD . '/user/64/' . (int) $iId . '.jpg');
    @unlink(PATH_UPLOAD . '/user/100/' . (int) $iId . '.jpg');
    @unlink(PATH_UPLOAD . '/user/200/' . (int) $iId . '.jpg');
    @unlink(PATH_UPLOAD . '/user/popup/' . (int) $iId . '.jpg');
    @unlink(PATH_UPLOAD . '/user/original/' . (int) $iId . '.jpg');

    try {
      $oQuery = $this->_oDb->prepare("DELETE FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        id = :id
                                      LIMIT
                                        1");

      $oQuery->bindParam('id', $iId);
      return $oQuery->execute();
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $this->_oDb->rollBack();
    }
  }

  public function verifyEmail($iVerificationCode) {
    try {
      $oQuery = $this->_oDb->prepare("SELECT
                                        id
                                      FROM
                                        " . SQL_PREFIX . "users
                                      WHERE
                                        verification_code = :verification_code
                                      LIMIT 1");

      $oQuery->bindParam('verification_code', $iVerificationCode);
      $oQuery->execute();

      $aResult = $oQuery->fetch(\PDO::FETCH_ASSOC);
    }
    catch (\CandyCMS\Helper\AdvancedException $e) {
      $this->_oDb->rollBack();
    }

    if (!empty($aResult['id'])) {
      try {
        $oQuery = $this->_oDb->prepare("UPDATE
                                          " . SQL_PREFIX . "users
                                        SET
                                          verification_code = ''
                                        WHERE
                                          id = :id");

        $oQuery->bindParam('id', $aResult['id']);
        \CandyCMS\Model\Session::setActiveSession($aResult['id']);
        return $oQuery->execute();
      }
      catch (\CandyCMS\Helper\AdvancedException $e) {
        $this->_oDb->rollBack();
      }
    }
  }
}
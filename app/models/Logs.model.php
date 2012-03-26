<?php

/**
 * Handle all log SQL requests.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 */

namespace CandyCMS\Model;

use CandyCMS\Helper\AdvancedException as AdvancedException;
use CandyCMS\Helper\Helper as Helper;
use CandyCMS\Helper\Pagination as Pagination;
use PDO;

require_once PATH_STANDARD . '/app/helpers/Pagination.helper.php';

class Logs extends Main {

  /**
   * Get log overview data.
   *
   * @access public
   * @param integer $iLimit page limit
   * @return array $this->_aData
   *
   */
  public function getData($iLimit = 50) {
    $aInts = array('id', 'uid', 'user_id', 'action_id');

    try {
      $oQuery = $this->_oDb->query("SELECT COUNT(*) FROM " . SQL_PREFIX . "logs");
      $iResult = $oQuery->fetchColumn();
    }
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0105 - ' . $p->getMessage());
      exit('SQL error.');
    }

    $this->oPagination = new Pagination($this->_aRequest, $iResult, $iLimit);

    try {
      $oQuery = $this->_oDb->prepare("SELECT
                                        l.*,
                                        u.id AS uid,
                                        u.name,
                                        u.surname
                                      FROM
                                        " . SQL_PREFIX . "logs l
                                      LEFT JOIN
                                        " . SQL_PREFIX . "users u
                                      ON
                                        l.user_id=u.id
                                      ORDER BY
                                        l.time_end DESC
                                      LIMIT
                                        :offset,
                                        :limit");

      $oQuery->bindParam('limit', $this->oPagination->getLimit(), PDO::PARAM_INT);
      $oQuery->bindParam('offset', $this->oPagination->getOffset(), PDO::PARAM_INT);
      $oQuery->execute();

      $aResult = $oQuery->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (\PDOException $p) {
      AdvancedException::reportBoth('0066 - ' . $p->getMessage());
      exit('SQL error.');
    }

    foreach ($aResult as $aRow) {
      $iId = $aRow['id'];

      $this->_aData[$iId] = $this->_formatForOutput($aRow, $aInts);
      $this->_aData[$iId]['time_start'] = & Helper::formatTimestamp($aRow['time_start']);
      $this->_aData[$iId]['time_end']   = & Helper::formatTimestamp($aRow['time_end']);
    }

    return $this->_aData;
  }

  /**
   * Get log overview data.
   *
   * @static
   * @access public
   * @param string $sControllerName name of controller
   * @param string $sActionName name of action (CRUD)
   * @param integer $iActionId ID of the row that is affected
   * @param integer $iUserId ID of the acting user
   * @param integer $iTimeStart starting timestamp of the entry
   * @param integer $iTimeEnd ending timestamp of the entry
   * @return boolean status of query
   *
   */
  public static function insert($sControllerName, $sActionName, $iActionId, $iUserId, $iTimeStart, $iTimeEnd) {
    if (empty(parent::$_oDbStatic))
      parent::connectToDatabase();

    $iTimeStart = empty($iTimeStart) ? time() : $iTimeStart;
    $iTimeEnd = empty($iTimeEnd) ? time() : $iTimeEnd;

    try {
      $oQuery = parent::$_oDbStatic->prepare("INSERT INTO
                                                " . SQL_PREFIX . "logs
                                                ( controller_name,
                                                  action_name,
                                                  action_id,
                                                  time_start,
                                                  time_end,
                                                  user_id)
                                              VALUES
                                                ( :controller_name,
                                                  :action_name,
                                                  :action_id,
                                                  :time_start,
                                                  :time_end,
                                                  :user_id)");

      $oQuery->bindParam('controller_name', strtolower($sControllerName));
      $oQuery->bindParam('action_name', strtolower($sActionName));
      $oQuery->bindParam('action_id', $iActionId, PDO::PARAM_INT);
      $oQuery->bindParam('time_start', $iTimeStart);
      $oQuery->bindParam('time_end', $iTimeEnd);
      $oQuery->bindParam('user_id', $iUserId);

      $bReturn = $oQuery->execute();
      parent::$iLastInsertId = Helper::getLastEntry('logs');

      return $bReturn;
    }
    catch (\PDOException $p) {
      try {
        parent::$_oDbStatic->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0067 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0068 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Delete a log entry.
   *
   * @access public
   * @param integer $iId ID to delete
   * @return boolean status of query
   *
   */
  public function destroy($iId) {
    try {
      $oQuery = $this->_oDb->prepare("DELETE FROM
                                        " . SQL_PREFIX . "logs
                                      WHERE
                                        id = :id
                                      LIMIT
                                        1");

      $oQuery->bindParam('id', $iId);
      return $oQuery->execute();
    }
    catch (\PDOException $p) {
      try {
        $this->_oDb->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0069 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0070 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }
}
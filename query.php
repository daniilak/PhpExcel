<?php
class Query {

  public function getIdGroup($name) {
    $stmt = DataBase::query()->
      prepare("
        SELECT `ID` 
        FROM `groups` 
        WHERE `name_group` LIKE ? 
        LIMIT 1
      ");
      $stmt->bindValue(1,  $name, PDO::PARAM_STR);
      $stmt->execute();
    if ($stmt->rowCount() == 0) 
		{
			return $this->addNewGroup($name);
		} else {
      $t = $stmt->fetchAll();
      return intval($t[0]['ID']);
    }
  }

  public function addNewGroup($name) {
    $stmt = DataBase::query()->
      prepare("
        INSERT INTO `groups`
        (`name_group`)
        VALUES (?)
      ");
    $stmt->bindValue(1,  $name, PDO::PARAM_STR);
    $stmt->execute();
    $stmt = DataBase::query()->
      prepare("
        SELECT MAX(`ID`) AS `ID` 
        FROM `groups`
      ");
    $stmt->execute();
		$t = $stmt->fetchAll();

		return intval($t[0]['ID']);
  }

  public function send($id_group, $id_day, $id_time, $id_type_week, $lesson) {
		$from = '2018-08-20';
    $to   = '2018-12-30';
    switch (intval($id_type_week)) {
      case 0:
        $from = '2018-08-27';
      break;
      case 1:
        $from = '2018-09-03';
      break;
      case 2:
        $from = '2018-08-27';
      break;
    }
    $id_day = $id_day + 1;
    $guid = $this->getNewVersion();
    while ($from < $to) 
    { 
      switch (intval($id_type_week)) 
      {
        case 0://на каждой неделе
          $from = date('Y-m-d', strtotime($from . ' +'.(7).' days'));
        break;
        case 1: //на нечетной
          $from = date('Y-m-d', strtotime($from . ' +'.(7*2).' days'));
        break;
        case 2:// на четной
          $from = date('Y-m-d', strtotime($from . ' +'.(7*2).' days'));
        break;
      }
      $date = date('Y-m-d', strtotime($from . ' +'.($id_day-1).' days'));
      $stmt = DataBase::query()->
        prepare("
          INSERT INTO `timetable`
          (`GUID`, `id_group`,`date`,`type`,`day`,`string`,`time_id`)
          VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->bindValue(1,  $guid, PDO::PARAM_STR);
        $stmt->bindValue(2,  $id_group, PDO::PARAM_INT);
        $stmt->bindValue(3,  $date, PDO::PARAM_STR);
        $stmt->bindValue(4,  $id_type_week, PDO::PARAM_INT);
        $stmt->bindValue(5,  $id_day, PDO::PARAM_INT);
        $stmt->bindValue(6,  $lesson, PDO::PARAM_STR);
        $stmt->bindValue(7,  $id_time, PDO::PARAM_INT);
        $stmt->execute();
        $this->updateVersion($id_group);
      }
    
  }
  public function updateVersion($id_group) {
    $stmt = DataBase::query()->
      prepare("
        UPDATE `groups` SET `version` = ? WHERE `ID` = ?
      ");
      $stmt->bindValue(1,  $this->getNewVersion(), PDO::PARAM_STR);
      $stmt->bindValue(2,  $id_group, PDO::PARAM_INT);
      $stmt->execute();
  }

  public function getNewVersion()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }
        return sprintf(
        	'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        	mt_rand(0, 65535),
        	mt_rand(0, 65535),
        	mt_rand(0, 65535),
        	mt_rand(16384, 20479),
        	mt_rand(32768, 49151),
        	mt_rand(0, 65535),
        	mt_rand(0, 65535),
        	mt_rand(0, 65535)
        );
    }


}

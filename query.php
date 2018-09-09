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
        (`id_inst`,`name_group`)
        VALUES (1, ?)
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

  public function getIdLesson($name) {
    $stmt = DataBase::query()->
      prepare("
        SELECT `ID` 
        FROM `objects` 
        WHERE `name` LIKE ? 
        LIMIT 1
      ");
    $stmt->bindValue(1,  $name, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->rowCount() == 0) 
		{
			return $this->addNewObject($name);
		} else {
      $t = $stmt->fetchAll();
      return intval($t[0]['ID']);
    }
  }

  public function addNewObject($name) {
    $stmt = DataBase::query()->
      prepare("
        INSERT INTO `objects`
        (`name`)
        VALUES (?)
      ");
    $stmt->bindValue(1,  $name, PDO::PARAM_STR);
    $stmt->execute();
    $stmt = DataBase::query()->
      prepare("
        SELECT MAX(`ID`) AS `ID` 
        FROM `objects`
      ");
    $stmt->execute();
		$t = $stmt->fetchAll();

		return intval($t[0]['ID']);
  }

  public function getIdTypeLesson($name) {
    $stmt = DataBase::query()->
      prepare("
        SELECT `ID` 
        FROM `types_lessons` 
        WHERE `short_lesson` LIKE ? 
        LIMIT 1
      ");
    $stmt->bindValue(1,  $name, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->rowCount() == 0) 
		{
			return 11;
		} else {
      $t = $stmt->fetchAll();
      return intval($t[0]['ID']);
    }
  }
  
  public function getIdTeacher($name) {
    $stmt = DataBase::query()->
      prepare("
        SELECT `ID` 
        FROM `prepods` 
        WHERE `prepod` LIKE ? 
        LIMIT 1
      ");
      $stmt->bindValue(1,  $name, PDO::PARAM_STR);
      $stmt->execute();
    if ($stmt->rowCount() == 0) 
		{
			return $this->addNewPrepod($name);
		} else {
      $t = $stmt->fetchAll();
      return intval($t[0]['ID']);
    }
  }

  public function addNewPrepod($name) {
    $stmt = DataBase::query()->
      prepare("
        INSERT INTO `prepods`
        (`prepod`)
        VALUES (?)
      ");
    $stmt->bindValue(1,  $name, PDO::PARAM_STR);
    $stmt->execute();
    $stmt = DataBase::query()->
      prepare("
        SELECT MAX(`ID`) AS `ID` 
        FROM `prepods`
      ");
    $stmt->execute();
		$t = $stmt->fetchAll();

		return intval($t[0]['ID']);
  }
}

<?php
/**
 * Parser timetable with PHPExcel
 *
 * @author   daniilak
 */

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('max_execution_time', '600');
date_default_timezone_set('Europe/London');

/** Include PHPExcel_IOFactory */
require_once dirname(__FILE__) . '/PHPExcel/IOFactory.php';

if (!file_exists("20.xls")) {
    exit("Please run 14excel5.php first.\n");
}

$ex = new ex("20.xls");
// $ex-> setData ();
$ex-> getData ();
echo 'ok';

class ex
{
    protected $objPHPExcel;
    protected $data;
    protected $groups = [];
    protected $lessons = [];
    protected $dates = ['8:20-9:40', '09:55-11:15', '11:30-12:50', '13:20-14:40', '14:55-16:15', '16:30-17:50', '18:05-19:25', '19:40-21:00'];
    protected $daysName = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    protected $days = [
        'Понедельник' => ['from' => 0, 'to' => 0],
        'Вторник' => ['from' => 0, 'to' => 0],
        'Среда' => ['from' => 0, 'to' => 0],
        'Четверг' => ['from' => 0, 'to' => 0],
        'Пятница' => ['from' => 0, 'to' => 0],
        'Суббота' => ['from' => 0, 'to' => 0],
    ];

    protected $file;
    public function __construct($file)
    {
        $this->file = $file;
    }

    public function setData () {
        $this->objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($this->objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->excel1($worksheet);
        }
        $this->save ();
    }

    public function getData () {
        $this->objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($this->objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->excel2($worksheet);
        }
        $this->load ();
    }

    public function save () {
        $tmp = $this->groups['index'];
        foreach ($tmp as &$t) {
            $t['days'] = $this->days;
            unset($t);
        }
        file_put_contents('data.json', json_encode($tmp));
        echo 'ok';
    }

    public function load () {
        $this->data = json_decode(file_get_contents('data.json'), true);
        echo 'ok';
    }
    
    /*
    * First method for saving first data
    */
    public function excel1($worksheet)
    {
        $columns_count = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());

        for ($row = 1; $row <= $worksheet->getHighestRow(); $row++) {
            for ($column = 0; $column < $columns_count; $column++) {
                if ($row == 1 || $column < 2 ) {
                    $cell = $worksheet->getCellByColumnAndRow($column, $row);
                    $value = trim($cell->getCalculatedValue());
                    foreach ($worksheet->getMergeCells() as $mergedCells) {
                        if ($cell->isInRange($mergedCells)) {
                            $value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                            break;
                        }
                    }

                    if (!is_null($value) && $value != "") {
                        $value = trim($value);
                        if ($row == 1) {
                            $this->groups($value, $column);
                        } 
                        if ($column < 2 ) {
                            $this->dates($value, $row);
                        }
                    }
                }
            }
        }
        return true;
    }
    /*
    * Second method with load first data
    */
    public function excel2($worksheet)
    {
        $columns_count = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());

        for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
            for ($column = 2; $column < $columns_count; $column++) {

                $cell = $worksheet->getCellByColumnAndRow($column, $row);
                $value = trim($cell->getCalculatedValue());
                foreach ($worksheet->getMergeCells() as $mergedCells) {
                    if ($cell->isInRange($mergedCells)) {
                        $value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                        break;
                    }
                }

                if (!is_null($value) && $value != "") {
                    $value = trim($value);
                    $this->lessons($value, $column, $row);
                }
            }
        }
        return true;
    }


    public function dates($value, $row)
    {
        $days = $this->days;
        if (in_array($value, $this->daysName)) {
            if ($days[$value]['from'] == 0) {
                $days[$value]['from'] = $row;
            }
            $days[$value]['to'] = $row;
        }
        if (in_array($value, $this->dates)) {
            foreach ($days as $key => $day) {
                $a = ($day['to'] == 0) ? 9999 : $day['to'];
                if ($day['from'] <= $row && $row <= $a) {
                    if (!isset($days[$key]['data'][$value])) {
                        $days[$key]['data'][$value] = [];
                        if (!isset($days[$key]['data'][$value]['from'])) {
                            $days[$key]['data'][$value]['from'] = $row;
                        }
                    } else {
                        $days[$key]['data'][$value]['to'] = $row;
                    }
                }
            }
        }
        $this->days = $days;
    }
    public function lessons($value, $column, $row)
    {
        // var_dump('value: ' . trim($value) . ', column: ' . $column . ', row: ' . $row);
        $lessons = $this->lessons;
        $data = $this->data;
        foreach ($data as $k => $groups) {
            if ($groups["from"] <= $column && $column <= $groups["to"]) {
                foreach($groups as $m => $days) {
                    if ($days["from"] <= $row && $row <= $days["to"]) {
                        foreach($days as $n=> $date) {
                            if ($date["from"] <= $row && $row <= $date["to"]) {
                                if (isset($data[$k][$m][$n]['index'])) {
                                    $data[$k][$m][$n]['index'] []= $value;
                                } else {
                                    $data[$k][$m][$n]['index'] = [];
                                    $data[$k][$m][$n]['index'] []= $value;
                                }
                                break;
                            }
                        }
                        break;
                    }
                }
                break;
            }
        }
        $this->lessons = $lessons;
        $this->data = $data;
        $this->groups = $groups;
    }

    public function groups($value, $column)
    {
        $groups = $this->groups;
        if (!isset($groups['index'][$value])) {
            $groups['index'][$value] = [];
            $groups['index'][$value]['from'] = $column;
        } else {
            $groups['index'][$value]['to'] = $column;
        }
        $this->groups = $groups;
        return true;
    }
}

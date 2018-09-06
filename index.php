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
    protected $worksheet;
    protected $data;
    protected $groups = [];
    protected $lessons = [];
    protected $dates = ['8:20-9:40', '09:55-11:15', '11:30-12:50', '13:20-14:40', '14:55-16:15', '16:30-17:50', '18:05-19:25', '19:40-21:00'];
    protected $daysName = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    protected $days = [
        'Понедельник' => ['f' => 0, 't' => 0],
        'Вторник' => ['f' => 0, 't' => 0],
        'Среда' => ['f' => 0, 't' => 0],
        'Четверг' => ['f' => 0, 't' => 0],
        'Пятница' => ['f' => 0, 't' => 0],
        'Суббота' => ['f' => 0, 't' => 0],
    ];

    protected $file;
    public function __construct($file)
    {
        $this->file = $file;
    }

    public function setData () {
        $objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->worksheet = $worksheet;
            $this->excel1();
            break;
        }
        $this->save ();
    }

    public function getData () {
        $this->load ();
        $objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->worksheet = $worksheet;
            $this->excel2();
            break;
        }
        file_put_contents('dataNew.json', json_encode($this->data));
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
    }
    
    /*
    * First method for saving first data
    */
    public function excel1()
    {
        $worksheet = $this->worksheet;
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
    public function excel2()
    {
        $worksheet = $this->worksheet;
        $columns_count = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());

        for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
            for ($column = 2; $column < $columns_count; $column++) {

                $cell = $worksheet->getCellByColumnAndRow($column, $row);
                $value = trim($cell->getCalculatedValue());
                $guid = 0;
                foreach ($worksheet->getMergeCells() as $mergedCells) {
                    if ($cell->isInRange($mergedCells)) {
                        $value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                        $guid = $mergedCells;
                        break;
                    }
                }

                if (!is_null($value) && $value != "") {
                    $value = trim($value);
                    $this->lessons($value, $column, $row, $guid);
                }
            }
        }
        return true;
    }
    /*
    * Third method for save last data
    */
    public function excel3()
    {
        $data = json_decode(file_get_contents('dataNew.json'), true);

    }


    public function dates($value, $row)
    {
        $days = $this->days;
        if (in_array($value, $this->daysName)) {
            if ($days[$value]['f'] == 0) {
                $days[$value]['f'] = $row;
            }
            $days[$value]['t'] = $row;
        }
        if (in_array($value, $this->dates)) {
            foreach ($days as $key => $day) {
                $a = ($day['t'] == 0) ? 9999 : $day['t'];
                if ($day['f'] <= $row && $row <= $a) {
                    if (!isset($days[$key]['data'][$value])) {
                        $days[$key]['data'][$value] = [];
                        if (!isset($days[$key]['data'][$value]['f'])) {
                            $days[$key]['data'][$value]['f'] = $row;
                        }
                    } else {
                        $days[$key]['data'][$value]['t'] = $row;
                    }
                }
            }
        }
        $this->days = $days;
    }
    public function lessons($value, $column, $row, $index = 0)
    {
        // var_dump('value: ' . trim($value) . ', column: ' . $column . ', row: ' . $row);
        $lessons = $this->lessons;
        $data = $this->data;
        foreach ($data as $k => $groups) {
            if ($groups["f"] <= $column && $column <= $groups["t"]) {
                foreach($groups['days'] as $m => $days) {
                    if ($days["f"] <= $row && $row <= $days["t"]) {
                        foreach($days['data'] as $n=> $date) {
                            if ($date["f"] <= $row && $row <= $date["t"]) {
                                if (isset($data[$k]['days'][$m]['data'][$n]['value'])) {
                                    if ($index == 0 || array_search($index, $data[$k]['days'][$m]['data'][$n]['index'], true) === false)
                                    {
                                        $data[$k]['days'][$m]['data'][$n]['value'] []= $value;
                                        $data[$k]['days'][$m]['data'][$n]['index'] []= $index;
                                    }
                                    
                                } else {
                                    $data[$k]['days'][$m]['data'][$n]['value'] = [];
                                    $data[$k]['days'][$m]['data'][$n]['index'] = [];
                                    $data[$k]['days'][$m]['data'][$n]['value'] []= $value;
                                    $data[$k]['days'][$m]['data'][$n]['index'] []= $index;
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
            $groups['index'][$value]['f'] = $column;
        } else {
            $groups['index'][$value]['t'] = $column;
        }
        $this->groups = $groups;
        return true;
    }
}

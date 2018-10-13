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
ini_set('max_execution_time', '1000');
date_default_timezone_set('Europe/London');

/** Include PHPExcel_IOFactory */
require_once dirname(__FILE__) . '/PHPExcel/IOFactory.php';
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/query.php';

$ex = new ex("55.xls");
$ex->setData();
$ex->getData();
$ex-> excel3();

class ex
{
    protected $worksheet;
    protected $data;
    protected $isSubGroup = 0;

    protected $tempValueStar = '';
    protected $groups = [];
    protected $lessons = [];

    protected $dates = [1, 2, 3, 4, 5, 6, 7, 8];
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
    protected $query;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function setData()
    {
        $objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->worksheet = $worksheet;
            $this->excel1();
            break;
        }
        $this->save();
        echo 'ok setData<br>';
    }

    public function getData()
    {
        $this->load();
        $objPHPExcel = PHPExcel_IOFactory::load($this->file);
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->worksheet = $worksheet;
            $this->excel2();
            break;
        }
        file_put_contents('dataNew.json', json_encode($this->data));
        echo 'ok getData<br>';
    }

    public function save()
    {
        $groups = $this->groups;
        foreach ($groups as &$group) {
            $group['days'] = $this->days;
        }
        file_put_contents('data.json', json_encode($groups));
        echo 'ok';
    }

    public function load()
    {
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
                if ($row <= 2 || $column < 2) {
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

                        if ($this->isSubGroup == 1 && $row == 2 && intval($value) != 0) {
                            $this->subGroup($value, $column);
                        }
                        if ($column < 2) {
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

        for ($row = ($this->isSubGroup == 1) ? 3 : 2; $row <= $worksheet->getHighestRow(); $row++) {
            for ($column = 2; $column < $columns_count; $column++) {

                $cell = $worksheet->getCellByColumnAndRow($column, $row);
                $value = $cell->getCalculatedValue();
                $guid = '';
                $startCol = 0;
                $endCol = 0;
                foreach ($worksheet->getMergeCells() as $mergedCells) {
                    if ($cell->isInRange($mergedCells)) {
                        $value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                        $guid = $mergedCells;
                        if ($this->isSubGroup == 1) {
                            $tmp = explode(":", $mergedCells);
                            $startCol = 
                                PHPExcel_Cell::columnIndexFromString( preg_replace('/[0-9]+/', '', $tmp[0])) - 1;
                            $endCol = 
                                PHPExcel_Cell::columnIndexFromString( preg_replace('/[0-9]+/', '', $tmp[1])) - 1;
                        }
                        break;
                    }
                }

                if (!is_null($value) && $value != "") {
                    $value = preg_replace("/\s{2,}/"," ", trim($value));
                    $this->lessons($value, $column, $row, $guid,$startCol, $endCol);
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
        $this->setQuery();
        foreach ($data as $k => &$groups) {
            foreach ($groups['days'] as $p => &$days) {
                foreach ($days['data'] as $t => &$date) {
                    if (isset($date['value'])) {
                        unset($date['index']);
                        foreach ($date['value'] as $j => &$lesson) {
                            if ($lesson == '*' || $lesson == '**') {
                                $date['value'][$j + 1] = $date['value'][$j + 1] . "§" . $lesson;
                                unset($date['value'][$j]);
                            } else {
                                $bool = 0; // 1 - если в одной ячейке 1 звезда и 2 звезды одновременно
                                if (stristr($lesson, '**')) {
                                    $tmp = str_replace("**", "", $lesson);
                                    if (stristr($lesson, '*')) {
                                        $lesson = $tmp;
                                        $bool = 1;
                                    }
                                }
                                if ($bool != 1) {
                                    if (stristr($lesson, '**')) {
                                        $lesson = str_replace("**", "", $lesson);
                                        $lesson = $lesson . "§" . '**';
                                    }
                                    if (stristr($lesson, '*')) {
                                        $lesson = str_replace("*", "", $lesson);
                                        $lesson = $lesson . "§" . '*';
                                    }
                                }
                                $lesson = str_replace("\n", " ", $lesson);
                                $this->sendTimetable($k, $p, $t, $lesson);
                            }
                        }
                    }
                }
            }
        }
        echo 'ok excel3<br>';
    }

    public function dates($value, $row)
    {
        $days = $this->days;
        if (in_array($value, $this->daysName)) {
            if ($days[$value]['f'] == 0) {
                $days[$value]['f'] = $row;
            }
            $days[$value]['t'] = $row;
            $this->days = $days;
            return;
        }
        $value = intval($value);
        if (in_array($value, $this->dates)) {
            foreach ($days as $key => $day) {
                $a = ($day['t'] == 0) ? 9999 : $day['t'];
                if ($day['f'] <= $row && $row <= $a) {
                    if (!isset($days[$key]['data'][$value])) {
                        $days[$key]['data'][$value] = [];
                        if (!isset($days[$key]['data'][$value]['f'])) {
                            $days[$key]['data'][$value]['f'] = $row;
                            $days[$key]['data'][$value]['t'] = $row;
                        }
                    } else {
                        $days[$key]['data'][$value]['t'] = $row;
                    }
                }
            }
        }
        $this->days = $days;
    }

    public function lessons($value, $column, $row, $index, $startCol = 0, $endCol = 0)
    {
        $data = $this->data;
        foreach ($data as $k => &$groups) {
            if ($groups["f"] <= $column && $column <= $groups["t"]) {
                if ($this->isSubGroup == 1 && isset($groups['sub'])) {
                    if ($startCol == $endCol)
                    foreach ($groups['sub'] as $key => $sub) {
                        if ($sub == $column) {
                            $value .= '(' . $key . ' подгруппа)';
                        }
                    }
                }
                foreach ($groups['days'] as $p => &$days) {
                    if ($days["f"] <= $row && $row <= $days["t"]) {
                        foreach ($days['data'] as $n => &$date) {
                            if ($date["f"] <= $row && $row <= $date["t"]) {
                                if (isset($date['value'])) {
                                    if ($index == '' || !in_array($index, $date['index'])) {
                                        $date['value'][] = $value;
                                        $date['index'][] = $index;
                                    }
                                } else {
                                    $date['value'] = [];
                                    $date['index'] = [];
                                    $date['index'][] = $index;
                                    $date['value'][] = $value;
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
        $this->data = $data;
    }

    public function subGroup($value, $column)
    {
        $groups = $this->groups;
        foreach ($groups as &$group) {
            if ($group["f"] <= $column && $column <= $group["t"]) {
                $group['sub'][$value] = $column;
                break;
            }
        }
        $this->groups = $groups;
        return true;
    }

    public function groups($value, $column)
    {
        $groups = $this->groups;
        if (!isset($groups[$value])) {
            $groups[$value] = [];
            $groups[$value]['f'] = $column;
            $groups[$value]['t'] = $column;
        } else {
            $groups[$value]['t'] = $column;
        }
        $this->groups = $groups;
        return true;
    }

    //name_group, id_subgroup || X, name_day, name_time, lesson by format:
    public function sendTimetable($name_group, $name_day, $name_time, $lesson)
    {
        $id_group = $this->query->getIdGroup($name_group);
        $id_day = array_search($name_day, $this->daysName);
        $id_type_week = 0;
        $arr = explode('§', $lesson);
        if (isset($arr[1])) {
            $id_type_week = ($arr[1] == '*') ? 1 : 2;
            $lesson = $arr[0];
        }

        $lesson = preg_replace("/\s{2,}/", " ", $lesson);
        $this->query->send($id_group, $id_day, $name_time, $id_type_week, $lesson);
    }

    public function setQuery()
    {
        $this->query = new Query();
    }
}

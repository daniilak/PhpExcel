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
// $ex->getData();
$ex->excel3();
echo 'ok';

class ex
{
    protected $worksheet;
    protected $data;
    protected $tempValueStar = '';
    protected $groups = [];
    protected $lessons = [];
    protected $dates = ['8:20-9:40', '09:55-11:15', '11:30-12:50', '13:20-14:40', '14:55-16:15', '16:30-17:50', '18:05-19:25', '19:40-21:00'];
    protected $daysName = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    protected $days = [
        'Понедельник'   => ['f' => 0, 't' => 0],
        'Вторник'       => ['f' => 0, 't' => 0],
        'Среда'         => ['f' => 0, 't' => 0],
        'Четверг'       => ['f' => 0, 't' => 0],
        'Пятница'       => ['f' => 0, 't' => 0],
        'Суббота'       => ['f' => 0, 't' => 0],
    ];

    protected $file;
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
    }

    public function save()
    {
        $groups = $this->groups;
        foreach ($groups as &$group) {
            foreach ($group['index'] as &$subGroups) {
                $subGroups['days'] = $this->days;
            }
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
                if ($row == 1 || $row == 2 || $column < 2) {
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

                        if ($row == 2) {
                            $this->subGroups($value, $column);
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

        for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
            for ($column = 2; $column < $columns_count; $column++) {

                $cell = $worksheet->getCellByColumnAndRow($column, $row);
                $value = trim($cell->getCalculatedValue());
                $guid = '';
                foreach ($worksheet->getMergeCells() as $mergedCells) {
                    if ($cell->isInRange($mergedCells)) {
                        $value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                        $guid = $mergedCells;
                        break;
                    }
                }

                if (!is_null($value) && $value != "") {
                    $value = trim($value);
                    // звезда проставлена слева сверху
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
        foreach ($data as $k => &$groups) {
            foreach ($groups['index'] as $m => &$subGroups) {
                foreach ($subGroups['days'] as $p => &$days) {
                    foreach ($days['data'] as $t => &$date) {
                        if (isset($date['value'])) {
                            unset($date['index']);
                            foreach ($date['value'] as $j => &$lesson) {
                                if ($lesson == '*' || $lesson == '**') {
                                    $date['value'][$j + 1] = $date['value'][$j + 1] . "§" . $lesson;
                                    unset($date['value'][$j]);
                                } else {
                                    $this->sendTimetable($k, $m, $p, $t, $lesson);
                                }
                            }
                        }
                    }
                }
            }
        }
        file_put_contents('dataNewNew.json', json_encode($data));
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

    public function lessons($value, $column, $row, $index = '')
    {
        $data = $this->data;
        foreach ($data as $k => &$groups) {
            if ($groups["f"] <= $column && $column <= $groups["t"]) {
                foreach ($groups['index'] as $m => &$subGroups) {
                    if ($subGroups["f"] <= $column && $column <= $subGroups["t"]) {
                        foreach ($subGroups['days'] as $p => &$days) {
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
                                            $date['value'][] = $value;
                                            $date['index'][] = $index;
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
                break;
            }
        }
        $this->data = $data;
    }

    public function groups($value, $column)
    {
        $groups = $this->groups;
        if (!isset($groups[$value])) {
            $groups[$value] = [];
            $groups[$value]['f'] = $column;
        } else {
            $groups[$value]['t'] = $column;
        }
        $this->groups = $groups;
        return true;
    }

    public function subGroups($value, $column)
    {
        $groups = $this->groups;
        foreach ($groups as $n => &$group) {
            if ($group["f"] <= $column && $column <= $group["t"]) {
                if (!isset($group['index'][$value])) {
                    $group['index'][$value] = [];
                    $group['index'][$value]['f'] = $column;
                } else {
                    $group['index'][$value]['t'] = $column;
                }
                break;
            }
        }
        $this->groups = $groups;
        return true;
    }

    //name_group, id_subgroup || X, name_day, name_time, lesson by format:
    //Г-316, Математический анализ (лк), доц. Сироткина М.Е.§*
    // физкультура хрен пойми как написана
    // типы пар могут еще быть
    // скобки могут быть в названии
    // подгруппы записаны в ячейках - нахера?
    // подргуппы написаны крива или 1 п/гр или 1 п/г => пока что 1 п/г
    // "С 1 по 8 недели" чо за 
    public function sendTimetable($name_group, $id_subgroup, $name_day, $name_time, $lesson)
    {
        $lesson = str_replace('1 п/г', '',$lesson);
        $lesson = str_replace('2 п/г', '',$lesson);
        $lesson = str_replace('3 п/г', '',$lesson);
        var_dump($lesson);
        return;
        //далее методов нет)
        $id_group = $this->getIdGroup($name_group);
        $id_subgroup = ($id_subgroup == 'X') ? 0 : $id_subgroup;
        $id_day = array_search($name_day, $this->daysName);
        $id_time = array_search($name_time, $this->dates);
        $id_type_week = 0;
        $arr = explode('§', $lesson);
        if (isset($arr[1])) {
            $id_type_week = ($arr[1] == '*') ? 1 : 2 ;
            $lesson = $arr[0];
        }
        
        $arr = explode(',', $lesson);
        $cab = trim($arr[0]);
        $lessonAndTypeLesson = explode('(', trim($arr[1]));
        $idLesson = $this->getIdLesson(trim($lessonAndTypeLesson[0]));
        $idTypeLesson = $this->getIdTypeLesson(str_replace(trim($lessonAndTypeLesson[1]), ')', 1 ));
        $idTeacher = $this->getIdTeacher(trim($arr[2]));

    }
}

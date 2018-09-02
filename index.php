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


class ex
{
    protected $objPHPExcel;
    protected $data;
    protected $groups = ['row' => 0];
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

    public function __construct($file)
    {
        $this->objPHPExcel = PHPExcel_IOFactory::load($file);
        
        foreach ($this->objPHPExcel->getWorksheetIterator() as $worksheet) {
            $this->excel1($worksheet);
        }
        $this->  save ();
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
                    if ($this->groups['row'] == 0 || $this->groups['row'] == $row) {
                        $this->groups($value, $column, $row);
                    }
                    if (intval($this->groups['row']) < $row && $column < 2) {
                        $this->dates($value, $row);
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
            $days[$value]['from'] = (isset($days[$value]['from'])) ? $days[$value]['from'] : $row;
            $days[$value]['to'] = $row;
        }
        if (in_array($value, $this->dates)) {
            foreach ($days as $key => $day) {
                if ($day["from"] <= $row && $row <= $day["to"]) {
                    if (isset($days[$key][$value])) {
                        $days[$key]['data'][$value]['from'] = (isset($days[$key]['data'][$value]['from'])) ? $days[$key]['data'][$value]['from'] : $row;
                        $days[$key]['data'][$value]['to'] = $row;
                    } else {
                        $days[$key]['data'][$value] = [];
                        $days[$key]['data'][$value]['from'] = (isset($days[$key]['data'][$value]['from'])) ? $days[$key]['data'][$value]['from'] : $row;
                        $days[$key]['data'][$value]['to'] = $row;
                    }
                    // break; ??
                }
            }
        }
        $this->days = $days;
    }
    public function lessons($value, $column, $row)
    {
        // var_dump('value: ' . trim($value) . ', column: ' . $column . ', row: ' . $row);
        $groups  = $this->groups;
        $days    = $this->days;
        $lessons = $this->lessons;
        // foreach ($groups["index"] as $m => $group) {
        //     if ($key != '' && $group["from"] <= $column && $column <= $group["to"]) {
        //         foreach ($days as $n => $day) {
        //             if ($day['from'] <= $row && $row <= $day['to']) {
        //                 foreach ($day as $k => $date) {
        //                     if ($key != 'from' && $key != 'to') {
        //                         if ($date['from'] <= $row && $row <= $date['to']) {

        //                             if (!isset($groups["index"][$m]['lesson'])) { 
        //                                 $groups["index"][$m]['lesson'] = $days;
        //                             }
        //                             foreach ($day as $o => $date) {
        //                                 $groups["index"][$m]['lesson'][] = $value . ', column: ' . $column . ', row: ' . $row;
        //                             }
                                    
        //                         }
        //                     }
        //                 }
        //             }
                    
        //         }                    
        //     }
        // }
        $this->lessons = $lessons;
        $this->groups = $groups;
    }

    public function groups($value, $column, $row)
    {
        $groups = $this->groups;
        if ($groups['row'] != 0 && $groups['row'] != $row) {
            return false;
        }
        $value = trim($value);
        $groups['index'][$value]['value'] = (isset($groups['index'][$value])) ? $groups['index'][$value]['value'] : $value;
        $groups['index'][$value]['from'] = (isset($groups['index'][$value]['from'])) ? $groups['index'][$value]['from'] : $column;
        $groups['index'][$value]['to'] = $column;
        if ($groups['row'] == 0) {
            $groups['row'] = $row;
        }
        $this->groups = $groups;

        return true;
    }
}

die();

/*
* Get html table with PHPExcel
*/
$objPHPExcel = PHPExcel_IOFactory::load("20.xls");
$string = '';
foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
    $string .= '<table border="1">';
    $columns_name_line = 0;
    $columns_count = PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());

    $rows_count = $worksheet->getHighestRow();
    for ($row = $columns_name_line + 1; $row <= $rows_count; $row++) {
        $string .= '<tr>';
        // Строка со значениями всех столбцов в строке листа Excel
        $value_str = "";
        // Перебираем столбцы листа Excel
        for ($column = 0; $column < $columns_count; $column++) {
            $merged_value = "";
            // Ячейка листа Excel
            $cell = $worksheet->getCellByColumnAndRow($column, $row);

            // Перебираем массив объединенных ячеек листа Excel
            foreach ($worksheet->getMergeCells() as $mergedCells) {
                // Если текущая ячейка - объединенная,
                if ($cell->isInRange($mergedCells)) {
                    // то вычисляем значение первой объединенной ячейки, и используем её в качестве значения
                    // текущей ячейки
                    $merged_value = $worksheet->getCell(explode(":", $mergedCells)[0])->getCalculatedValue();
                    break;
                }
            }
            $string .= '<td>' . (strlen($merged_value) == 0 ? $cell->getCalculatedValue() : $merged_value) . '</td>';
        }
        $string .= '</tr>';
    }
    $string .= '</table>';
}
echo $string;

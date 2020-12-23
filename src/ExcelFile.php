<?php


namespace CkAmaury\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelFile extends File {

    //####### WRITER #######
    private Spreadsheet $spreadsheet;

    public function __construct(?string $path = null){
        parent::__construct($path);
        $this->spreadsheet = new Spreadsheet();
    }

    public function getActiveSheet() : Worksheet{
        return $this->spreadsheet->getActiveSheet();
    }
    public function getSheet(string $sheetname) : Worksheet{
        return $this->createSheetIfNotExist($sheetname);
    }
    public function getSheetIndex(string $sheetname) : int{
        return $this->spreadsheet->getIndex(
            $this->spreadsheet->getSheetByName($sheetname)
        );
    }

    public function putData(){
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        $this->create();
        $writer->save($this->path);
    }
    public function deleteAllSheets(){
        foreach($this->spreadsheet->getSheetNames() as $sheetName){
            $sheetIndex = $this->getSheetIndex($sheetName);
            $this->deleteSheet($sheetIndex);
        }
    }

    public function insertSpreadheetFromArray(string $sheetname, array $values){
        $sheet = $this->getSheet($sheetname);
        $this->insertValuesWithHeaders($sheet,$values);

        $this->addAutoFilter($sheet);
        $this->stylizedSheet($sheet);
        $this->stylizedHeaders($sheet);
        $this->addAutoSize($sheet);
        $sheet->setSelectedCell('A1');
    }
    /**
     * @param array[][] $data
     * @example [sheet][[header=>value],[header=>value],[header=>value]]
     */
    public function insertMultipleSpreadheetFromArray(array $data){
        $this->deleteAllSheets();

        foreach($data as $sheetname => $values){
            $this->insertSpreadheetFromArray($sheetname,$values);
        }
    }

    private function createSheetIfNotExist(string $search_sheetname) : Worksheet{
        foreach($this->spreadsheet->getSheetNames() as $sheetName){
            if($search_sheetname == $sheetName){
                return $this->spreadsheet->getSheetByName($search_sheetname);
            }
        }
        return $this->createSheet($search_sheetname);
    }
    private function createSheet(string $name) : Worksheet{
        $worksheet = new Worksheet($this->spreadsheet, $name);
        return $this->spreadsheet->addSheet($worksheet);
    }
    private function deleteSheet(int $sheet_index){
        $this->spreadsheet->removeSheetByIndex($sheet_index);
    }

    private function extractHeaders(array $values) : array{
        $headers = array();
        foreach($values as $value){
            $headers = array_unique(array_merge($headers,array_keys($value)));
        }

        $i = 1;
        $formatted_headers = array();
        foreach($headers as $key => $header){
            $formatted_headers[$i++] = $header;
        }
        return $formatted_headers;
    }
    private function insertValuesWithHeaders(Worksheet $sheet, array $values){
        $headers = $this->extractHeaders($values);
        $row_index = 1;
        foreach($headers as $column_index => $header){
            $sheet->getCellByColumnAndRow($column_index, $row_index)->setValue(strtoupper($header));
        }
        $row_index++;
        foreach($values as $row){
            foreach($headers as $column_index => $header){
                if(isset($row[$header])){
                    $sheet->getCellByColumnAndRow($column_index, $row_index)->setValue($row[$header]);
                }
            }
            $row_index++;
        }
    }

    private function addAutoFilter(Worksheet $sheet){
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
    }
    private function addAutoSize(Worksheet $sheet){

        $columns_array = array();
        foreach(range('A','Z') as $letter) {
            $columns_array[] = $letter;
        }
        foreach(range('A','Z') as $letter1) {
            foreach(range('A','Z') as $letter2) {
                $columns_array[] = $letter1.$letter2;
            }
        }
        foreach($columns_array as $columnID) {
            $length = strlen($sheet->getCell($columnID.'1')->getValue());
            $width_px = 55 + $length * 10;
            $width = $width_px / 7;
            $sheet->getColumnDimension($columnID)->setWidth($width);
            $sheet->getRowDimension(1)->setRowHeight(30);
            if($columnID == $sheet->getHighestDataColumn()) break;
        }


    }
    private function stylizedHeaders(Worksheet $sheet){
        $sheet->freezePane('A2');
        $styleArray = array(
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => array('argb' => '00000000'),
                ),
            ),
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'FF4F81BD')
            )
        );
        $this->getRowStyle($sheet,1)->applyFromArray($styleArray);
    }
    private function stylizedSheet(Worksheet $sheet){
        $styleArray = array(
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => array('argb' => '00000000'),
                ),
                'allBorders' => array(
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => array('argb' => '00000000'),
                ),
            )
        );
        $sheet->getStyle($this->getAllCoordinate($sheet))->applyFromArray($styleArray);
    }
    private function getRowStyle(Worksheet $sheet, string $rowNumber){
        return $sheet->getStyle($this->getRowCoordinate($sheet,$rowNumber));
    }

    private function getRowCoordinate(Worksheet $sheet, string $rowNumber) : string{
        $from = 'A'.$rowNumber;
        $to = $sheet->getHighestColumn().$rowNumber;
        return "$from:$to";
    }
    private function getAllCoordinate(Worksheet $sheet) : string{
        $from = 'A1';
        $to = $sheet->getHighestDataColumn().$sheet->getHighestDataRow();
        return "$from:$to";
    }

    //####### READER #######
    public function getData() : array{
        $reader = new Xlsx();
        $spreadsheet = $reader->load($this->getPath());

        $return = array();
        for($i = 0;$i < $spreadsheet->getSheetCount();$i++){
            $return[] = $this->extractSheetDataByNumber($spreadsheet,$i);
        }

        return $return;
    }

    public function extractSheetDataByNumber($spreadsheet, int $number) : array{
        $sheet = $spreadsheet->getSheet($number);
        return $this->extractSheetData($sheet);
    }
    public function extractSheetDataByName($spreadsheet, string $name) : array{
        $sheet = $spreadsheet->getSheetByName($name);
        return $this->extractSheetData($sheet);
    }
    public function extractSheetData($sheet) : array{
        return ($sheet->toArray(null, true, true, true));
    }
}
<?php


namespace CkAmaury\Spreadsheet;

class FileReader extends File {

    /**
     * @var resource|false|null
     */
    protected $handle = null;

    public function __destruct(){
        $this->close();
    }

    public function open(){
        $this->close();
        $this->handle = fopen($this->path, "r");
    }
    public function close(){
        if(!is_null($this->handle)){
            fclose($this->handle);
        }
    }

    public function getContents(){
        return file_get_contents($this->path, true);
    }

}
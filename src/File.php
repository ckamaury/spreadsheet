<?php


namespace CkAmaury\Spreadsheet;

class File {

    protected string $path;

    protected $fileputter = null;

    /**
     * @var resource|false|null
     */
    protected $handle = null;

    private CONST CHMOD_FOLDER = 0755;
    private CONST CHMOD_FILE = 0770;

    public function __construct(?string $path = null){
        if(!is_null($path)){
            $this->path = $path;
        }
    }
    public function __destruct(){
        $this->closeReader();
    }

    public function setPath($path) : self{
        $this->path = $path;
        return $this;
    }
    public function getPath(): string {
        return $this->path;
    }
    public function getName(): string {
        return basename($this->path);
    }

    public function isExisting() : bool{
        return file_exists($this->path);
    }
    public function isReadable() : bool{
        return is_readable($this->path);
    }

    public function create(){
        if(!$this->isExisting()){
            $this->checkAndCreateFolder();
            file_put_contents($this->path,'');
            chmod($this->path,self::CHMOD_FILE);
        }
    }
    public function rename(string $newPath){
        $oldPath = $this->path;
        $this->changePath($newPath);
        rename($oldPath,$this->path);
    }
    public function copy(string $newPath) : File{
        $new_file = new File();
        $new_file
            ->setPath($newPath)
            ->create();
        copy($this->path,$newPath);
        return $new_file;
    }
    public function delete(){
        unlink($this->path);
    }

    private function checkAndCreateFolder(){
        $level = $this->searchLevelOfFolderExist();
        $this->createFoldersInCascade($level);
    }
    private function searchLevelOfFolderExist() : int{
        $level = 0;
        do{
            $level++;
            $dirname = dirname($this->path,$level);
        }while(!is_dir($dirname));
        return $level;
    }
    private function createFoldersInCascade($level){
        for($level--;$level > 0;$level--){
            $dirname = dirname($this->path,$level);
            mkdir($dirname,self::CHMOD_FOLDER);
        }
    }

    private function changePath(string $newPath){
        $this->setPath($newPath);
        $this->checkAndCreateFolder();
    }

    //####### WRITER #######
    public function openWriter(){
        $this->fileputter = fopen($this->getPath(), 'w');
    }
    public function closeWriter(){
        if(!is_null($this->fileputter)){
            fclose($this->fileputter);
            $this->fileputter = null;
        }
    }
    public function putContents($text,int $flags = 0){
        file_put_contents($this->path, $text, $flags);
    }

    //####### READER #######
    public function openReader(){
        $this->closeReader();
        $this->handle = fopen($this->path, "r");
    }
    public function closeReader(){
        if(!is_null($this->handle)){
            fclose($this->handle);
            $this->handle = null;
        }
    }
    public function getContents(){
        return file_get_contents($this->path, true);
    }
    public function getData() : array{
        return array();
    }
    public function getDataWithHeaders() : array{
        $data = $this->getData();
        $this->replaceKeysByFirstLine($data);
        return $data;
    }

    //####### GLOBAL FUNCTIONS #######
    protected function replaceKeysByFirstLine(array &$array){
        $headers = array_shift($array);
        foreach($array as &$row){
            $this->replaceKeys($row,$headers);
        }
    }
    protected function replaceKeys(array &$row,array $headers){
        foreach($row as $old_key => $value){
            $new_key = $headers[$old_key];
            $row[$new_key] = $value;
            unset($row[$old_key]);
        }
    }
}
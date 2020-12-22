<?php


namespace CkAmaury\Spreadsheet;

class File {

    protected string $path;

    private CONST CHMOD_FOLDER = 0755;
    private CONST CHMOD_FILE = 0770;

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
}
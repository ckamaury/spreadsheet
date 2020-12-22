<?php

namespace CkAmaury\SpreadsheetTests;

use CkAmaury\Spreadsheet\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase {

    private function getTempPath(){
        return getcwd().'\temp\\';
    }

    public function testBasicFunctionsFile(): void{
        $file = new File();
        $file_name = 'test.txt';
        $file_path = $this->getTempPath().$file_name;
        $file->setPath($file_path);
        $file->create();
        self::assertTrue($file->isExisting());
        self::assertFileExists($file_path);
        self::assertSame($file->getName(),$file_name);

        $file_copy_path = $this->getTempPath().'test2.txt';
        $file_copy = $file->copy($file_copy_path);
        self::assertFileExists($file_path);
        self::assertTrue($file->getPath() == $file_path);

        self::assertFileExists($file_path);
        self::assertTrue($file_copy->getPath() == $file_copy_path);

        $file->delete();
        self::assertFileDoesNotExist($file_path);
        self::assertFileExists($file_copy_path);

        $file_copy->rename($file_path);
        self::assertFileExists($file_path);
        self::assertFileDoesNotExist($file_copy_path);

        $file_copy->delete();
        self::assertFileDoesNotExist($file_path);

        rmdir($this->getTempPath());
    }

}

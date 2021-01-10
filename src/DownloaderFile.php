<?php


namespace CkAmaury\Spreadsheet;

class DownloaderFile extends File {

    public function download(string $url){
        $file_path = $this->path;

        $contextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $contents = file_get_contents(
            $url,
            false,
            stream_context_create($contextOptions)
        );

        $is_success = file_put_contents($file_path,$contents);

        if(!$is_success) {
            throw new \Exception('Downloading is unsuccessful.');
        }
    }
}
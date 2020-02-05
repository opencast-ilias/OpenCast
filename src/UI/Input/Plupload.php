<?php

namespace srag\Plugins\Opencast\UI\Input;

use ilException;
use ilUtil;
use xoctException;

/**
 * Class Plupload
 *
 * @package srag\Plugins\Opencast\UI\Input
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Plupload
{

    /**
     * @var bool
     */
    protected $finished = false;
    /**
     * @var string
     */
    protected $target_dir = '';
    /**
     * @var string
     */
    protected $file_path = '';
    /**
     * @var bool
     */
    protected $clean_up = false;


    /**
     * Plupload constructor.
     */
    public function __construct() {
        $this->setTargetDir(ilUtil::getDataDir() . "/temp/plupload");
    }


    /**
     * @return boolean
     */
    public function isFinished() {
        return $this->finished;
    }


    /**
     * @param boolean $finished
     */
    public function setFinished($finished) {
        $this->finished = $finished;
    }


    /**
     * @return string
     */
    public function getFilePath() {
        return $this->file_path;
    }


    /**
     * @param string $file_path
     */
    public function setFilePath($file_path) {
        $this->file_path = $file_path;
    }


    /**
     * @return string
     */
    public function getTargetDir() {
        return $this->target_dir;
    }


    /**
     * @param string $target_dir
     */
    public function setTargetDir($target_dir) {
        $this->target_dir = $target_dir;
    }


    /**
     * @return boolean
     */
    public function isCleanUp() {
        return $this->clean_up;
    }


    /**
     * @param boolean $clean_up
     */
    public function setCleanUp($clean_up) {
        $this->clean_up = $clean_up;
    }


    public function handleUpload() {
        $this->setHeaders();

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // Settings
        $targetDir = $this->getTargetDir();

        //$targetDir = 'uploads';
        $cleanupTargetDir = $this->isCleanUp(); // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds

        // Create target dir
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                throw new ilException('Could not create directory');
            }
        }

        // Get a file name
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        global $DIC;
        $ilLog = $DIC['ilLog'];
        $ilLog->write('plupload chunks');
        $ilLog->write($filePath);
        $this->setFilePath($filePath);

        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}.part") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }

        // Return Success JSON-RPC response

        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    }


    protected function setHeaders() {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
}



/**
 * Class xoctPluploadException
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctPluploadException extends xoctException {

    /**
     * @param string $code
     * @param string $additional_message
     */
    public function __construct($code, $additional_message) {
        parent::__construct($code, $additional_message);
    }
}
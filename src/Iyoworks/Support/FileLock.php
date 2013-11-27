<?php namespace Iyoworks\Support;

class FileLock {

    private $handle;

    public static function read ( $handle ) {
        $lock = new static();
        $lock->handle = $handle;
        return flock($handle,LOCK_SH) ? $lock : false;
    }

    public static function write ( $handle ) {
        $lock = new static();
        $lock->handle = $handle;
        return flock($handle,LOCK_EX) ? $lock : false;
    }

    public function __destruct ( ) {
        flock($this->handle,LOCK_UN);
    }

}
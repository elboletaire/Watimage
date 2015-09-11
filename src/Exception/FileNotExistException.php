<?php
namespace Elboletaire\Watimage\Exception;

class FileNotExistException extends \Exception
{
    public function __construct($file, $code = 0, \Exception $previous = null)
    {
        return parent::__construct(
            "Specified {$file} does not exist.",
            $code,
            $previous
        );
    }
}

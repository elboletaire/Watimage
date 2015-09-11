<?php
namespace Elboletaire\Watimage\Exception;

class InvalidExtensionException extends \Exception
{
    public function __construct($extension, $code = 0, \Exception $previous = null)
    {
        return parent::__construct("Extension .{$extension} not allowed.", $code, $previous);
    }
}

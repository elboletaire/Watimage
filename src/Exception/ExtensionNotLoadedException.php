<?php
namespace Elboletaire\Watimage\Exception;

class ExtensionNotLoadedException extends \Exception
{
    public function __construct($extension, $code = 0, \Exception $previous = null)
    {
        return parent::__construct(
            "PHP {$extension} extension is required by Watimage but it's not loaded.",
            $code,
            $previous
        );
    }
}

<?php
namespace Elboletaire\Watimage\Exception;

class InvalidMimeException extends \Exception
{
    public function __construct($mime, $code = 0, \Exception $previous = null)
    {
        return parent::__construct(
            "Mime type \"{$mime}\" not allowed or not recognised.",
            $code,
            $previous
        );
    }
}

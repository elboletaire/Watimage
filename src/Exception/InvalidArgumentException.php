<?php
namespace Elboletaire\Watimage\Exception;

class InvalidArgumentException extends \Exception
{
    public function __construct($message, $args = null, $code = 0, \Exception $previous = null)
    {
        if (!empty($args)) {
            if (is_array($args)) {
                $args = json_encode($args);
            }
            $message = sprintf($message, $args);
        }

        return parent::__construct($message, $code, $previous);
    }
}

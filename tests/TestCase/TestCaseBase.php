<?php
namespace Elboletaire\Watimage\Test\TestCase;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

abstract class TestCaseBase extends PHPUnit_Framework_TestCase
{
    protected $testClass;

    protected $reflection;

    protected $files_path;

    public function setUp()
    {
        $this->reflection = new ReflectionClass($this->testClass);

        $this->files_path = realpath(dirname(__FILE__) . '/../../examples/files');
        $this->files_path .= DIRECTORY_SEPARATOR;

        $this->output_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    }

    public function getMethod($method)
    {
        $method = $this->reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    public function getProperty($property)
    {
        $property = $this->reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($this->testClass);
    }

    public function setProperty($property, $value)
    {
        $property = $this->reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->setValue($this->testClass, $value);
    }

    public function getOutputFilename($filename)
    {
        $filename = pathinfo($filename);
        $random = substr(md5(time()), 0, 10);
        return "{$this->output_path}watimage-{$filename['filename']}-{$random}.{$filename['extension']}";
    }
}

<?php
namespace Elboletaire\Watimage\Test\TestCase;

use Elboletaire\Watimage\Watimage;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class WatimageTest extends PHPUnit_Framework_TestCase
{
    private $files_path;

    public function setUp()
    {
        $this->watimage   = new Watimage;
        $this->reflection = new ReflectionClass($this->watimage);

        $this->files_path = realpath(dirname(__FILE__) . '/../visual/files');
        $this->files_path .= DIRECTORY_SEPARATOR;
    }

    public function testSetWatermark()
    {
        $watermark = "{$this->files_path}/watermark.png";

        // Checking default values passing a string
        $this->watimage->setWatermark($watermark);

        $watermark_resource = $this->getProperty('watermark');
        $metadata = $watermark_resource['metadata'];
        $options = $watermark_resource['options'];

        $this->assertArrayHasKey('file', $options);
        $this->assertEquals($watermark, $options['file']);

        $this->assertEquals('100%', $options['size']);
        $this->assertEquals('bottom right', $options['position']);
        $this->assertArraySubset(['margin' => ['x' => 0, 'y' => 0]], $options);

        // Test the different ways of passing the margin...
        $expected = [
            'margin' => [
                'x' => 23,
                'y' => 23
            ]
        ];

        // Using an integer (no array)
        $this->watimage->setWatermark([
            'file'   => $watermark,
            'margin' => 23
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an integer (in an array)
        $this->watimage->setWatermark([
            'file'   => $watermark,
            'margin' => [23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an integer (in an array)
        $this->watimage->setWatermark([
            'file'   => $watermark,
            'margin' => [23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using a sequential array
        $this->watimage->setWatermark([
            'file'   => $watermark,
            'margin' => [23, 23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an associative array
        $this->watimage->setWatermark([
            'file'   => $watermark,
            'margin' => ['x' => '23', 'y' => '23']
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);
    }


    /**
     * Returns protected/private values from the tested class.
     *
     * @param  [type] $property [description]
     * @return [type]           [description]
     */
    public function getProperty($property)
    {
        $property = $this->reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($this->watimage);
    }
}

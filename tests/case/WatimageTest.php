<?php
namespace Elboletaire\Watimage\Test\TestCase;

use Elboletaire\Watimage\Watimage;

class WatimageTest extends TestCaseBase
{
    public function setUp()
    {
        $this->testClass   = new Watimage;

        parent::setUp();
    }

    public function testSetWatermark()
    {
        $watermark = "{$this->files_path}/watermark.png";

        // Checking default values passing a string
        $this->testClass->setWatermark($watermark);

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
        $this->testClass->setWatermark([
            'file'   => $watermark,
            'margin' => 23
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an integer (in an array)
        $this->testClass->setWatermark([
            'file'   => $watermark,
            'margin' => [23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an integer (in an array)
        $this->testClass->setWatermark([
            'file'   => $watermark,
            'margin' => [23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using a sequential array
        $this->testClass->setWatermark([
            'file'   => $watermark,
            'margin' => [23, 23]
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);

        // Using an associative array
        $this->testClass->setWatermark([
            'file'   => $watermark,
            'margin' => ['x' => '23', 'y' => '23']
        ]);

        $watermark_resource = $this->getProperty('watermark');
        $options = $watermark_resource['options'];

        $this->assertArraySubset($expected, $options);
    }
}

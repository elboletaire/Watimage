<?php
namespace Elboletaire\Watimage\Test\TestCase;

use Elboletaire\Watimage\Image;

class ImageTest extends TestCaseBase
{
    public function setUp()
    {
        $this->testClass = new Image;

        parent::setUp();
    }

    public function testLoad()
    {
        $image = "{$this->files_path}/image.jpg";

        $this->testClass->load($image);

        // Check filename has been properly loaded
        $this->assertEquals($image, $this->getProperty('filename'));

        // Check gd resource has been created
        $this->assertEquals('gd', get_resource_type($this->getProperty('image')));

        // Check for metadata
        $this->assertNotEmpty($this->testClass->getMetadata());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGenerate()
    {
        $image = "{$this->files_path}/image.png";
        $output = "{$this->output_path}/test-watimage-generate.png";
        @unlink($output);

        // Check that image can be generated and printed to screen
        ob_start();
        $this->testClass->load($image)->generate();
        $buffer = ob_get_contents();
        ob_end_clean();
        $this->assertNotEmpty($buffer);

        // Check output
        $this->assertFileNotExists($output);
        $this->testClass->load($image)->generate($output);
        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testRotate()
    {
        // We know this image has portrait orientation
        $image = "{$this->files_path}/image.png";
        $output = "{$this->output_path}/test-watimage-rotate.png";
        @unlink($output);

        // Check rotation image size
        $this->testClass->load($image)->rotate(90)->generate($output);
        list($width, $height) = getimagesize($output);
        // Knowing rotation, check width according to it
        $this->assertLessThanOrEqual($this->getProperty('width'), $height);
        $this->assertGreaterThan($this->getProperty('width'), $width);
        // Knowing rotation, check height according to it
        $this->assertLessThanOrEqual($this->getProperty('height'), $width);
        $this->assertLessThan($this->getProperty('height'), $height);
    }
}

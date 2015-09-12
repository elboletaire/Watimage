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
        $image = "{$this->files_path}/peke.jpg";

        $this->testClass->load($image);

        // Check filename has been properly loaded
        $this->assertEquals($image, $this->getProperty('filename'));

        // Check gd resource has been created
        $this->assertEquals('gd', get_resource_type($this->getProperty('image')));

        // Check for metadata
        $this->assertNotEmpty($this->testClass->getMetadata());
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testLoadArgumentsFail()
    {
        $this->testClass->load(null);
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\FileNotExistException
     */
    public function testLoadFileNotExistFail()
    {
        $this->testClass->load('a-non-existant-file.png');
    }

    public function testCreate()
    {
        $output = "{$this->output_path}/test-watimage-create.png";
        $this->testClass->create(250, 400);

        $this->assertEquals(250, $this->getProperty('width'));
        $this->assertEquals(400, $this->getProperty('height'));

        // Check that height is set to width when no height specified
        $this->testClass->create(350);
        $this->assertEquals(350, $this->getProperty('height'));
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testCreateArgumentsFail()
    {
        $this->testClass->create(null);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGenerate()
    {
        $image = "{$this->files_path}/test.png";
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
        $image = "{$this->files_path}/test.png";
        $output = "{$this->output_path}/test-watimage-rotate.png";
        @unlink($output);

        // Check rotation image size
        $image = $this->testClass->load($image);
        // Get current width and height
        $old_width = $this->getProperty('width');
        $old_height = $this->getProperty('height');
        // Rotate it
        $image->rotate(90)->generate($output);
        list($width, $height) = getimagesize($output);
        // Knowing rotation, check width according to it
        $this->assertLessThanOrEqual($old_width, $height);
        $this->assertGreaterThan($old_width, $width);
        // Knowing rotation, check height according to it
        $this->assertLessThanOrEqual($old_height, $width);
        $this->assertLessThan($old_height, $height);
    }

    public function testClassicResize()
    {
        $image = "{$this->files_path}/test.png";
        $output = "{$this->output_path}/image-classic-resize.png";
        @unlink($output);

        // Test types fallback
        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $this->testClass->classicResize(200, 300)->generate($output);
        list($width, $height) = getimagesize($output);

        $this->assertEquals(182, $width);
        $this->assertEquals(300, $height);
    }
}

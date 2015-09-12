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
        $this->testClass->create(250, 400);

        $this->assertEquals(250, $this->getProperty('width'));
        $this->assertEquals(400, $this->getProperty('height'));

        // Check that height is set to width when no height specified
        $this->testClass->create(350);
        $this->assertEquals(350, $this->getProperty('height'));

        $this->assertEquals('gd', get_resource_type($this->testClass->getImage()));
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
        $output = $this->getOutputFilename("image-generate.png");

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

    public function testSave()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-save.png");

        $this->assertFileNotExists($output);
        $this->testClass->load($image)->flip()->save($output);
        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
        // Check that it overrides original file
        $original_size = filesize($output);
        $this->testClass->load($output)->resize('min', 50)->save();
        // We need to clear file status cache to get correct sizes
        clearstatcache();
        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
        $this->assertNotEquals($original_size, filesize($output));
    }

    public function testAutoOrientate()
    {
        $image = "{$this->files_path}/tripi.jpg";
        $output = $this->getOutputFilename("image-auto-orientate.jpg");

        // I know that image must be rotated, so...
        // disable auto orientate to manually do it..
        $this->testClass->load($image, false);
        $original_metadata = $this->testClass->getMetadata();
        // auto orientate and save
        $this->testClass->autoOrientate()->save($output);
        // get new image size
        list($width, $height) = getimagesize($output);
        $this->assertEquals($original_metadata['width'], $height);
        $this->assertEquals($original_metadata['height'], $width);
    }

    public function testRotate()
    {
        // We know this image has portrait orientation
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-rotate.png");

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

    public function testResize()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-resize.png");

        $types = [
            'classic',
            'resize',
            'reduce',
            'resizemin',
            'min',
            'crop',
            'resizecrop'
        ];

        // Test types fallback
        $this->testClass->load($image);

        // We're just gonna check that it does not crash.
        // Every method is tested in its proper test method.
        foreach ($types as $type) {
            $this->testClass->resize($type, 200);
        }

        $this->testClass->generate($output);
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testResizeFail()
    {
        $image = "{$this->files_path}/test.png";

        $this->testClass->load($image)->resize('fail', 'fail');
    }

    public function testClassicResize()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-classic-resize.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $this->testClass->classicResize(200, 300)->generate($output);
        list($width, $height) = getimagesize($output);

        $this->assertEquals(182, $width);
        $this->assertEquals(300, $height);
    }

    public function testReduce()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-resizemin.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $this->testClass->resizeMin(200, 300)->generate($output);
        list($width, $height) = getimagesize($output);

        $this->assertEquals(182, $width);
        $this->assertEquals(300, $height);
    }

    public function testClassicCrop()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-classic-crop.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $this->testClass->classicCrop(200, 200)->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertNotEquals($metadata['width'], $width);
        $this->assertNotEquals($metadata['height'], $height);
        $this->assertEquals(200, $width);
        $this->assertEquals(200, $height);
    }

    public function testResizeCrop()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-resize-crop.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $this->testClass->resizeCrop(250, 250)->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertNotEquals($metadata['width'], $width);
        $this->assertNotEquals($metadata['height'], $height);
        // Check current size
        $this->assertEquals(250, $width);
        $this->assertEquals(250, $height);
    }
}

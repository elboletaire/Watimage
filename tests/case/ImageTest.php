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

    public function testNormalizeCropArguments()
    {
        $expected = [23, 32, 200, 150];

        $parseCropOptions = $this->getMethod('normalizeCropArguments');
        // Passing multiple arguments
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass,
                // x, y, width, height
                23, 32, 200, 150
            )
        );
        // Passing an array
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    // x, y, width, height
                    23, 32, 200, 150
                ]
            )
        );
        // Passing an associative array
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    'x'      => 23,
                    'y'      => 32,
                    'width'  => 200,
                    'height' => 150
                ]
            )
        );
        // Passing a simplified associative array
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    'x' => 23,
                    'y' => 32,
                    'w' => 200,
                    'h' => 150
                ]
            )
        );
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testFailNormalizeCropArguments()
    {
        $parseCropOptions = $this->getMethod('normalizeCropArguments');

        $parseCropOptions->invoke($this->testClass, 23);
    }

    public function testNormalizeResizeArguments()
    {
        $expected = [250, 320];

        $parseCropOptions = $this->getMethod('normalizeResizeArguments');
        // Passing multiple arguments
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass,
                // width, height
                250, 320
            )
        );
        // Passing an array
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    // width, height
                    250, 320
                ]
            )
        );
        // Passing an associative array
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    'width'  => 250,
                    'height' => 320
                ]
            )
        );
        // Passing simplified associative arrays
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    'w' => 250,
                    'h' => 320
                ]
            )
        );
        $this->assertArraySubset(
            $expected,
            $parseCropOptions->invoke(
                $this->testClass, [
                    'x' => 250,
                    'y' => 320
                ]
            )
        );
        // Passing just width (should return same height)
        $this->assertArraySubset(
            [250, 250],
            $parseCropOptions->invoke(
                $this->testClass,
                250
            )
        );
        $this->assertArraySubset(
            [250, 250],
            $parseCropOptions->invoke(
                $this->testClass,
                ['width' => 250]
            )
        );
    }

    public function testRotate()
    {
        // We know this image has portrait orientation
        $image = "{$this->files_path}/image.png";
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
}

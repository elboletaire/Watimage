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

        $instance = $this->testClass->load($image);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);

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
        $image = $this->testClass->create(250, 400);
        // Check instance
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $image);
        // Check size
        $this->assertEquals(250, $this->getProperty('width'));
        $this->assertEquals(400, $this->getProperty('height'));
        // Check it again
        $resource = $this->testClass->getImage();
        $this->assertEquals(250, imagesx($resource));
        $this->assertEquals(400, imagesy($resource));

        // Check that height is set to width when no height specified
        $this->testClass->create(350);
        $this->assertEquals(350, $this->getProperty('width'));
        $this->assertEquals(350, $this->getProperty('height'));

        $this->assertEquals('gd', get_resource_type($resource));

        $metadata = $this->testClass->getMetadata();
        $this->assertNull($metadata['exif']);
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

        // Generate saving to file
        $image = $this->testClass->load($image)->generate($output);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $image);
        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testSave()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-save.png");

        $this->assertFileNotExists($output);
        $image = $this->testClass->load($image)->flip()->save($output);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $image);
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
        // auto orientate
        $instance = $this->testClass->autoOrientate();
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        // and save
        $instance->save($output);
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
        $instance = $image->rotate(90);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        // Knowing rotation, check width according to it
        $this->assertLessThanOrEqual($old_width, $height);
        $this->assertGreaterThan($old_width, $width);
        // Knowing rotation, check height according to it
        $this->assertLessThanOrEqual($old_height, $width);
        $this->assertLessThan($old_height, $height);
    }

    /**
     * @covers Elboletaire\Watimage\Image::resize
     * @group  resize
     */
    public function testResize()
    {
        $image = "{$this->files_path}/peke.jpg";
        $output = $this->getOutputFilename("image-resize.jpg");

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
            $instance = $this->testClass->resize($type, 200);
            $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
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

    /**
     * @group  resize
     */
    public function testClassicResize()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-classic-resize.png");

        $this->testClass->load($image);
        $instance = $this->testClass->classicResize(200, 300);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);

        // Check that the current size corresponds to the defined one
        list($width, $height) = getimagesize($output);
        $this->assertEquals(182, $width);
        $this->assertEquals(300, $height);

        // Check that the size of the image has been updated
        $this->assertEquals(182, $this->getProperty('width'));
        $this->assertEquals(300, $this->getProperty('height'));
    }

    /**
     * @group  resize
     */
    public function testReduce()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-resizemin.png");

        $this->testClass->load($image);
        $instance = $this->testClass->reduce(200, 300);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);

        // Check that the current size corresponds to the defined one
        list($width, $height) = getimagesize($output);
        $this->assertEquals(182, $width);
        $this->assertEquals(300, $height);

        // Check that the size of the image has been updated
        $this->assertEquals(182, $this->getProperty('width'));
        $this->assertEquals(300, $this->getProperty('height'));
    }

    /**
     * @group  resize
     */
    public function testClassicCrop()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-classic-crop.png");

        $this->testClass->load($image);
        $instance = $this->testClass->classicCrop(200, 250);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);

        // Check that the current size corresponds to the defined one
        list($width, $height) = getimagesize($output);
        $this->assertEquals(200, $width);
        $this->assertEquals(250, $height);

        // Check that the size of the image has been updated
        $this->assertEquals(200, $this->getProperty('width'));
        $this->assertEquals(250, $this->getProperty('height'));
    }

    /**
     * @group  resize
     */
    public function testResizeCrop()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-resize-crop.png");

        $this->testClass->load($image);
        $instance = $this->testClass->resizeCrop(200, 250);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);

        // Check that the current size corresponds to the defined one
        list($width, $height) = getimagesize($output);
        $this->assertEquals(200, $width);
        $this->assertEquals(250, $height);

        // Check that the size of the image has been updated
        $this->assertEquals(200, $width);
        $this->assertEquals(250, $height);
    }

    /**
     * @group  effects
     */
    public function testFlip()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-flip.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $instance = $this->testClass->flip('horizontal');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);

        $instance = $this->testClass->flip('vertical');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);

        $instance = $this->testClass->flip('both');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);
    }

    /**
     * @group  effects
     */
    public function testConvenienceFlip()
    {
        $image = "{$this->files_path}/test.png";
        $output = $this->getOutputFilename("image-convenience-flip.png");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        $instance = $this->testClass->convenienceFlip('horizontal');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);

        $instance = $this->testClass->convenienceFlip('vertical');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);

        $instance = $this->testClass->convenienceFlip('both');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        list($width, $height) = getimagesize($output);
        $this->assertEquals($metadata['width'], $width);
        $this->assertEquals($metadata['height'], $height);
    }

    public function testFill()
    {
        // Create a 1px x 1px canvas
        $image = $this->testClass->create(1, 1);
        // Fill it with red
        $instance = $image->fill('#f00');
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        // Get color at that unique pixel
        $resource = $image->getImage();
        $color = imagecolorsforindex($resource, imagecolorat($resource, 0, 0));
        // Assert is red
        $this->assertArraySubset([
            'red'   => 255,
            'green' => 0,
            'blue'  => 0,
            'alpha' => 0
        ], $color);
    }

    public function testCrop()
    {
        $image = "{$this->files_path}/peke.jpg";
        $output = $this->getOutputFilename("image-crop.jpg");

        $this->testClass->load($image);
        $metadata = $this->testClass->getMetadata();
        // Get color index at crop position
        $resource = $this->testClass->getImage();
        $color = imagecolorsforindex($resource, imagecolorat($resource, 500, 500));
        // Crop
        $instance = $this->testClass->crop(500, 500, 100, 150);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $instance->generate($output);
        // Get color index at cropped position and compare
        $resource = $this->testClass->getImage();
        $new_color = imagecolorsforindex($resource, imagecolorat($resource, 0, 0));
        $this->assertArraySubset($color, $new_color);
        // Compare size
        list($width, $height) = getimagesize($output);
        $this->assertNotEquals($metadata['width'], $width);
        $this->assertNotEquals($metadata['height'], $height);
        $this->assertEquals(100, $width);
        $this->assertEquals(150, $height);
    }

    /**
     * The blur method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testBlur()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->blur();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     * @group  effects
     */
    public function testBlurFail()
    {
        $this->testClass->create(250, 250)->fill('#f00')->blur('fail');
    }

    /**
     * The brightness method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testBrightness()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->brightness(23);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The colorize method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testColorize()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->brightness(23);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The contrast method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testContrast()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->contrast(23);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The edgeDetection method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testEdgeDetection()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->edgeDetection();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The emboss method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testEmboss()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->emboss();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The grayscale method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testGrayscale()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->grayscale();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The meanRemove method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testMeanRemove()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->meanRemove();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The negate method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testNegate()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->negate();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The pixelate method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testPixelate()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->pixelate();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The sepia method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testSepia()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->sepia();

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The smooth method test.
     *
     * In all effects methods I can only test for the returning value (as they're
     * php core features).
     *
     * @return void
     * @group  effects
     */
    public function testSmooth()
    {
        $image = "{$this->files_path}/peke.jpg";

        $image = $this->testClass->load($image);
        $instance = $image->smooth(5);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
    }

    /**
     * The vignette method test.
     *
     * @return void
     * @group  effects
     * @group  slow
     * @covers Elboletaire\Watimage\Image::vignette
     * @covers Elboletaire\Watimage\Image::vignetteEffect
     */
    public function testVignette()
    {
        $image = "{$this->files_path}/peke.jpg";

        $instance = $this->testClass->load($image);
        // Let's create a very dark vignette to check if borders are black
        $instance = $instance->vignette(10, 1);

        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);

        $resource = $instance->getImage();
        $color = imagecolorsforindex($resource, imagecolorat($resource, 0, 0));

        $this->assertArraySubset([
            'red' => 0,
            'green' => 0,
            'blue' => 0,
            'alpha' => 0
        ], $color);
    }

    public function testSetImage()
    {
        $image = "{$this->files_path}/peke.jpg";
        $resource = imagecreatefromjpeg($image);

        $instance = $this->testClass->setImage($resource);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);

        $this->setExpectedException('Exception');
        $this->testClass->setImage("{$this->files_path}/peke.jpg");
    }

    public function testGetMetadataFromFile()
    {
        $image = "{$this->files_path}/peke.jpg";

        $expected = [
            'width' => 1944,
            'height' => 1296,
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'exif' => null // we're not testing exif functions
        ];

        $metadata = $this->testClass->getMetadataFromFile($image);
        // unset exif
        $metadata['exif'] = null;
        $this->assertArraySubset($expected, $metadata);
    }

    public function testGetMimeFromExtension()
    {
        $method = $this->getMethod('getMimeFromExtension');

        $this->assertEquals('image/jpeg', $method->invoke($this->testClass, 'image.jpg'));
        $this->assertEquals('image/jpeg', $method->invoke($this->testClass, 'image.jpeg'));
        $this->assertEquals('image/gif', $method->invoke($this->testClass, 'image.gif'));
        $this->assertEquals('image/png', $method->invoke($this->testClass, 'image.png'));

        $this->setExpectedException('Elboletaire\Watimage\Exception\InvalidExtensionException');
        $method->invoke($this->testClass, 'image.bmp');
    }

    public function testDestroy()
    {
        $image = "{$this->files_path}/peke.jpg";
        $instance = $this->testClass->load($image);

        $this->assertNotNull($this->getProperty('filename'));
        $this->assertNotNull($this->getProperty('width'));
        $this->assertNotNull($this->getProperty('height'));

        $instance = $instance->destroy();
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);

        $this->assertArraySubset([], $this->getProperty('metadata'));
        $this->assertNull($this->getProperty('filename'));
        $this->assertNull($this->getProperty('width'));
        $this->assertNull($this->getProperty('height'));
    }
}

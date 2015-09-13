<?php
namespace Elboletaire\Watimage\Test\TestCase;

use Elboletaire\Watimage\Image;
use Elboletaire\Watimage\Watermark;

class WatermarkTest extends TestCaseBase
{
    public function setUp()
    {
        $this->testClass = new Watermark();

        parent::setUp();

        $this->testClass->load("{$this->files_path}/watermark.png");
    }

    public function testDestroy()
    {
        $instance = $this->testClass
            ->setSize("150%")
            ->setPosition("centered")
            ->setMargin(20)
            ->destroy()
        ;

        $this->assertInstanceOf('Elboletaire\Watimage\Watermark', $instance);

        $this->assertNull($this->getProperty('position'));
        $this->assertNull($this->getProperty('size'));
        $this->assertArraySubset([0, 0], $this->getProperty('margin'));
    }

    /**
     * @covers Elboletaire\Watimage\Watermark::calculatePosition
     * @uses   Elboletaire\Watimage\Image
     */
    public function testApply()
    {
        // Check values
        $red = [
            'red' => 255,
            'green' => 0,
            'blue' => 0,
            'alpha' => 0
        ];
        $white = [
            'red' => 255,
            'green' => 255,
            'blue' => 255,
            'alpha' => 0
        ];
        $transparent = [
            'red' => 0,
            'green' => 0,
            'blue' => 0,
            'alpha' => 127
        ];

        // Init classes
        $image = new Image();
        $watermark = $this->testClass;

        $image->create(200)->fill($white);
        $instance = $watermark->create(10)
            ->fill($red)
            ->setPosition('top left')
            ->apply($image)
        ;

        // Check instances are the expected ones
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $image);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);
        $this->assertInstanceOf('Elboletaire\Watimage\Watermark', $watermark);

        $resource = $image->getImage();

        // Ensure whatermark is there
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 0, 0));
        $this->assertArraySubset($red, $pixel);
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 9, 9));
        $this->assertArraySubset($red, $pixel);
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 9, 0));
        $this->assertArraySubset($red, $pixel);
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 0, 9));
        $this->assertArraySubset($red, $pixel);

        // Check image is just after the image
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 10, 10));
        $this->assertArraySubset($white, $pixel);
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 0, 10));
        $this->assertArraySubset($white, $pixel);
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 10, 0));
        $this->assertArraySubset($white, $pixel);

        // Check transparencies
        $image->create(400);
        $watermark
            ->load("{$this->files_path}/watermark.png")
            ->setPosition('top left')
            ->apply($image)
        ;

        $resource = $image->getImage();
        // Ensure it's still transparent
        $pixel = imagecolorsforindex($resource, imagecolorat($resource, 0, 0));
        $this->assertArraySubset($transparent, $pixel);
    }

    /**
     * @covers Elboletaire\Watimage\Watermark::calculatePosition
     * @uses   Elboletaire\Watimage\Image
     */
    public function testCalculatePosition()
    {
        $size_image = 200;
        $size_watermark = 10;

        $image = new Image();
        $image->create($size_image);

        $this->testClass->create($size_watermark);
        $this->testClass->fill('#f00');

        $instance = $this->testClass->setPosition('bottom right')->apply($image);

        $metadata_img = $image->getMetadata();
        $calculatePosition = $this->getMethod('calculatePosition');
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);

        $this->assertArraySubset([190, 190], $position);
        // Setting exact position should return array
        $this->setProperty('position', [200, 200]);
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([200, 200], $position);
        // When position is null or size is `full` should set position to `center center`
        $this->setProperty('position', null);
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertEquals('center center', $this->getProperty('position'));
        $this->setProperty('position', null);
        $this->testClass->setSize('full');
        $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertEquals('center center', $this->getProperty('position'));

        // Position calculated without margin
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('top left')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([0, 0] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('top center')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([95, 0] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('top right')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([190, 0] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('bottom left')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([0, 190] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('bottom center')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([95, 190] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('bottom right')
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([190, 190] , $position);

        // Position calculated with margin
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('center center')
            ->setMargin(10)
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([105, 105] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('center left')
            ->setMargin(10, 0)
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([10, 95] , $position);
        $this->testClass
            ->destroy()
            ->create($size_watermark)
            ->fill('#f00')
            ->setPosition('center right')
            ->setMargin(-10, 10)
        ;
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);
        $this->assertArraySubset([180, 105] , $position);
    }
}

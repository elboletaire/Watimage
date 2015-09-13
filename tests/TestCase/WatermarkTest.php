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

    public function testApply()
    {

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
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $image);
        $this->assertInstanceOf('Elboletaire\Watimage\Image', $instance);

        $image->generate($this->getOutputFilename('watermark-position.png'));

        $metadata_img = $image->getMetadata();
        $calculatePosition = $this->getMethod('calculatePosition');
        $position = $calculatePosition->invoke($this->testClass, $metadata_img);

        $this->assertArraySubset([190, 190], $position);
    }
}

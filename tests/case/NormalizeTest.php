<?php
namespace Elboletaire\Watimage\Test\TestCase;

use Elboletaire\Watimage\Normalize;

class NormalizeTest extends \PHPUnit_Framework_TestCase
{
    public function testColor()
    {
        $expected = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 127];
        $this->assertArraySubset($expected, Normalize::color(-1));
        $this->assertArraySubset($expected, Normalize::color([0, 0, 0, 127]));
        $this->assertArraySubset($expected, Normalize::color([
            'red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 127
        ]));
        $this->assertArraySubset($expected, Normalize::color('#0000007F'));

        $expected['a'] = 119;
        $this->assertArraySubset($expected, Normalize::color('#0007'));
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testColorFail()
    {
        Normalize::color('#33333');
    }

    public function testCrop()
    {
        $expected = [23, 32, 200, 150];

        // Passing multiple arguments
        $this->assertArraySubset(
            $expected,
            // x, y, width, height
            Normalize::crop(23, 32, 200, 150)
        );
        // Passing an array
        $this->assertArraySubset(
            $expected,
            Normalize::crop([23, 32, 200, 150])
        );
        // Passing an associative array
        $this->assertArraySubset(
            $expected,
            Normalize::crop([
                'x'      => 23,
                'y'      => 32,
                'width'  => 200,
                'height' => 150
            ])
        );
        // Passing a simplified associative array
        $this->assertArraySubset(
            $expected,
            Normalize::crop([
                'x' => 23,
                'y' => 32,
                'w' => 200,
                'h' => 150
            ])
        );
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testCropFail()
    {
        Normalize::crop(23);
    }

    public function testFlip()
    {
        $this->assertEquals(IMG_FLIP_HORIZONTAL, Normalize::flip('x'));
        $this->assertEquals(IMG_FLIP_HORIZONTAL, Normalize::flip('h'));
        $this->assertEquals(IMG_FLIP_HORIZONTAL, Normalize::flip('horizontal'));
        $this->assertEquals(IMG_FLIP_HORIZONTAL, Normalize::flip(IMG_FLIP_HORIZONTAL));

        $this->assertEquals(IMG_FLIP_VERTICAL, Normalize::flip('y'));
        $this->assertEquals(IMG_FLIP_VERTICAL, Normalize::flip('v'));
        $this->assertEquals(IMG_FLIP_VERTICAL, Normalize::flip('vertical'));
        $this->assertEquals(IMG_FLIP_VERTICAL, Normalize::flip(IMG_FLIP_VERTICAL));

        $this->assertEquals(IMG_FLIP_BOTH, Normalize::flip('b'));
        $this->assertEquals(IMG_FLIP_BOTH, Normalize::flip('both'));
        $this->assertEquals(IMG_FLIP_BOTH, Normalize::flip(IMG_FLIP_BOTH));
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testFlipFail()
    {
        Normalize::flip('fail');
    }

    public function testPosition()
    {
        $expected = [23, 23];
        $this->assertArraySubset($expected, Normalize::watermarkPosition(23));
        $this->assertArraySubset($expected, Normalize::watermarkPosition(['x' => 23]));

        $expected = [23, 32];
        $this->assertArraySubset($expected, Normalize::watermarkPosition($expected));
        $this->assertArraySubset($expected, Normalize::watermarkPosition(['x' => 23, 'y' => 32]));
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testPositionFail()
    {
        Normalize::position(23, 'fail');
    }

    public function testSize()
    {
        $expected = [250, 320];

        // Passing multiple arguments
        $this->assertArraySubset(
            $expected,
            Normalize::size(
                // width, height
                250, 320
            )
        );
        // Passing an array
        $this->assertArraySubset(
            $expected,
            Normalize::size([
                // width, height
                250, 320
            ])
        );
        // Passing an associative array
        $this->assertArraySubset(
            $expected,
            Normalize::size([
                'width'  => 250,
                'height' => 320
            ])
        );
        // Passing simplified associative arrays
        $this->assertArraySubset(
            $expected,
            Normalize::size([
                'w' => 250,
                'h' => 320
            ])
        );
        $this->assertArraySubset(
            $expected,
            Normalize::size([
                'x' => 250,
                'y' => 320
            ])
        );
        // Passing just width (should return same height)
        $this->assertArraySubset(
            [250, 250],
            Normalize::size(250)
        );
        $this->assertArraySubset(
            [250, 250],
            Normalize::size(['width' => 250])
        );
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testSizeFail()
    {
        Normalize::size(null);
    }

    public function testWatermarkPosition()
    {
        // Test string
        $expected = 'center center';

        $this->assertEquals($expected, Normalize::watermarkPosition('center'));
        $this->assertEquals($expected, Normalize::watermarkPosition('centered'));
        // Many of the possibilities are tested in testPosition
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testWatermarkPositionFail()
    {
        Normalize::watermarkPosition('not valid');
    }

    public function testWatermarkSize()
    {
        $this->assertEquals('50%', Normalize::watermarkSize('50%'));
        $this->assertEquals('full', Normalize::watermarkSize('full'));
        $this->assertArraySubset([23, 42], Normalize::watermarkSize(23, 42));
    }

    /**
     * @expectedException Elboletaire\Watimage\Exception\InvalidArgumentException
     */
    public function testWatermarkSizeFail()
    {
        Normalize::watermarkSize('not valid');
    }
}

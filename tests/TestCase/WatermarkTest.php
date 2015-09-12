<?php
namespace Elboletaire\Watimage\Test\TestCase;

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
        $this->testClass
            ->setSize("150%")
            ->setPosition("centered")
            ->setMargin(20)
            ->destroy()
        ;

        $this->assertNull($this->getProperty('position'));
        $this->assertNull($this->getProperty('size'));
        $this->assertArraySubset([0, 0], $this->getProperty('margin'));
    }

    public function testSetPosition()
    {
        $this->testClass->setPosition('centered');

        $this->assertNotNull($this->getProperty('position'));
    }

    public function testSetSize()
    {
        $this->testClass->setSize('121%');

        $this->assertNotNull($this->getProperty('size'));
    }

    public function testSetMargin()
    {
        $this->testClass->setMargin(23);

        $this->assertNotNull($this->getProperty('margin'));
        $this->assertNotEquals([0, 0], $this->getProperty('margin'));
    }
}

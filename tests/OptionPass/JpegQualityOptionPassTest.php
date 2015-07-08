<?php

namespace Imagecraft\OptionPass;

/**
 * @covers Imagecraft\OptionPass\JpegQualityOptionPass
 */
class JpegQualityOptionPassTest extends \PHPUnit_Framework_TestCase
{
    protected $pass;

    public function setUp()
    {
        $this->pass = $this->getMock('Imagecraft\\OptionPass\\JpegQualityOptionPass', null);
    }

    public function testProcess()
    {
        $option = $this->pass->process(array());
        $this->assertEquals(100, $option['jpeg_quality']);

        $option = $this->pass->process(array('jpeg_quality' => 200));
        $this->assertEquals(100, $option['jpeg_quality']);

        $option = $this->pass->process(array('jpeg_quality' => -100));
        $this->assertEquals(100, $option['jpeg_quality']);
    }
}

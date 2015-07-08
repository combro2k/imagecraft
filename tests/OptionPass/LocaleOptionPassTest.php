<?php

namespace Imagecraft\OptionPass;

/**
 * @covers Imagecraft\OptionPass\LocaleOptionPass
 */
class LocaleOptionPassTest extends \PHPUnit_Framework_TestCase
{
    protected $pass;

    public function setUp()
    {
        $this->pass = $this->getMock('Imagecraft\\OptionPass\\LocaleOptionPass', null);
    }

    public function testProcess()
    {
        $option = $this->pass->process(array());
        $this->assertEquals('en', $option['locale']);

        $option = $this->pass->process(array('locale' => 'foo'));
        $this->assertEquals('en', $option['locale']);
    }
}

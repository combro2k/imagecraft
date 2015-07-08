<?php

namespace Imagecraft\Engine\PhpGd\Extension\Gif\EventListener;

/**
 * @covers Imagecraft\Engine\PhpGd\Extension\Gif\EventListener\ImageFactoryListener
 */
class ImageFactoryListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected $listener;

    protected $event;

    protected $layer;

    protected $image;

    public function setUp()
    {
        $this->factory = $this->getMock('Imagecraft\\Engine\\PhpGd\\Extension\\Gif\\ImageFactory', array(), array(), '', false);
        $this->listener = $this->getMock(
            'Imagecraft\\Engine\\PhpGd\\Extension\\Gif\\EventListener\\ImageFactoryListener',
            null,
            array($this->factory)
        );
        $this->layer = $this->getMock('Imagecraft\\Layer\\BackgroundLayer', null);
        $this->event = $this->getMock('Imagecraft\\Engine\\PhpGd\\PhpGdEvent', array(), array(), '', false);
        $this->event
            ->method('getLayers')
            ->will($this->returnValue(array($this->layer)))
        ;
        $this->image = $this->getMock('Imagecraft\\Image', null);
        $this->event
            ->method('getImage')
            ->will($this->returnValue($this->image))
        ;
    }

    public function testCreateImage()
    {
        $this->event
            ->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array()))
        ;
        $this->factory
            ->expects($this->once())
            ->method('createImage')
            ->will($this->returnValue($this->image))
        ;
        $this->event
            ->expects($this->once())
            ->method('setImage')
            ->with($this->image)
        ;
        $this->layer->add(array('gif.extracted' => array()));
        $this->listener->createImage($this->event);
    }

    public function testAddImageExtras()
    {
        $this->event
            ->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array()))
        ;
        $this->factory
            ->expects($this->once())
            ->method('createImage')
            ->will($this->throwException(new \Exception()))
        ;
        $this->layer->add(array('gif.extracted' => array()));
        $this->listener->createImage($this->event);
        $this->listener->addImageExtras($this->event);
        $this->assertNotEmpty($this->image->getExtras()['gif_error']);
    }
}

<?php

namespace Imagecraft\Engine\PhpGd\Extension\Core\EventListener;

use Imagecraft\Layer\ImageAwareLayerInterface;
use ImcStream\ImcStream;

/**
 * @covers Imagecraft\Engine\PhpGd\Extension\Core\EventListener\ImageAwareLayerListener
 */
class ImageAwareLayerListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $listener;

    protected $event;

    protected $layer;

    public function setUp()
    {
        $context = $this->getMock('Imagecraft\\Engine\\PhpGd\\PhpGdContext', null);
        $info    = $this->getMock('Imagecraft\\Engine\\PhpGd\\Extension\\Core\\ImageInfo', null, array($context));
        $rh      = $this->getMock('Imagecraft\\Engine\\PhpGd\\Helper\\ResourceHelper', null);
        $this->listener = $this->getMock(
            'Imagecraft\\Engine\PhpGd\\Extension\\Core\\EventListener\\ImageAwareLayerListener',
            null,
            array($info, $rh)
        );
        $this->layer = $this->getMock('Imagecraft\\Layer\\ImageLayer', null);
        $this->event = $this->getMock('Imagecraft\\Engine\\PhpGd\\PhpGdEvent', array(), array(), '', false);
        $this->event
            ->method('getLayers')
            ->will($this->returnValue(array($this->layer)))
        ;
    }

    public function testInitImcUri()
    {
        if (!ini_get('allow_url_fopen') || !$r = @fsockopen('8.8.8.8', 53, $e, $r, 1)) {
            $this->markTestSkipped('No internet connection or allow_url_fopen is not enabled.');
        }

        $this->layer->add(array(
            'image.http.url'        => 'http://www.example.com',
            'image.http.data_limit' => 500,
            'image.http.timeout'    => 20,
        ));
        $this->listener->initImcUri($this->event);
        $this->assertNotEmpty($this->layer->get('image.imc_uri'));

        $this->layer->clear();
        $this->layer->add(array('image.filename' => __FILE__));
        $this->listener->initImcUri($this->event);
        $this->assertNotEmpty($this->layer->get('image.imc_uri'));
    }

    public function testInitFilePointer()
    {
        if (!ini_get('allow_url_fopen') || !$r = @fsockopen('8.8.8.8', 53, $e, $r, 1)) {
            $this->markTestSkipped('No internet connection or allow_url_fopen is not enabled.');
        }

        $this->layer->add(array('image.imc_uri' => 'http://www.example.com'));
        $this->listener->initFilePointer($this->event);
        $this->assertInternalType('resource', $this->layer->get('image.fp'));

        $this->layer->clear();
        $this->layer->add(array('image.contents' => file_get_contents(__FILE__)));
        $this->listener->initFilePointer($this->event);
        $this->assertInternalType('resource', $this->layer->get('image.fp'));
    }

    public function testInitImageInfo()
    {
        $this->layer->set('image.fp', fopen(__DIR__.'/../../../../../Fixtures/gif_87a_palette_250x297.gif', 'rb'));
        $this->listener->initImageInfo($this->event);
        $this->assertInternalType('integer', $this->layer->get('image.width'));
        $this->assertInternalType('integer', $this->layer->get('image.height'));
        $this->assertInternalType('string', $this->layer->get('image.format'));
    }

    public function testInitFinalDimensions()
    {
        $this->layer->add(array(
            'image.width'         => 200,
            'image.height'        => 200,
            'image.resize.width'  => 100,
            'image.resize.height' => 100,
            'image.resize.option' => ImageAwareLayerInterface::RESIZE_FILL_CROP,
        ));
        $this->listener->initFinalDimensions($this->event);
        $this->assertInternalType('integer', $this->layer->get('final.width'));
        $this->assertInternalType('integer', $this->layer->get('final.height'));
    }

    /**
     * @depends testInitFilePointer
     */
    public function testTermFilePointer()
    {
        $this->layer->add(array('image.contents' => file_get_contents(__FILE__)));
        $this->listener->initFilePointer($this->event);
        $this->listener->termFilePointer($this->event);
        $this->assertFalse($this->layer->has('image.fp'));
    }

    /**
     * @depends testInitImcUri
     */
    public function testTermImcUri()
    {
        if (!ini_get('allow_url_fopen') || !$r = @fsockopen('8.8.8.8', 53, $e, $r, 1)) {
            $this->markTestSkipped('No internet connection or allow_url_fopen is not enabled.');
        }

        $this->layer->add(array(
            'image.http.url'        => 'http://www.example.com',
            'image.http.data_limit' => 500,
            'image.http.timeout'    => 20,
        ));
        $this->listener->initImcUri($this->event);
        $this->listener->termImcUri($this->event);
        $this->assertFalse($this->layer->has('image.imc_uri'));
    }
}

<?php

namespace Imagecraft\Engine\PhpGd\Helper;

use Imagecraft\Layer\ImageAwareLayerInterface;
use Imagecraft\Layer\RegularLayerInterface;
use Imagecraft\Engine\PhpGd\PhpGdContext;

/**
 * @requires extension gd
 * @requires function imagegif
 * @requires function imagecreatefromgif
 * @requires function imagecreatefromwebp
 * @requires function imagecreatefromjpeg
 * @requires function imagecreatefrompng
 * @requires function imagefttext
 * @covers   Imagecraft\Engine\PhpGd\Helper\ResourceHelper
 */
class ResourceHelperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->rh = $this->getMock('Imagecraft\\Engine\\PhpGd\\Helper\\ResourceHelper', null);
    }

    public function testGetEmptyGdResource()
    {
        $resource = $this->rh->getEmptyGdResource(100, 100);
        $this->assertInternalType('resource', $resource);
        $this->assertEquals(100, imagesx($resource));
        $this->assertEquals(100, imagesy($resource));
        imagepng($resource, __DIR__.'/TestOutput/resource_helper_image_should_be_blank.png');
        imagedestroy($resource);
    }

    public function testGetPalettizedGdResource()
    {
        $resource = imagecreatetruecolor(100, 100);
        $resource = $this->rh->getPalettizedGdResource($resource);
        $this->assertInternalType('resource', $resource);
        $this->assertFalse(imageistruecolor($resource));
        imagedestroy($resource);
    }

    /**
     * @dataProvider imageDataProvider
     */
    public function testGetGdResourceFromStream($format, $uri)
    {
        $resource = $this->rh->getGdResourceFromStream($format, $uri);
        $this->assertInternalType('resource', $resource);
    }

    /**
     * @expectedException Imagecraft\Exception\InvalidImageException
     */
    public function testGetGdResourceFromStreamWhenStreamIsInvalid()
    {
        $this->rh->getGdResourceFromStream(PhpGdContext::FORMAT_PNG, __FILE__, true);
    }

    /**
     * @dataProvider imageDataProvider
     */
    public function testGetGdResourceFromContents($format, $stream)
    {
        $this->assertInternalType(
            'resource',
            $this->rh->getGdResourceFromContents($format, file_get_contents($stream))
        );
    }

    /**
     * @expectedException Imagecraft\Exception\InvalidImageException
     */
    public function testGetGdResourceFromContentsWhenContentsIsInvalid()
    {
        $this->rh->getGdResourceFromContents(PhpGdContext::FORMAT_WEBP, file_get_contents(__FILE__), true);
    }

    /**
     * @depends      testGetGdResourceFromContents
     * @depends      testGetPalettizedGdResource
     * @dataProvider imageDataProvider
     */
    public function testGetContentsFromGdResource($format, $uri)
    {
        $contents = file_get_contents($uri);
        $resource = $this->rh->getGdResourceFromContents($format, $contents, true);
        $contents = $this->rh->getContentsFromGdResource(
            $format,
            $resource,
            array('jpeg_quality' => 77, 'png_compression' => 46),
            true
        );
        $this->assertNotEmpty($contents);
        imagedestroy($resource);
    }

    /**
     * @expectedException Imagecraft\Exception\InvalidImageException
     */
    public function testGetContentsFromGdResourceWhenResourceIsInvalid()
    {
        $this->rh->getContentsFromGdResource(PhpGdContext::FORMAT_WEBP, 'foo', array(), true);
    }

    /**
     * @dataProvider resizeDataProvider
     */
    public function testGetResizeArguments(
        $originalWidth,
        $originalHeight,
        $resizeWidth,
        $resizeHeight,
        $resizeOption,
        $expectedDstW,
        $expectedDstH
    ) {
        $args = $this->rh->getResizeArguments($originalWidth, $originalHeight, $resizeWidth, $resizeHeight, $resizeOption);
        $this->assertInternalType('array', $args);
        $this->assertEquals($expectedDstW, $args['dst_w']);
        $this->assertEquals($expectedDstH, $args['dst_h']);
    }

    public function testGetResizeArgumentsWhenNoResizeIsNeeded()
    {
        $args = $this->rh->getResizeArguments(
            100, 100, 100, 100, ImageAwareLayerInterface::RESIZE_SHRINK
        );
        $this->assertFalse($args);

        $args = $this->rh->getResizeArguments(
            100, 100, 200, 200, ImageAwareLayerInterface::RESIZE_SHRINK
        );
        $this->assertFalse($args);
    }

    /**
     * @depends      testGetResizeArguments
     * @depends      testGetEmptyGdResource
     * @dataProvider resizeDataProvider
     */
    public function testGetResizedGdResource(
        $originalWidth,
        $originalHeight,
        $resizeWidth,
        $resizeHeight,
        $resizeOption,
        $expectedDstW,
        $expectedDstH
    ) {
        $resource = imagecreate($originalWidth, $originalHeight);
        $resource = $this->rh->getResizedGdResource($resource, $resizeWidth, $resizeHeight, $resizeOption, true);
        $this->assertEquals($expectedDstW, imagesx($resource));
        $this->assertEquals($expectedDstH, imagesy($resource));
        imagedestroy($resource);

        $resource = imagecreate($originalWidth, $originalHeight);
        $resource = $this->rh->getResizedGdResource($resource, $resizeWidth, $resizeHeight, $resizeOption, false);
        $this->assertEquals($expectedDstW, imagesx($resource));
        $this->assertEquals($expectedDstH, imagesy($resource));
        imagedestroy($resource);

        $resource1 = imagecreate($originalWidth, $originalHeight);
        $resource2 = $this->rh->getResizedGdResource($resource1, $originalWidth, $originalHeight, $resizeOption, false);
        $this->assertTrue($resource1 === $resource2);
        imagedestroy($resource1);
    }

    /**
     * @depends      testGetEmptyGdResource
     * @dataProvider mergeDataProvider
     */
    public function testGetMergedGdResource($x, $y, $gravity)
    {
        $srcResource = imagecreatetruecolor(100, 100);
        $dstResource = imagecreate(300, 300);
        imagecolorallocate($dstResource, 255, 0, 0);
        $resource = $this->rh->getMergedGdResource($dstResource, $srcResource, $x, $y, $gravity);
        $this->assertInternalType('resource', $resource);
        imagepng($resource, __DIR__.'/TestOutput/resource_helper_image_should_be_merged_'.$x.'_'.$y.'_'.$gravity.'.png');
        imagedestroy($resource);
    }

    /**
     * @depends testGetEmptyGdResource
     * @depends testGetMergedGdResource
     * @depends testGetPalettizedGdResource
     */
    public function testGetClonedGdResource()
    {
        $resource       = imagecreate(100, 100);
        $clonedResource = $this->rh->getClonedGdResource($resource);
        $this->assertInternalType('resource', $clonedResource);
        $this->assertFalse($resource === $clonedResource);
        $this->assertFalse(imageistruecolor($clonedResource));
    }

    /**
     * @depends      testGetEmptyGdResource
     * @dataProvider fontDataProvider
     */
    public function testGetTextGdResource($filename)
    {
        $resource = $this->rh->getTextGdResource(
            $filename, 25, array(0, 0, 0), 'Hello World', 1.5, 60, array(0, 0, 0, 0), array(133, 133, 133)
        );
        $this->assertInternalType('resource', $resource);
        imagepng($resource, __DIR__.'/TestOutput/resource_helper_text_should_be_valid.png');
        @imagedestroy($resource);
    }

    /**
     * @expectedException Imagecraft\Exception\InvalidFontException
     */
    public function testGetTextGdResourceWhenFontIsInvalid()
    {
        $this->rh->getTextGdResource(
            __FILE__, 12, array(0, 0, 0), 'Hello World', 1.5, 60, array(0, 0, 0, 0), array(133, 133, 111)
        );
    }

    public function imageDataProvider()
    {
        return array(
            array(PhpGdContext::FORMAT_PNG,  __DIR__.'/../../../Fixtures/png_palette_alpha_3000x1174.png'),
            array(PhpGdContext::FORMAT_WEBP, __DIR__.'/../../../Fixtures/webp_vp8_lossy_truecolor_550x368.webp'),
            array(PhpGdContext::FORMAT_JPEG, __DIR__.'/../../../Fixtures/jpeg_jfjf_truecolor_1920x758.jpg'),
            array(PhpGdContext::FORMAT_GIF,  __DIR__.'/../../../Fixtures/gif_87a_palette_250x297.gif'),
        );
    }

    public function resizeDataProvider()
    {
        return array(
            array(200, 100, 100, 100, ImageAwareLayerInterface::RESIZE_SHRINK, 100, 50),
            array(100, 200, 100, 100, ImageAwareLayerInterface::RESIZE_SHRINK, 50, 100),
            array(200, 100,  50,  50, ImageAwareLayerInterface::RESIZE_FILL_CROP, 50, 50),
            array(100, 200,  50,  50, ImageAwareLayerInterface::RESIZE_FILL_CROP, 50, 50),
        );
    }

    public function mergeDataProvider()
    {
        return array(
            array(50,    50, RegularLayerInterface::MOVE_TOP_LEFT),
            array(50,    50, RegularLayerInterface::MOVE_TOP_CENTER),
            array(50,    50, RegularLayerInterface::MOVE_TOP_RIGHT),
            array(50,    50, RegularLayerInterface::MOVE_CENTER_LEFT),
            array(50,    50, RegularLayerInterface::MOVE_CENTER),
            array(50,    50, RegularLayerInterface::MOVE_CENTER_RIGHT),
            array(50,    50, RegularLayerInterface::MOVE_BOTTOM_LEFT),
            array(50,    50, RegularLayerInterface::MOVE_BOTTOM_CENTER),
            array(50,    50, RegularLayerInterface::MOVE_BOTTOM_RIGHT),
            array(-50,  -50, RegularLayerInterface::MOVE_CENTER),
            array(-500, -500, RegularLayerInterface::MOVE_CENTER),
        );
    }

    public function fontDataProvider()
    {
        return array(
            array(__DIR__.'/../../../Fixtures/pfa_truecolor_alpha.pfa'),
        );
    }
}

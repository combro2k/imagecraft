<?php

namespace Imagecraft\Engine\PhpGd\Extension\Core\EventListener;

use Imagecraft\Engine\PhpGd\Extension\Core\ImageInfo;
use Imagecraft\Engine\PhpGd\Helper\ResourceHelper;
use Imagecraft\Engine\PhpGd\PhpGdEvent;
use Imagecraft\Engine\PhpGd\PhpGdEvents;
use Imagecraft\Layer\ImageAwareLayerInterface;
use ImcStream\ImcStream;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Xianghan Wang <coldume@gmail.com>
 *
 * @since  1.0.0
 */
class ImageAwareLayerListener implements EventSubscriberInterface
{
    /**
     * @var ImageInfo
     */
    protected $info;

    /**
     * @var ResourceHelper
     */
    protected $rh;

    /**
     * @param ImageInfo
     */
    public function __construct(ImageInfo $info, ResourceHelper $rh)
    {
        $this->info = $info;
        $this->rh = $rh;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            PhpGdEvents::PRE_IMAGE => array(
                array('initImcUri', 909),
                array('initFilePointer', 899),
                array('initImageInfo', 889),
                array('initFinalDimensions', 879),
                array('termFilePointer', 99),
            ),
            PhpGdEvents::FINISH_IMAGE => array(
                array('termFilePointer', 99),
                array('termImcUri', 89),
            ),
        );
    }

    /**
     * @param PhpGdEvent
     */
    public function initImcUri(PhpGdEvent $event)
    {
        ImcStream::register();
        $layers = $event->getLayers();
        foreach ($layers as $key => $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            $arr = false;
            if ($layer->has('image.http.url')) {
                $arr = array(
                    'uri' => $layer->get('image.http.url'),
                    'data_limit' => $layer->get('image.http.data_limit'),
                    'timeout' => $layer->get('image.http.timeout'),
                    'seek' => true,
                    'global' => true,
                );
            } elseif ($layer->has('image.filename')) {
                $arr = array(
                    'uri' => $layer->get('image.filename'),
                    'seek' => true,
                );
            }
            if ($arr) {
                $uri = 'imc://'.serialize($arr);
                $layer->set('image.imc_uri', $uri);
            }
        }
    }

    /**
     * @param PhpGdEvent
     */
    public function initFilePointer(PhpGdEvent $event)
    {
        $layers = $event->getLayers();
        foreach ($layers as $key => $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            if ($layer->has('image.imc_uri')) {
                $fp = fopen($layer->get('image.imc_uri'), 'rb');
            } elseif ($layer->has('image.contents')) {
                $fp = fopen('php://temp', 'rb+');
                fwrite($fp, $layer->get('image.contents'));
            }
            $layer->set('image.fp', $fp);
        }
    }

    /**
     * @param PhpGdEvent
     */
    public function initImageInfo(PhpGdEvent $event)
    {
        $layers = $event->getLayers();
        foreach ($layers as $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            $info = $this->info->resolveFromFilePointer($layer->get('image.fp'));
            $layer->add(array(
                'image.width' => $info['width'],
                'image.height' => $info['height'],
                'image.format' => $info['format'],
            ));
        }
    }

    /**
     * @param PhpGdEvent
     */
    public function initFinalDimensions(PhpGdEvent $event)
    {
        $layers = $event->getLayers();
        foreach ($layers as $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            $width = $layer->get('image.width');
            $height = $layer->get('image.height');
            if ($layer->has('image.resize.width')) {
                $args = $this->rh->getResizeArguments(
                    $width,
                    $height,
                    $layer->get('image.resize.width'),
                    $layer->get('image.resize.height'),
                    $layer->get('image.resize.option')
                );
                if ($args) {
                    $width = $args['dst_w'];
                    $height = $args['dst_h'];
                }
            }
            $layer->add(array('final.width' => $width, 'final.height' => $height));
        }
    }

    /**
     * @param PhpGdEvent
     */
    public function termFilePointer(PhpGdEvent $event)
    {
        $layers = $event->getLayers();
        foreach ($layers as $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            if ($layer->has('image.fp')) {
                fclose($layer->get('image.fp'));
                $layer->remove('image.fp');
            }
        }
    }

    /**
     * @param PhpGdEvent
     */
    public function termImcUri(PhpGdEvent $event)
    {
        $layers = $event->getLayers();
        foreach ($layers as $key => $layer) {
            if (!($layer instanceof ImageAwareLayerInterface)) {
                continue;
            }
            if ($layer->has('image.imc_uri')) {
                ImcStream::fclose($layer->get('image.imc_uri'));
                $layer->remove('image.imc_uri');
            }
        }
    }
}

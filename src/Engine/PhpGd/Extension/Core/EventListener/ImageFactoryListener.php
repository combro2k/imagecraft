<?php

namespace Imagecraft\Engine\PhpGd\Extension\Core\EventListener;

use Imagecraft\Engine\PhpGd\Extension\Core\ImageFactory;
use Imagecraft\Engine\PhpGd\PhpGdEvent;
use Imagecraft\Engine\PhpGd\PhpGdEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Xianghan Wang <coldume@gmail.com>
 *
 * @since  1.0.0
 */
class ImageFactoryListener implements EventSubscriberInterface
{
    /**
     * @var ImageFactory
     */
    protected $factory;

    /**
     * @param ImageFactory $factory
     */
    public function __construct(ImageFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            PhpGdEvents::IMAGE => array('createImage', 99),
        );
    }

    /**
     * @param PhpGdEvent $event
     */
    public function createImage(PhpGdEvent $event)
    {
        $image = $this->factory->createImage($event->getLayers(), $event->getOptions());
        $event->setImage($image);
        $event->stopPropagation();
    }
}

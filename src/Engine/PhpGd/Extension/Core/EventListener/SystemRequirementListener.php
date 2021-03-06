<?php

namespace Imagecraft\Engine\PhpGd\Extension\Core\EventListener;

use Imagecraft\Engine\PhpGd\PhpGdContext;
use Imagecraft\Engine\PhpGd\PhpGdEvent;
use Imagecraft\Engine\PhpGd\PhpGdEvents;
use Imagecraft\Exception\InvalidArgumentException;
use Imagecraft\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Xianghan Wang <coldume@gmail.com>
 *
 * @since  1.0.0
 */
class SystemRequirementListener implements EventSubscriberInterface
{
    /**
     * @var PhpGdContext
     */
    protected $context;

    /**
     * @param PhpGdContext $context
     */
    public function __construct(PhpGdContext $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            PhpGdEvents::PRE_IMAGE => array(
                array('verifyEngine', 999),
                array('verifySavedFormat', 989),
            ),
        );
    }

    /**
     * @throws RuntimeException
     */
    public function verifyEngine()
    {
        if (!$this->context->isEngineSupported()) {
            throw new RuntimeException('gd.extension.not.enabled');
        }
    }

    /**
     * @param PhpGdEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function verifySavedFormat(PhpGdEvent $event)
    {
        $format = $event->getOptions()['output_format'];
        if ('default' !== $format && !$this->context->isImageFormatSupported($format)) {
            throw new InvalidArgumentException(
                'output.image.format.not.supported.%cp_unsupported%',
                array('%cp_unsupported%' => '"'.$format.'"')
            );
        }
    }
}

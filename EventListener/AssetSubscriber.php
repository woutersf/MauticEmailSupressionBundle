<?php

namespace MauticPlugin\MauticEmailSupressionBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['onInjectCustomAssets', 0],
        ];
    }

    public function onInjectCustomAssets(CustomAssetsEvent $event): void
    {
        $event->addScript('plugins/MauticEmailSupressionBundle/Assets/js/calendar.js');
        $event->addStylesheet('plugins/MauticEmailSupressionBundle/Assets/css/calendar.css');
        $event->addStylesheet('plugins/MauticEmailSupressionBundle/Assets/css/form.css');
    }
}

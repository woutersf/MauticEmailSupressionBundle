<?php

declare(strict_types=1);

return [
    'name'        => 'Email Supression Lists',
    'description' => 'Manage email suppression lists with calendar-based date selection',
    'version'     => '1.0.0',
    'author'      => 'Frederik Wouters',

    'routes' => [
        'main' => [
            'mautic_supressionlist_index' => [
                'path'       => '/supressionlists/{page}',
                'controller' => 'MauticPlugin\MauticEmailSupressionBundle\Controller\SupressionListController::indexAction',
                'defaults'   => ['page' => 1],
            ],
            'mautic_supressionlist_action' => [
                'path'       => '/supressionlists/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticEmailSupressionBundle\Controller\SupressionListController::executeAction',
            ],
            'mautic_supressionlist_calendar' => [
                'path'       => '/supressionlists/{id}/calendar/{year}',
                'controller' => 'MauticPlugin\MauticEmailSupressionBundle\Controller\SupressionListController::calendarAction',
                'defaults'   => ['year' => null],
            ],
            'mautic_supressionlist_dates' => [
                'path'       => '/supressionlists/{id}/dates',
                'controller' => 'MauticPlugin\MauticEmailSupressionBundle\Controller\SupressionListController::datesAction',
            ],
        ],
        'public' => [
            'mautic_supressionlist_toggle_date' => [
                'path'       => '/supressionlists/{id}/supress_date/{date}/{action}',
                'controller' => 'MauticPlugin\MauticEmailSupressionBundle\Controller\SupressionListController::toggleDateAction',
            ],
        ],
        'api'    => [],
    ],

    'menu' => [
        'main' => [
            'mautic.supressionlist.menu.index' => [
                'iconClass' => 'ri-calendar-close-fill',
                'route'     => 'mautic_supressionlist_index',
                'access'    => 'supressionlist:supressionlists:view',
                'priority'  => 45,
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.supressionlist.asset.subscriber' => [
                'class' => MauticPlugin\MauticEmailSupressionBundle\EventListener\AssetSubscriber::class,
            ],
            'mautic.supressionlist.campaign.suppression.subscriber' => [
                'class' => MauticPlugin\MauticEmailSupressionBundle\EventListener\CampaignSuppressionSubscriber::class,
                'arguments' => [
                    'database_connection',
                ],
            ],
            'mautic.supressionlist.email.suppression.subscriber' => [
                'class' => MauticPlugin\MauticEmailSupressionBundle\EventListener\EmailSuppressionSubscriber::class,
                'arguments' => [
                    'database_connection',
                ],
            ],
        ],
        'forms' => [
            'mautic.supressionlist.form.type.supressionlist' => [
                'class' => MauticPlugin\MauticEmailSupressionBundle\Form\Type\SupressionListType::class,
                'tags' => [
                    'form.type',
                ],
            ],
        ],
        'models' => [
            'mautic.supressionlist.model.supressionlist' => [
                'class'     => MauticPlugin\MauticEmailSupressionBundle\Model\SupressionListModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],

    'parameters' => [],
];

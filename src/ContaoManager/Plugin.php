<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use numero2\DeepLBundle\DeepLBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;


class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface {


    public function getBundles( ParserInterface $parser ): array {

        return [
            BundleConfig::create(DeepLBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class
                ])
        ];
    }


    function getRouteCollection( LoaderResolverInterface $resolver, KernelInterface $kernel ): ?RouteCollection {

        return $resolver
            ->resolve(__DIR__.'/../../config/routes.yaml')
            ->load(__DIR__.'/../../config/routes.yaml')
        ;
    }


    /**
     * Add a new cache pool
     *
     * @param string $extensionName
     * @param array $extensionConfigs
     * @param Contao\ManagerPlugin\Config\ContainerBuilder $container
     *
     * @return array
     */
    public function getExtensionConfig( $extensionName, array $extensionConfigs, ContainerBuilder $container ) {

        if( $extensionName === 'framework' ) {

            foreach( $extensionConfigs as &$extensionConfig ) {

                if( isset($extensionConfig['cache']) ) {

                    # creates a "numero2_deepl.cache" service
                    # uses the "app" cache configuration
                    $extensionConfig['cache']['pools']['numero2_deepl.cache'] = [
                        'adapter' => 'cache.app'
                    ,   'default_lifetime' => 0
                    ];

                    break;
                }
            }
        }

        return $extensionConfigs;
    }
}

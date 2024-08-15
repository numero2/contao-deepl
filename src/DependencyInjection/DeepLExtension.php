<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class DeepLExtension extends Extension implements PrependExtensionInterface {


    /**
     * {@inheritdoc}
     */
    public function getAlias(): string {

        return 'deepl';
    }


    /**
     * {@inheritdoc}
     */
    public function prepend( ContainerBuilder $container ): void {

        $configuration = new Configuration((string) $container->getParameter('kernel.project_dir'));
        $config = $this->processConfiguration($configuration, $container->getExtensionConfig($this->getAlias()));

        $apiKey = $config['api_key'];

        // try reading api key from ENV
        if( empty($apiKey) ) {

            $envApiKey = getenv('DEEPL_API_KEY') ?: ($_ENV['DEEPL_API_KEY'] ?? null);

            if( !empty($envApiKey) ) {
                $apiKey = $envApiKey;
            }
        }

        $container->setParameter('contao.deepl.api_key', $apiKey);
    }


    /**
     * {@inheritdoc}
     */
    public function load( array $mergedConfig, ContainerBuilder $container ): void {

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');
    }
}

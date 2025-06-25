<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\DependencyInjection;

use numero2\DeepLBundle\LanguageResolver\LanguageResolverInterface;
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

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $container->getExtensionConfig($this->getAlias()));

        $container->setParameter('contao.deepl.api_key', $config['api_key']);
        $container->setParameter('contao.deepl.pref_lang', $config['pref_lang']);

        $container
            ->registerForAutoconfiguration(LanguageResolverInterface::class)
            ->addTag('numero2.deepl_language_resolver')
            ->setLazy(true)
        ;
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

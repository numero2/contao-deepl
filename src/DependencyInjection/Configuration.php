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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface {


    public function getConfigTreeBuilder(): TreeBuilder {

        $treeBuilder = new TreeBuilder('deepl');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('api_key')
                    ->info('The DeepL API key.')
                    ->defaultValue('%env(DEEPL_API_KEY)%')
                ->end()
                ->arrayNode('pref_lang')
                    ->info('Language preference mapping with source language as key and target language as value')
                    ->defaultValue([])
                    ->useAttributeAsKey('source_lang')
                    ->scalarPrototype()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

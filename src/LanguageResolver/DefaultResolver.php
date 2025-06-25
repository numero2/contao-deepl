<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\LanguageResolver;

use \Exception;
use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\DataContainer;
use Contao\Model;
use Contao\PageModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


abstract class DefaultResolver implements LanguageResolverInterface {

    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }
    /**
     * Map some default language codes to ones DeepL supports (with support for user-defined preferences)
     *
     * @param string $lang
     *
     * @return string
     */
    protected function mapLangauge( ?string $lang ): string {

        if( !$lang ) {
            $lang = 'en-US';
        }

        $prefLang = $this->parameterBag->get('contao.deepl.pref_lang');

        if ($prefLang && strpos($prefLang, ':') !== false) {
            $mappings = $this->parsePrefLangConfig($prefLang);

            if (isset($mappings[$lang])) {
                return $mappings[$lang];
            }
        }

        if( $lang == 'en' ) {
            $lang = 'en-US';
        } else if( $lang == 'pt' ) {
            $lang = 'pt_PT';
        }

        return $lang;
    }

    /**
     * Parse the pref_lang configuration string into an array
     * Supports both single mapping 'en:en-GB' and multiple mappings 'en:en-GB,pt:pt-BR'
     *
     * @param string $prefLangConfig
     * @return array
     */
    private function parsePrefLangConfig(string $prefLangConfig): array
    {
        $mappings = [];

        $pairs = explode(',', $prefLangConfig);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (strpos($pair, ':') !== false) {
                [$source, $target] = explode(':', $pair, 2);
                $mappings[trim($source)] = trim($target);
            }
        }

        return $mappings;
    }
    /**
     * Gets the language of the root for the given page id
     *
     * @param int $id
     *
     * @return string
     */
    protected function getRootLangForPageID( int $id ): string {

        $page = PageModel::findOneBy('id', $id);

        if( !$page ) {
            return '';
        }

        $page->loadDetails();

        return $page->rootLanguage;
    }


    /**
     * Finds the top parent model for the given child Model
     *
     * @param \Contao\Model $child
     *
     * @return \Contao\Model
     */
    protected function findRootParentForContent( Model $child ): Model {

        try {

            $parentModel = Model::getClassFromTable($child->ptable);

        } catch( Exception $e ) {

            return $child;
        }

        $parent = $parentModel::findById($child->pid);

        if( $parent && $parent->pid ) {
            return self::findRootParentForContent($parent);
        }

        return $child;
    }
}
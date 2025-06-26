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

        $prefLangMappings = $this->parameterBag->get('contao.deepl.pref_lang');

        if (is_array($prefLangMappings) && isset($prefLangMappings[$lang])) {
            return $prefLangMappings[$lang];
        }

        if( $lang == 'en' ) {
            $lang = 'en-US';
        } else if( $lang == 'pt' ) {
            $lang = 'pt_PT';
        }

        return $lang;
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
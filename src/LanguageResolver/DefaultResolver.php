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

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\DataContainer;
use Contao\PageModel;


abstract class DefaultResolver implements LanguageResolverInterface {


    protected function mapLangauge( string $lang ): string {

        if( $lang == 'en' ) {
            $lang == 'en-US';
        } else if( $lang == 'pt' ) {
            $lang == 'pt_PT';
        }

        return $lang;
    }


    protected function getRootLangForPageID(int $id): string {

        $page = PageModel::findOneBy('id', $id);

        if( !$page ) {
            return '';
        }

        $page->loadDetails();

        return $page->rootLanguage;
    }
}
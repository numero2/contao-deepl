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
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\PageModel;


class CoreResolver extends DefaultResolver {


    public function supports( DataContainer $dc ): bool {

        if( ($dc->table === ContentModel::getTable() && $dc->parentTable === ArticleModel::getTable()) || in_array($dc->table, [ArticleModel::getTable(), PageModel::getTable()], true)) {
            return true;
        } else if( $dc->table === FormFieldModel::getTable() ) {
            return true;
        }

        return false;
    }


    public function resolve( DataContainer $dc ): string {

        $lang = null;

        // tl_content
        if( $dc->table === ContentModel::getTable() && $dc->parentTable === ArticleModel::getTable()) {

            $content = ContentModel::findOneBy('id', $dc->id);

            $article = ArticleModel::findOneBy('id', $content->pid);

            if( !$article ) {
                return '';
            }

            $lang = $this->getRootLangForPageID((int) $article->pid);

        // tl_article
        } elseif( $dc->table === ArticleModel::getTable() ) {

            $article = ArticleModel::findOneBy('id', $dc->id);
            $lang = $this->getRootLangForPageID((int) $article->pid);

        // tl_page
        } elseif( $dc->table === PageModel::getTable() ) {

            $page = PageModel::findOneBy('id', $dc->id);
            $lang = $this->getRootLangForPageID((int) $page->id);

        // tl_form_field
        } else if( $dc->table === FormFieldModel::getTable() && $dc->parentTable === FormModel::getTable() ) {

            $formField = FormFieldModel::findOneBy('id', $dc->id);
            $form = FormModel::findOneBy('id', $formField->pid);

            if( $form && $form->jumpTo ) {
                $lang = $this->getRootLangForPageID((int) $form->jumpTo);
            }
        }

        return parent::mapLangauge($lang);
    }
}
<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\LanguageResolver;

use Contao\ContentModel;
use Contao\DataContainer;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\NewsModel;


class NewsResolver extends DefaultResolver {


    public function supports( DataContainer $dc ): bool {

        if( !class_exists(ContaoNewsBundle::class) ) {
            return false;
        }

        if( ($dc->table === ContentModel::getTable() && $this->findRootParentForContent((new ContentModel())->setRow($dc->getCurrentRecord()))::class === NewsModel::class) || $dc->table === NewsArchiveModel::getTable() ) {
            return true;
        }

        if( $dc->table === NewsModel::getTable() && $dc->parentTable === NewsArchiveModel::getTable() ) {
            return true;
        }

        if( $dc->table === NewsArchiveModel::getTable() ) {
            return true;
        }

        return false;
    }


    public function resolve( DataContainer $dc ): string {

        $lang = '';

        // tl_content
        if( $dc->table === ContentModel::getTable() && in_array($dc->parentTable, [NewsModel::getTable(),ContentModel::getTable()]) ) {

            $content = ContentModel::findOneBy('id', $dc->id);
            $pid = $content->pid;

            // handling for nested Content Elements
            if( $content->ptable == ContentModel::getTable() ) {
                $pid = $this->findRootParentForContent($content)->id;
            }

            $news = NewsModel::findOneBy('id', $pid);

            if( !$news ) {
                return '';
            }

            $archive = NewsArchiveModel::findOneBy('id', $news->pid);

            $lang = $this->getRootLangForPageID((int) $archive->jumpTo);

        // tl_news
        } elseif( $dc->table === NewsModel::getTable() ) {

            $news = NewsModel::findOneBy('id', $dc->id);

            if( !$news ) {
                return '';
            }

            $archive = NewsArchiveModel::findOneBy('id', $news->pid);

            if( !$archive ) {
                return '';
            }

            $lang = $this->getRootLangForPageID((int) $archive->jumpTo);

        // tl_news_archive
        } elseif( $dc->table === NewsArchiveModel::getTable() ) {

            $archive = NewsArchiveModel::findOneBy('id', $dc->id);
            $lang = $this->getRootLangForPageID((int) $archive->jumpTo);
        }

        return parent::mapLangauge($lang);
    }
}

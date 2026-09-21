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


abstract class DefaultResolver implements LanguageResolverInterface, SourceLanguageResolverInterface {


    protected ParameterBagInterface $parameterBag;


    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }


    /**
     * {@inheritdoc}
     */
    public function resolve( DataContainer $dc ): string {

        $id = $this->resolvePageId($dc);

        if( $id === null ) {
            return '';
        }

        return $this->mapLangauge($this->getRootLangForPageID($id));
    }


    /**
     * {@inheritdoc}
     *
     * The language an editor translates FROM is the main language of the site
     * the record belongs to, which Contao already knows as the fallback root of
     * the same hostname. A copied record keeps no reference to its source, but
     * it does not need to: in the normal multilingual workflow the source is
     * the fallback tree, whether the record was copied or written from scratch.
     *
     * Empty when the record's own root IS the fallback (one domain per language,
     * for instance) — there is no signal then, and guessing would be worse than
     * saying nothing: a wrong source means DeepL silently finds no glossary for
     * the pair, and the editor never learns their terminology was not applied.
     */
    public function resolveSource( DataContainer $dc ): string {

        $id = $this->resolvePageId($dc);

        if( $id === null ) {
            return '';
        }

        $lang = $this->getRootFallbackLangForPageID($id);

        return $lang ? $this->mapLangauge($lang) : '';
    }


    /**
     * The id of the page a record belongs to, or null when the record cannot be
     * traced to one at all.
     *
     * 0 is a valid answer and means "no page, but carry on" — it keeps the
     * behaviour resolve() had before this method existed, where an unresolvable
     * jumpTo fell through to mapLangauge('') and therefore to en-US.
     *
     * The default keeps third-party resolvers working: those implement
     * resolve() themselves, so this is never reached for them.
     *
     * @param \Contao\DataContainer $dc
     *
     * @return int|null
     */
    protected function resolvePageId( DataContainer $dc ): ?int {

        return null;
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

        return $page->rootLanguage ?? '';
    }


    /**
     * Gets the language of the FALLBACK root for the given page id — the main
     * language of that site.
     *
     * Free of charge: loadDetails() resolves rootFallbackLanguage in the same
     * pass as rootLanguage, so this costs no extra query. Returns an empty
     * string when the page's own root IS the fallback, because the two are
     * equal then and say nothing about a source language.
     *
     * @param int $id
     *
     * @return string
     */
    protected function getRootFallbackLangForPageID( int $id ): string {

        $page = PageModel::findOneBy('id', $id);

        if( !$page ) {
            return '';
        }

        $page->loadDetails();

        $fallback = $page->rootFallbackLanguage ?? '';

        if( !$fallback || $fallback === ($page->rootLanguage ?? '') ) {
            return '';
        }

        return $fallback;
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
<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Api;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\CoreBundle\Monolog\ContaoContext;
use DeepL\DeepLException;
use DeepL\LanguageCode;
use DeepL\TranslateTextOptions;
use DeepL\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;


class DeepLApi {


    private ?string $apiKey = '';
    private CacheInterface $cache;
    private ?Translator $translator = null;
    private LoggerInterface $errorLogger;
    private string $defaultSourceLang = '';
    private array $glossaries = [];


    public function __construct( ?string $apiKey, CacheInterface $cache, LoggerInterface $errorLogger, string $defaultSourceLang='', array $glossaries=[] ) {

        $this->apiKey = $apiKey;
        $this->cache = $cache;
        $this->errorLogger = $errorLogger;
        $this->defaultSourceLang = $defaultSourceLang;
        $this->glossaries = $glossaries;

        if( $this->apiKey ) {
            $this->translator = new Translator($this->apiKey);
        }
    }


    public function isActive(): bool {

        return $this->apiKey?true:false;
    }


    public function translate( string $text, string $targetLang='', ?string $sourceLang=null ): string {

        if( !$this->translator ) {
            return '';
        }

        $sourceLang = $sourceLang ?: ($this->defaultSourceLang ?: null);
        $glossaryId = $this->getGlossaryId($sourceLang, $targetLang);

        // The glossary belongs in the cache key. Without it a translation made
        // before a glossary existed keeps being served afterwards, and changing
        // a glossary never takes effect — the editor sees the old wording and
        // has no way to tell why.
        $cacheKey = md5($text).'.'.$targetLang.($sourceLang?'.'.$sourceLang:'').($glossaryId?'.'.$glossaryId:'');
        $cached = $this->cache->getItem($cacheKey);

        if( !$cached->isHit() ) {

            try {

                $options = $glossaryId ? [TranslateTextOptions::GLOSSARY => $glossaryId] : [];

                $translation = $this->translator->translateText($text, $sourceLang, $targetLang, $options);

            } catch( DeepLException $e ) {

                $this->logException($e, __METHOD__);
                return '';
            }

            if( $translation ) {

                $cached->set($translation);
                $this->cache->save($cached);
            }
        }

        return $cached->get()??'';
    }



    /**
     * The configured glossary for a language pair, or null when there is none.
     *
     * DeepL only accepts a glossary when the source language is explicit — with
     * auto-detection it throws — so no source means no glossary, and the text is
     * translated without one. That is the deliberate outcome: translating
     * without a glossary is better than translating with a guessed source, where
     * DeepL silently finds no glossary for the pair and the editor never learns
     * their terminology was not applied.
     *
     * Glossaries exist for base languages only, while target codes carry the
     * regional variant (en-US, pt-PT), so both sides are reduced to the base
     * code for the lookup while the translation keeps the full target code.
     */
    private function getGlossaryId( ?string $sourceLang, string $targetLang ): ?string {

        if( !$sourceLang || !$targetLang || !$this->glossaries ) {
            return null;
        }

        $source = strtolower(LanguageCode::removeRegionalVariant($sourceLang));
        $target = strtolower(LanguageCode::removeRegionalVariant($targetLang));

        if( $source === $target ) {
            return null;
        }

        foreach( $this->glossaries as $pair => $id ) {

            if( strtolower((string) $pair) === $source.'-'.$target && $id ) {
                return (string) $id;
            }
        }

        return null;
    }


    public function getSupportedLanguages(): array {

        if( !$this->translator ) {
            return [];
        }

        $cacheKey = md5($this->apiKey).'.getSupportedLanguages';
        $cached = $this->cache->getItem($cacheKey);

        if( !$cached->isHit() ) {

            try {

                $languages = $this->translator->getTargetLanguages();

            } catch( DeepLException $e ) {

                $this->logException($e, __METHOD__);
                return [];
            }

            if( $languages ) {

                $cached->set($languages);
                $cached->expiresAfter(3600);
                $this->cache->save($cached);
            }
        }

        return $cached->get()??[];
    }


    private function logException( DeepLException $e, string $method ): void {

        $this->errorLogger->error(
            sprintf('DeepL API request failed: %s', $e->getMessage())
        ,   ['contao' => new ContaoContext($method, ContaoContext::ERROR)]
        );
    }
}

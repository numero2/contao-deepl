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
use DeepL\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;


class DeepLApi {


    private ?string $apiKey = '';
    private CacheInterface $cache;
    private ?Translator $translator = null;
    private LoggerInterface $errorLogger;


    public function __construct( ?string $apiKey, CacheInterface $cache, LoggerInterface $errorLogger ) {

        $this->apiKey = $apiKey;
        $this->cache = $cache;
        $this->errorLogger = $errorLogger;

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

        $cacheKey = md5($text).'.'.$targetLang.($sourceLang?'.'.$sourceLang:'');
        $cached = $this->cache->getItem($cacheKey);

        if( !$cached->isHit() ) {

            try {

                $translation = $this->translator->translateText($text, $sourceLang, $targetLang);

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

<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\EventListener\DataContainer;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Template;
use numero2\DeepLBundle\Api\DeepLApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;


class ButtonListener {


    private string $targetLang = '';
    private array $locales;

    private DeepLApi $api;
    private RequestStack $requestStack;
    private ScopeMatcher $scopeMatcher;
    private TranslatorInterface $translator;
    private string $backendRoutePrefix;
    private iterable $languageResolvers;
    private LoggerInterface $errorLogger;

    private const EXCLUDE_FIELDS = ['cssClass','cssID','class','language','urlSuffix','timeFormat','dateFormat','datimFormat','attributes','formID'];


    public function __construct( DeepLApi $api, RequestStack $requestStack, ScopeMatcher $scopeMatcher, TranslatorInterface $translator, string $backendRoutePrefix, iterable $languageResolvers, LoggerInterface $errorLogger ) {

        $this->api = $api;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->translator = $translator;
        $this->backendRoutePrefix = $backendRoutePrefix;
        $this->languageResolvers = $languageResolvers;
        $this->errorLogger = $errorLogger;

        $this->locales = Locales::getNames();
    }


    /**
     * Add xlabel callbacks to all fields we want to translate
     *
     * @param \Contao\DataContainer $dc
     */
    public function init( DataContainer $dc ): void {

        if( !$dc->id ) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if( !$request || !$this->scopeMatcher->isBackendRequest($request) ) {
            return;
        }

        // figure out which language we need to translate to
        foreach( $this->languageResolvers as $resolver ) {

            if( $resolver->supports($dc) ) {

                $this->targetLang = $resolver->resolve($dc);
                break;
            }
        }

        if( empty($this->targetLang) ) {
            return;
        }

        // check if target language is supported by DeepL
        $targetLangSupported = false;
        $supportedLangs = $this->api->getSupportedLanguages();

        if( $supportedLangs ) {

            foreach( $supportedLangs as $lang ) {

                if( strtolower($lang->code) == strtolower($this->targetLang) ) {
                    $targetLangSupported = true;
                    break;
                }
            }
        }

        // lanuage not supported
        if( !$targetLangSupported ) {

            $this->errorLogger->error(
                sprintf(
                    'The language "%s" is currently not supported by the DeepL API'
                ,   strtoupper($this->targetLang)
                )
            ,   ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );

            return;
        }

        // add translate buttons to all eligible fields
        foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'] as $name => $config ) {

            if( in_array($name, self::EXCLUDE_FIELDS) ) {
                continue;
            }

            if( empty($config['inputType']) || !in_array($config['inputType'], ['text', 'textarea', 'inputUnit', 'optionWizard']) ) {
                continue;
            }

            if( !empty($config['eval']['rgxp']) ) {
                continue;
            }

            $GLOBALS['TL_DCA'][$dc->table]['fields'][$name]['xlabel'][] = [self::class, 'addButton'];
        }
    }


    /**
     * Inject JavaScript and add button markup
     *
     * @param \Contao\DataContainer $dc
     */
    public function addButton( DataContainer $dc ) {

        $scriptSrc = 'bundles/deepl/js/backend.js';

        if( !in_array($scriptSrc, ($GLOBALS['TL_JAVASCRIPT']??[])) ) {
            $GLOBALS['TL_JAVASCRIPT'][] = $scriptSrc;
        }

        // add inline-script to TL_MOOTOOLS since there is no other way to add inline-scripts to backend
        $settings = Template::generateInlineScript("window.DeepL = { base: '".$this->backendRoutePrefix."', target: '".$this->targetLang."' };");

        if( !in_array($settings, ($GLOBALS['TL_MOOTOOLS']??[])) ) {
            $GLOBALS['TL_MOOTOOLS'][] = $settings;
        }

        $title = sprintf(
            $this->translator->trans('deepl.translate', [], 'contao_default')
        ,   $this->locales[$this->targetLang] ?? $this->targetLang
        );

        return sprintf(
            '<button class="deepl-translate" title="%s" type="button"></button>'
        ,   $title
        );
    }
}

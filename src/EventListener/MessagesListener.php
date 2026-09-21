<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use numero2\DeepLBundle\Api\DeepLApi;
use Symfony\Contracts\Translation\TranslatorInterface;


#[AsHook('getSystemMessages')]
class MessagesListener {


    private DeepLApi $api;
    private TranslatorInterface $translator;


    public function __construct( DeepLApi $api, TranslatorInterface $translator ) {

        $this->api = $api;
        $this->translator = $translator;
    }


    public function __invoke(): string {

        if( !$this->api->isActive() ) {
            return '<p class="tl_error">' . $this->translator->trans('deepl.missing_api_key', [], 'contao_default') . '</p>';
        }

        return '';
    }
}

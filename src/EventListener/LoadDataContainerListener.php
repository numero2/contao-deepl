<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use numero2\DeepLBundle\Api\DeepLApi;
use numero2\DeepLBundle\EventListener\DataContainer\ButtonListener;
use Symfony\Component\HttpFoundation\RequestStack;


#[AsHook('loadDataContainer')]
class LoadDataContainerListener {


    private DeepLApi $api;
    private RequestStack $requestStack;
    private ScopeMatcher $scopeMatcher;

    private const EXCLUDE_TABLES = ['tl_user','tl_favorites','tl_comments','tl_undo','tl_opt_in','tl_log'];


    public function __construct( DeepLApi $api, RequestStack $requestStack, ScopeMatcher $scopeMatcher ) {

        $this->api = $api;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }


    public function __invoke( string $table ): void {

        // disable in frontend
        $request = $this->requestStack->getMainRequest();
        if( !$request || !$this->scopeMatcher->isBackendRequest($request) ) {
            return;
        }

        // disable if no API key given
        if( !$this->api->isActive() ) {
            return;
        }

        // disable in certain tables and actions
        if( !Input::get('do') || in_array($table,self::EXCLUDE_TABLES) || !in_array(Input::get('act'),['edit','editAll']) ) {
            return;
        }

        if( !empty($GLOBALS['TL_DCA'][$table]) ) {

            $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = [ButtonListener::class, 'init'];
        }
    }
}

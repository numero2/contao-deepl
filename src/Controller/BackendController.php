<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use numero2\DeepLBundle\Api\DeepLApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route(defaults: ['_scope' => 'backend', '_token_check' => false])]
class BackendController extends AbstractController {


    private DeepLApi $api;


    public function __construct( DeepLApi $api ) {

        $this->api = $api;
    }


    #[Route('%contao.backend.route_prefix%/deepl/translate', name: 'deepl_translate')]
    public function translate( Request $request ): JsonResponse {

        $lang = $request->query->get('lang');
        $content = json_decode($request->getContent(), true)['content'];

        if( is_array($content) ) {

            $translation = $this->api->translate(json_encode($content), $lang);
            $translation = json_decode($translation,true);

        } else {

            $translation = $this->api->translate($content, $lang);
        }


        return new JsonResponse(['translation' => $translation, 'lang' => $lang], Response::HTTP_OK);
    }
}

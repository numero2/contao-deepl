<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use numero2\DeepLBundle\Api\DeepLApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route(defaults: ['_scope'=>'backend', '_token_check'=>false])]
class BackendController extends AbstractController {


    private DeepLApi $api;


    public function __construct( DeepLApi $api ) {

        $this->api = $api;
    }


    #[Route('%contao.backend.route_prefix%/deepl/translate', name: 'deepl_translate')]
    public function translate( Request $request ): JsonResponse {

        if( !$this->api->isActive() ) {
            return new JsonResponse(['error' => 'No DeepL API key configured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $lang = (string) $request->query->get('lang', '');
        $decoded = json_decode($request->getContent(), true);
        $content = $decoded['content'] ?? '';

        if( $lang === '' || $content === '' || $content === [] ) {
            return new JsonResponse(['error' => 'Missing parameter "lang" or "content"'], Response::HTTP_BAD_REQUEST);
        }

        if( is_array($content) ) {
            $translation = $this->api->translate(json_encode($content), $lang);
            $translation = json_decode($translation,true);

        } else {

            $translation = $this->api->translate((string) $content, $lang);
        }

        if( $translation === '' || $translation === null ) {
            return new JsonResponse(['error' => 'Translation failed', 'lang' => $lang], Response::HTTP_BAD_GATEWAY);
        }


        return new JsonResponse(['translation' => $translation, 'lang' => $lang], Response::HTTP_OK);
    }
}

<?php

namespace Drupal\drup\Helper;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DrupRequest
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupRequest {

    /**
     * @return mixed
     */
    public static function getTitle() {
        $request = \Drupal::request();
        $route = \Drupal::routeMatch()->getRouteObject();

        return \Drupal::service('title_resolver')->getTitle($request, $route);
    }

    /**
     * @param \Symfony\Component\Routing\Route|null $route
     *
     * @return mixed
     */
    public static function isAdminRoute(\Symfony\Component\Routing\Route $route = null) {
        $adminContext = \Drupal::service('router.admin_context');

        if ($route === null) {
            $route = \Drupal::routeMatch()->getRouteObject();
        }

        return $adminContext->isAdminRoute($route);
    }

    /**
     * Determines if the current route is on layout builder admin
     *
     * @param \Symfony\Component\Routing\Route|null $route
     *
     * @return bool
     */
    public static function isLayoutBuilderRoute(\Symfony\Component\Routing\Route $route = null): bool {
        if ($route === null) {
            $route = \Drupal::routeMatch()->getRouteObject();
        }

        return $route->getPath() === '/node/{node}/layout';
    }

    /**
     * @return FormattableMarkup
     */
    public static function get404Content() {
        $drupRouter = \Drupal::service('drup_router');

        $content404 = '<h2 class="title--h3">' . t('You may have followed a broken link, or tried to view a page that no longer exists.') . '</h2>';
        if ($contact = $drupRouter->getPath('contact')) {
            $content404 .= '<p>' . t('If the problem persists, <a href=":link">contact us</a>.', [':link' => $contact]) . '</p>';
        }
        $content404 .= '<p><a href="' . Url::fromRoute('<front>')->toString() . '" class="btn btn--primary">' . t('Back to the front page') . '</a></p>';

        return new FormattableMarkup($content404, []);
    }

    /**
     * @return array
     */
    public static function getArgs() {
        return \Drupal::routeMatch()->getParameters()->all();
    }

    /**
     * @return string|null
     */
    public static function getRouteName() {
        return \Drupal::routeMatch()->getRouteName();
    }

    /**
     * Nom de la route de la page courante hors contexte ajax (ex : dans un view ajax)
     *
     * @return string|null
     */
    public static function getRefererRouteName() {
        $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
        $fakeRequest = Request::create($previousUrl);

        /** @var \Drupal\Core\Url $url */
        if ($url = \Drupal::service('path.validator')->getUrlIfValid($fakeRequest->getRequestUri())) {
            return $url->getRouteName();
        }

        return null;
    }

    /**
     * Title de la route de la page courante hors contexte ajax (ex : dans un view ajax)
     *
     * @return string|null
     */
    public static function getRefererTitle() {
        $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
        $fakeRequest = Request::create($previousUrl);

        /** @var \Symfony\Component\Routing\Route $route */
        if ($route = \Drupal::service('router.route_provider')->getRouteByName('view.search.page')) {
            return $route->hasDefault('_title') ? $route->getDefault('_title') : null;
        }

        return null;
    }

    /**
     * @return bool
     */
    public static function isFront() {
        return \Drupal::service('path.matcher')->isFrontPage();
    }

    /**
     * Récupère les paramètres de l'url précédente
     *
     * @return array
     */
    public static function getPreviousRouteParameters() {
        $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
        $fakeRequest = Request::create($previousUrl);

        /** @var \Drupal\Core\Url $url * */
        $url = \Drupal::service('path.validator')->getUrlIfValid($fakeRequest->getRequestUri());

        if ($url) {
            return $url->getRouteParameters();
        }

        return [];
    }
}

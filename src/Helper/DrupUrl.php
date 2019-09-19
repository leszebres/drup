<?php

namespace Drupal\drup\Helper;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DrupUrl
 *
 * Méthodes globales pour le traitement des urls
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupUrl {

    /**
     * Remplacement d'un argument dans la queryString
     *
     * @param $argument
     * @param $value
     * @param $queryString
     *
     * @return string
     */
    public static function replaceArgument($argument, $value, $queryString): string {
        $separator = !empty($queryString) ? '&' : null;
        $replace = strpos($queryString, $argument) !== false;

        return '?' . ($replace ? preg_replace('/' . $argument . '\=[a-z0-9]+/i', $argument . '=' . $value, $queryString) : $queryString . $separator . $argument . '=' . $value);
    }

    /**
     * Retourne le chemin absolu
     *
     * @param null $relativePath
     * @param null $baseUrl
     *
     * @return string
     */
    public static function getAbsolutePath($relativePath = null, $baseUrl = null) {
        if ($baseUrl === null) {
            $baseUrl = Request::createFromGlobals()->getSchemeAndHttpHost();
        }
        if ($relativePath === null) {
            $relativePath = \Drupal::service('path.current')->getPath();
        }

        return $baseUrl . $relativePath;
    }

    /**
     * Load l'entité de contenu Drupal depuis une entité Url
     *
     * @param \Drupal\Core\Url $url
     *
     * @return \Drupal\Core\Entity\EntityInterface|null
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function loadEntity(Url $url) {
        if (!$url->isExternal() && $url->isRouted()) {
            $urlParameters = $url->getRouteParameters();

            if (!empty($urlParameters)) {
                $entityType = current(array_keys($urlParameters));
                $entityId = current(array_values($urlParameters));

                return \Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);
            }
        }

        return null;
    }
}

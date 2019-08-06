<?php

namespace Drupal\drup\TwigExtension;

use Drupal\Component\Utility\UrlHelper;

/**
 * Class UrlTitle
 *
 * Strips protocols from url
 *
 * @example https://www.leszebres.fr|url_title => www.leszebres.fr
 *
 * @package Drupal\drup\TwigExtension
 */
class UrlTitle extends \Twig_Extension {

    /**
     * @return array|\Twig\TwigFilter[]
     */
    public function getFilters() {
        return [new \Twig_SimpleFilter('url_title', [$this, 'filter'])];
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function filter($string) {
        $string = UrlHelper::filterBadProtocol($string);
        $string = str_replace(['http://', 'https://'], '', $string);

        return $string;
    }
}

<?php

namespace Drupal\drup\TwigExtension;

use Drupal\drup\Helper\DrupString;

/**
 * Class PhoneNumber
 *
 * Sanitize a phone number for link src attribute with tel: prefix
 *
 * @example <a href="{{ '05 55 55 55 55'|phone_number }}"></a> => <a href="tel:0555555555"></a>
 *
 * @package Drupal\drup\TwigExtension
 */
class PhoneNumber extends \Twig_Extension {

    /**
     * @return array|\Twig\TwigFilter[]
     */
    public function getFilters() {
        return [new \Twig_SimpleFilter('phone_number', [$this, 'phoneNumber'])];
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function phoneNumber($string) {
        return DrupString::formatPhoneNumber($string, 'tel:');
    }
}

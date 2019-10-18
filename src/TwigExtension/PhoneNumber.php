<?php

namespace Drupal\drup\TwigExtension;

use Drupal\drup\Helper\DrupString;

/**
 * Class PhoneNumber
 *
 * Format a phone number with separators
 *
 * @example {{ '+33655555555'|phone_number }} => +33 6 55 55 55 55
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
        return DrupString::formatPhoneNumber($string);
    }

}

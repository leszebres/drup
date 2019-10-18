<?php

namespace Drupal\drup\Helper;

use Drupal\Component\Utility\Unicode;

/**
 * Class DrupString
 *
 * Méthodes globales pour le traitement des chaines de caractères
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupString {

    /**
     * Tronquer
     *
     * @param $string
     * @param int $maxLength
     * @param string|bool $stripTags false pour désactiver
     *
     * @return string
     */
    public static function truncate($string, $maxLength = 250, $stripTags = null) {
        if ($stripTags !== false) {
            $string = strip_tags($string, $stripTags);
        }

        $string = str_replace(PHP_EOL, '', $string);

        if ($maxLength > 0) {
            $string = Unicode::truncate($string, $maxLength, true, true);
        }

        return $string;
    }

    /**
     * Formatage d'un numéro de téléphone
     *
     * @param string $phone Numéro
     * @param string $separator Séparateur des nombres
     *
     * @return string
     */
    public static function formatPhoneNumber($phone, $separator = ' ') {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

        switch ($langcode) {
            case 'fr':
            default:
                $split = str_split($phone, 2);

                if (strncmp($phone, '+', 1) === 0) {
                    $split[0] .= substr($split[1], 0, 1);
                    $split[1] = substr($split[1], 1);
                }
                break;
        }

        if (!empty($split)) {
            $phone = implode($separator, $split);
        }

        return $phone;
    }

    /**
     * Enlève les caractères spéciaux d'un numéro de téléphone
     *
     * @param $phone
     *
     * @return mixed
     */
    public static function cleanPhoneNumber($phone) {
        return str_replace([' ', '.'], '', $phone);
    }

    /**
     * Convert string to camelCase format
     *
     * @param $string
     * @param bool $capitalizeFirstCharacter
     *
     * @return string
     */
    public static function toCamelCase($string, $capitalizeFirstCharacter = true, $wordSeparator = '_') {
        $string = ucfirst(strtolower($string));
        $string = str_replace($wordSeparator, '', ucwords($string, $wordSeparator));

        if (!$capitalizeFirstCharacter) {
            $string = lcfirst($string);
        }

        return $string;
    }

    /**
     * Explode PHP_EOL and wrap new lines between html tag
     *
     * @param $string
     * @param string $tag
     *
     * @return string
     */
    public static function nl2Html($string, $tag = 'p') {
        $content = '';

        foreach (explode("\n", $string) as $line) {
            if (trim($line)) {
                $content .= '<'.$tag.'>' . $line . '</'.$tag.'>';
            }
        }

        return $content;
    }

    /**
     * Enlève les attributs dans une string
     *
     * @param string $string
     * @param array  $attributes
     *
     * @return string|null
     */
    public static function removeAttributes(string $string, array $attributes) {
        $regex = null;

        foreach ($attributes as $attribute) {
            if ($regex !== null) {
                $regex .= '|';
            }

            $regex .= '\s' . $attribute . '="\w"*';
        }

        return preg_replace('/' . $regex . '/m', '', $string);
    }

    /**
     * Extrait la/les email(s) dans une chaîne de caractère
     *
     * @param string $string
     *
     * @return array|null
     */
    public static function extractEmails($string) {
        $regexp = '/([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+/i';
        //$regexp = '(?:[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])';
        preg_match_all($regexp, $string, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extrait la/les url(s) dans une chaîne de caractère
     *
     * @param string $string
     *
     * @return array|null
     */
    public static function extractUrls($string) {
        $regexp = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
        //$regexp = '(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
        preg_match_all($regexp, $string, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extrait la/les url(s) dans une chaîne de caractère
     *
     * @param string $string
     *
     * @return array|null
     */
    public static function extractPhoneNumbers($string) {
        $regexp = '/\+?[0-9][0-9()\-\s+]{4,20}[0-9]/';
        //$regexp = '(?:(?:\+?([1-9]|[0-9][0-9]|[0-9][0-9][0-9])\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([0-9][1-9]|[0-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?';
        preg_match_all($regexp, $string, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extrait des liens d'une chaîne de caractères et les remplace par leur correspondant HTML selon le type
     *
     * @param $string
     * @param array $types
     *
     * @return mixed
     */
    public static function stringToLinks($string, $types = ['emails', 'phone_numbers', 'urls']) {
        if (in_array('emails', $types)) {
            if ($emails = self::extractEmails($string)) {
                foreach ($emails as $email) {
                    $string = str_replace($email, '<a href="mailto:'.$email.'" target="_blank">'.$email.'</a>', $string);
                }
            }
        }
        if (in_array('urls', $types)) {
            if ($urls = self::extractUrls($string)) {
                foreach ($urls as $url) {
                    $string = str_replace($url, '<a href="'.$url.'" target="_blank">'.$url.'</a>', $string);
                }
            }
        }
        if (in_array('emails', $types)) {
            if ($phoneNumbers = self::extractPhoneNumbers($string)) {
                foreach ($phoneNumbers as $phoneNumber) {
                    $string = str_replace($phoneNumber, '<a href="tel:'.self::formatPhoneNumber($phoneNumber).'" target="_blank">'.$phoneNumber.'</a>', $string);
                }
            }
        }

        return $string;
    }
}

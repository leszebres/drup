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
     * Formattage d'un numéro de téléphone
     *
     * @param string $phone Numéro
     * @param null $prefix Préfix du numéro (+33 ou tel:)
     *
     * @return string
     */
    public static function formatPhoneNumber($phone, $prefix = null) {
        // Remplacement des caractères spéciaux
        $phone = str_replace([' ', '.'], '', $phone);

        if (!empty($phone) && $prefix !== null) {
            // Ajout de l'indicatif région (+33)
            if (strncmp($prefix, '+', 1) === 0) {
                $phone = $prefix . substr($phone, 1);
            }
            // Ajout du prefix custom (tel:)
            else {
                $phone = $prefix . $phone;
            }
        }

        return $phone;
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
}

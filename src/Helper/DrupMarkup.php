<?php

namespace Drupal\drup\Helper;


use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;

/**
 * Class DrupMarkup
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupMarkup {

    /**
     * Rendu d'un lien
     *
     * @param           $text
     * @param           $path
     * @param Attribute $attributes
     *
     * @return \Drupal\Component\Render\MarkupInterface|string
     */
    public static function l($text, $path, Attribute $attributes = null) {
        // Options par défaut
        $options = [
            '@text' => $text,
            ':url' => $path,
            '@attributes' => null
        ];

        // Ajout des attributs
        if ($attributes !== null) {
            $options['@attributes'] = $attributes;
        }

        // Lien absolu ?
        $matches = [];
        if ((preg_match('/https*:\/\//i', $options[':url'], $matches) !== false) && !empty($matches)) {
            $host = \Drupal::request()->getHost();

            if (strpos($options[':url'], $host) === false) {
                if ($options['@attributes'] instanceof Attribute) {
                    if (!array_key_exists('target', $options['@attributes']->storage())) {
                        $options['@attributes']->setAttribute('target', '_blank');
                    }

                } else {
                    $options['@attributes'] = new Attribute(['target' => '_blank']);
                }
            }
        }

        // Markup
        $markup = new FormattableMarkup('<a href=":url"@attributes><span>@text</span></a>', $options);

        return Markup::create($markup->__toString());
    }
}
<?php

namespace Drupal\drup\Media;

use Drupal\media\IFrameMarkup;

/**
 * Class DrupFile
 *
 * @package Drupal\drup\Media
 */
class DrupOEmbed {

    /**
     * @var string
     */
    protected $url;

    /**
     * @var \Drupal\media\OEmbed\Resource|null
     */
    protected $oEmbedResource;

    /**
     * DrupOEmbed constructor.
     *
     * @param string $url
     */
    public function __construct(string $url) {
        $this->url = $url;

        $this->oEmbedResource = self::fetchResourceByUrl($this->url);
    }

    /**
     * @return \Drupal\media\OEmbed\Resource|null
     */
    public function getResource() {
        return $this->oEmbedResource;
    }

    /**
     * @return mixed|string
     */
    public function getIncreasedThumbnailUrl() {
        return self::getOEmbedThumbnailUrl($this->getResource(), true);
    }

    /**
     * Get thumbnail url from OEmbed resource
     *
     * @param \Drupal\media\OEmbed\Resource $resource
     * @param bool $increaseSize
     *
     * @return mixed|string
     */
    public static function getOEmbedThumbnailUrl(\Drupal\media\OEmbed\Resource $resource, bool $increaseSize = true) {
        $url = null;

        if ($uri = $resource->getThumbnailUrl()) {
            $url = $uri->getUri();

            // Replace url with bigger format
            if ($increaseSize === true) {
                switch (strtolower($resource->getProvider()->getName())) {
                    case 'youtube':
                        $url = str_replace('hqdefault', 'maxresdefault', $url);
                        break;

                    case 'vimeo':
                        if (($width = $resource->getThumbnailWidth()) && ($height = $resource->getThumbnailHeight())) {
                            $url = str_replace($width . 'x' . $height, '800x450', $url);
                        }
                        break;
                }
            }
        }

        return $url;
    }

    /**
     * Encode media public url into OEmbed resource
     *
     * @param string $url
     *
     * @return \Drupal\media\OEmbed\Resource|null
     */
    public static function fetchResourceByUrl(string $url) {
        if ($oEmbedUrl = \Drupal::service('media.oembed.url_resolver')->getResourceUrl($url)) {
            if ($resource = \Drupal::service('media.oembed.resource_fetcher')->fetchResource($oEmbedUrl)) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Generate an iframe with a public media url
     *
     * @param $url
     * @param bool $asMarkup
     *
     * @return \Drupal\Component\Render\MarkupInterface|string|null
     */
    public static function generateIframe($url, $asMarkup = true) {
        if ($resource = self::fetchResourceByUrl($url)) {
            $iframe = $resource->getHtml();

            // unset width/height attributes
            $iframe = preg_replace('/(width|height)="\d*"\s/', '', $iframe);

            return $asMarkup ? IFrameMarkup::create($iframe) : $iframe;
        }

        return null;
    }
}

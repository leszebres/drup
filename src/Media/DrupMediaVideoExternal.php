<?php

namespace Drupal\drup\Media;

use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;
use Drupal\media\IFrameMarkup;

/**
 * Class DrupMediaVideoExternal
 *
 * @package Drupal\drup\Media
 */
class DrupMediaVideoExternal extends DrupMedia {

    /**
     * DrupMediaVideoExternal constructor.
     *
     * @param int|int[]|Media|Media[] $medias
     * @param null $fileField
     */
    public function __construct($medias, $fileField = null) {
        $this->type = 'video_external';

        if ($fileField === null) {
            $fileField = 'field_media_oembed_video';
        }

        parent::__construct($medias, $fileField);
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function renderMedias($attributes = []) {
        $medias = [];

        if (!empty($this->mediasData)) {
            foreach ($this->mediasData as $index => $media) {
                $medias[] = $this->renderMedia($index, $attributes);
            }
        }

        return $medias;
    }

    /**
     * @return array
     */
    public function getMediasUrl() {
        $urls = [];

        foreach ($this->mediasData as $index => $media) {
            $urls[] = $this->getMediaUrl($index);
        }

        return $urls;
    }

    /**
     * @return array
     */
    public function getMediasData() {
        $data = [];

        foreach ($this->mediasData as $index => $media) {
            $data[] = $this->getMediaData($index);
        }

        return $data;
    }


    /**
     * Render iframe returned by OEmbed service
     *
     * @param int $index
     * @param array $attributes
     *
     * @return bool
     */
    protected function renderMedia($index = 0, $attributes = []) {
        if (isset($this->mediasData[$index])) {
            // todo theme custom avec title / desc / iframe / image
            return self::generateIframe($this->mediasData[$index]->field_value);
        }

        return false;
    }

    /**
     * Return the src attribute of the OEmbed iframe
     *
     * @param int $index
     *
     * @return bool|\Drupal\Core\GeneratedUrl|string|null
     */
    protected function getMediaUrl($index = 0) {
        if (isset($this->mediasData[$index])) {
            if ($iframe = self::generateIframe($this->mediasData[$index]->field_value)) {
                preg_match('/src="([^"]+)"/', $iframe, $iframeSrc);

                if (isset($iframeSrc[1]) && is_string($iframeSrc[1])) {
                    return $iframeSrc[1];
                }
            }
        }

        return false;
    }

    /**
     * Info about OEmbed fetched resource
     *
     * @param int $index
     *
     * @return array
     */
    protected function getMediaData($index = 0) {
        $data = [];

        if (isset($this->mediasData[$index]) && ($oEmbedResource = self::fetchResource($this->mediasData[$index]->field_value))) {
            $iframe = self::generateIframe($this->mediasData[$index]->field_value);
            $thumbnail = self::getThumbnailUrl($oEmbedResource);
            $name = $this->mediasData[$index]->entity->getName();

            $data = [
                'url' => $this->mediasData[$index]->field_value,
                'name' => $this->mediasData[$index]->entity->getName(),
                'legend' => $this->getLegend($index),
                'oembed' => $oEmbedResource,
                'iframe' => $iframe,
                'iframe_url' => self::extractIframeUrl($iframe),
                'thumbnail' => self::generateThumbnail($thumbnail, $name),
                'thumbnail_url' => $thumbnail
            ];
        }

        return $data;
    }

    /**
     * Get thumbnail url from OEmbed resource
     *
     * @param \Drupal\media\OEmbed\Resource $resource
     * @param bool $increaseSize
     *
     * @return mixed|string
     */
    public static function getThumbnailUrl(\Drupal\media\OEmbed\Resource $resource, bool $increaseSize = true) {
        $url = null;

        if ($uri = $resource->getThumbnailUrl()) {
            $url = $uri->getUri();

            // Replace url with bigger format
            if ($increaseSize === true) {
                switch (strtolower($resource->getProvider()->getName())) {
                    case 'youtube':
                        $url = str_replace('hqdefault', 'maxresdefault',  $url);
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
     * @param string $url
     *
     * @return \Drupal\Component\Render\MarkupInterface|string
     */
    public static function generateThumbnail(string $url, string $alt = null) {
        $output = '<img src="' . $url . '"';

        if ($alt !== null) {
            $output .= ' alt="' . $alt . '"';
        }

        $output .= ' />';

        return Markup::create($output);
    }

    /**
     * Encode media public url into OEmbed resource
     *
     * @param $url
     *
     * @return \Drupal\media\OEmbed\Resource|null
     */
    public static function fetchResource($url) {
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
        if ($resource = self::fetchResource($url)) {
            $iframe = $resource->getHtml();

            // unset width/height attributes
            $iframe = preg_replace('/(width|height)="\d*"\s/', '', $iframe);

            return $asMarkup ? IFrameMarkup::create($iframe) : $iframe;
        }

        return null;
    }

    /**
     * Extrait la source d'une iframe
     *
     * @param $string
     *
     * @return mixed|null
     */
    public static function extractIframeUrl($string) {
        if ($string instanceof Markup) {
            $string = $string->__toString();
        }

        preg_match('/src="([^"]+)"/', $string, $match);

        return $match[1] ?? null;
    }
}

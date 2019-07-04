<?php

namespace Drupal\drup\Media;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Class DrupMedia
 *
 * @package Drupal\drup\Media
 */
class DrupMedia {

    /**
     * Media entities list
     *
     * @var \Drupal\media\Entity\Media[]
     */
    protected $mediasList = [];

    /**
     * Data for each medias
     *
     * @var array
     */
    protected $mediasData = [];

    /**
     * Media type (ex : Image or File)
     *
     * @var string
     */
    protected $type;

    /**
     * Media entity field representing File entity
     *
     * @var string
     */
    protected $filesFieldName;

    /**
     * Current language id
     *
     * @var string
     */
    protected $languageId;

    /**
     * DrupMedia constructor.
     *
     * @param int|int[]|Media|Media[] $medias
     * @param null $fileField
     */
    public function __construct($medias, $fileField = null) {
        $this->languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();

        if (!empty($medias)) {
            $this->filesFieldName = $this->formatFieldName($fileField);
            $this->mediasList = $this->formatMedias($medias);

            $this->mediasData = $this->getData();
        }
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return empty($this->mediasData);
    }

    /**
     * Get the media legend (from media entity field)
     *
     * @param int $index
     * @param string $fieldName
     *
     * @return \Drupal\Component\Render\MarkupInterface|string|null
     */
    public function getLegend($index = 0, $fieldName = 'field_description') {
        if (isset($this->mediasData[$index]) && $this->mediasData[$index]->entity->hasField($fieldName)) {
            if ($legend = $this->mediasData[$index]->entity->get($fieldName)->value) {
                return Markup::create(nl2br($legend));
            }
        }

        return null;
    }

    /**
     * Retourne l'uri de la miniature générée du Media
     *
     * @param int $index
     * @param string $fieldName
     *
     * @return null|string
     */
    public function getGeneratedThumbnailUri($index = 0, $fieldName = 'field_thumbnail_uri') {
        if (isset($this->mediasData[$index]) && $this->mediasData[$index]->entity->hasField($fieldName)) {
            return $this->mediasData[$index]->entity->get('field_thumbnail_uri')->value;
        }

        return null;
    }


    /**
     * Récupère l'entité du File principal référencé dans le Media
     *
     * @param \Drupal\media\Entity\Media $media
     * @param $fieldName
     *
     * @return \Drupal\Core\Entity\EntityInterface|\Drupal\file\Entity\File|mixed|null
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public static function getReferencedFile(Media $media, $fieldName) {
        if ($media->hasField($fieldName)) {
            /** @var FieldItemBase $fileReferenced */
            $fileReferenced = $media->get($fieldName)->first();

            if (!$fileReferenced->isEmpty() && !empty($fileReferenced->getValue())) {
                if ($fileReferenced->target_id !== null && ($fileEntity = File::load($fileReferenced->target_id)) && $fileEntity instanceof File) {
                    return $fileEntity;
                }

                return $fileReferenced->value;
            }
        }

        return null;
    }

    /**
     * Standardize media sources into array of media entities
     *
     * @param $medias
     *
     * @return array
     */
    protected function formatMedias($medias) {
        $entities = [];

        if (!is_array($medias)) {
            $medias = [$medias];
        }

        foreach ($medias as $media) {
            if ($entity = ($media instanceof Media) ? $media : $this->loadMedia($media)) {
                $entities[] = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $this->languageId);
            }
        }

        return $entities;
    }

    /**
     * Get Media's file entities info
     *
     * @return array
     */
    protected function getData() {
        $data = [];

        if (!empty($this->mediasList)) {
            foreach ($this->mediasList as $mediaEntity) {
                if ($mediaEntity->hasField($this->filesFieldName)) {
                    /** @var FieldItemBase $fileReferenced */
                    $fileReferenced = $mediaEntity->get($this->filesFieldName)->first();

                    if (!$fileReferenced->isEmpty() && !empty($fileReferenced->getValue())) {
                        $item = [
                            'entity' => $mediaEntity,
                            'field' => $fileReferenced,
                            'field_value' => self::getReferencedFile($mediaEntity, $this->filesFieldName)
                        ];

                        $data[] = (object) $item;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $mid
     *
     * @return \Drupal\Core\Entity\EntityInterface|Media|null
     */
    protected function loadMedia($mid) {
        if (($mediaEntity = Media::load($mid)) && $mediaEntity instanceof Media) {
            return $mediaEntity;
        }

        return null;
    }

    /**
     * @param $fid
     *
     * @return \Drupal\Core\Entity\EntityInterface|Media|null
     */
    protected function loadFile($fid) {
        if (($fileEntity = Media::load($fid)) && $fileEntity instanceof File) {
            return $fileEntity;
        }

        return null;
    }

    /**
     * @param $fieldName
     *
     * @return string
     */
    protected function formatFieldName($fieldName) {
        return $fieldName ?? 'field_media_' . $this->type;
    }
}

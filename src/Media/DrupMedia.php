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
    protected $mediasList;

    /**
     * Data for each medias
     *
     * @var
     */
    protected $mediasData;

    /**
     * Media type (ex : Image or File)
     *
     * @var
     */
    protected $type;

    /**
     * Media entity field representing File entity
     *
     * @var
     */
    protected $filesField;

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

        $this->filesField = $this->formatFieldName($fileField);
        $this->mediasList = $this->formatMedias($medias);

        $this->mediasData = $this->getData();
    }

    /**
     * Get the media legend (from media entity field)
     *
     * @param int $index
     *
     * @return \Drupal\Component\Render\MarkupInterface|string|null
     */
    public function getLegend($index = 0) {
        if (isset($this->mediasData[$index]) && $this->mediasData[$index]->entity->hasField('field_description')) {
            if ($legend = $this->mediasData[$index]->entity->get('field_description')->value) {
                return Markup::create(nl2br($legend));
            }
        }

        return null;
    }

    /**
     * Standardize media sources into array of media entities
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
                if ($mediaEntity->hasField($this->filesField)) {

                    /** @var FieldItemBase $fileReferenced */
                    $fileReferenced = $mediaEntity->get($this->filesField)->first();

                    if (!$fileReferenced->isEmpty() && !empty($fileReferenced->getValue())) {
                        $item = [
                            'entity' => $mediaEntity,
                            'field' => $fileReferenced
                        ];

                        if ($fileReferenced->target_id !== null && ($fileEntity = File::load($fileReferenced->target_id)) && $fileEntity instanceof File) {
                            $item['field_value'] = $fileEntity;
                        } else {
                            $item['field_value'] = $fileReferenced->value;
                        }

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

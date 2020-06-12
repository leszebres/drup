<?php

namespace Drupal\drup_gdpr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class WebformSubmissionsDeletionController
 *
 * @package Drupal\drup_gdpr\Controller
 */
class WebformSubmissionsDeletionController extends ControllerBase {

    /**
     * Delete webform_submission entities after time by creation date
     *
     * @param string $from
     *
     */
    public function deleteAfter(string $from = '1 years') {
        $date = new DrupalDateTime();
        $date->modify('- ' . $from);

        $entities = $this->getFrom($date);

        if (!empty($entities)) {
            \Drupal::entityTypeManager()->getStorage('webform_submission')->delete($entities);

            \Drupal::logger('drup_gdpr')->notice($this->t('@count webform submissions have been deleted since @date', [
                '@count' => count($entities),
                '@date' => $date->format('Y-d-m H:i:s')
            ]));
        }
    }

    /**
     * Get webform_submission entities after time by creation date
     *
     * @param \Drupal\Core\Datetime\DrupalDateTime $date
     *
     * @return array|\Drupal\Core\Entity\EntityBase[]|\Drupal\Core\Entity\EntityInterface[]
     */
    protected function getFrom(DrupalDateTime $date) {
        $entities = \Drupal::entityQuery('webform_submission')
            ->condition('created', $date->getTimestamp(), '<=')
            ->execute();

        return !empty($entities) ? WebformSubmission::loadMultiple($entities) : [];
    }
}

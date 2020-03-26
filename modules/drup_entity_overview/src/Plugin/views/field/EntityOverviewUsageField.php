<?php

namespace Drupal\drup_entity_overview\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drup_entity_overview\EntityOverviewUsageManager;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_overview_usage_field")
 */
class EntityOverviewUsageField extends FieldPluginBase {

    /**
     * The current display.
     *
     * @var string
     *   The current display of the view.
     */
    protected $currentDisplay;

    /**
     * @var EntityOverviewUsageManager
     */
    protected $entityOverviewUsageManager;

    /**
     * {@inheritdoc}
     */
    public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = null) {
        parent::init($view, $display, $options);
        $this->currentDisplay = $view->current_display;

        $this->entityOverviewUsageManager = \Drupal::service('drup_entity_overview.entity_usage');

    }

    /**
     * {@inheritdoc}
     */
    public function usesGroupBy() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function query() {
        // Do nothing -- to override the parent query.
    }

    /**
     * {@inheritdoc}
     */
    protected function defineOptions() {
        $options = parent::defineOptions();

        // First check whether the field should be hidden if the value(hide_alter_empty = TRUE) /the rewrite is empty (hide_alter_empty = FALSE).
        $options['hide_alter_empty'] = [
            'default' => false,
        ];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResultRow $resultRow) {
        //$states = workflow_get_workflow_state_names();
        //return $states[$node->get('field_phase')->getValue()[0]['value']];

        if ($entity = $resultRow->_entity) {
            return $this->entityOverviewUsageManager->isReferenced($entity->getEntityTypeId(), $entity->bundle(), $entity->id()) ? $this->t('Yes') : $this->t('No');
        }

        return null;
    }

}

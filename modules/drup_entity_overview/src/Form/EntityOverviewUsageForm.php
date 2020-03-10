<?php

namespace Drupal\drup_entity_overview\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\drup\Helper\DrupRequest;
use Drupal\drup_entity_overview\EntityOverviewUsageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOverviewUsageForm
 *
 * @package Drupal\drup_entity_overview\Form
 */
class EntityOverviewUsageForm extends FormBase {

    /**
     * @var \Drupal\drup_entity_overview\EntityOverviewUsageManager
     */
    private $entityOverviewUsageManager;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    private $entityTypeManager;

    /**
     * @inheritDoc
     */
    public function getFormId() {
        return 'drup_entity_overview_usage_form';
    }

    /**
     * EntityOverviewUsageForm constructor.
     *
     * @param EntityOverviewUsageManager $entityOverviewUsageManager
     */
    public function __construct(EntityOverviewUsageManager $entityOverviewUsageManager, EntityTypeManagerInterface $entityTypeManager) {
        $this->entityOverviewUsageManager = $entityOverviewUsageManager;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('drup_entity_overview.entity_usage'),
            $container->get('entity_type.manager')
        );
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state, string $entityType = null, string $entityId = null) {
        // Reset cache
        $form['#cache']['max-age'] = 0;

        $form['#prefix'] = '<div id="drup-entity-overview-usage-form">';
        $form['#suffix'] = '</div>';

        $form['#attached']['library'][] = 'views/views.module';
        $form['#attached']['library'][] = 'drup_entity_overview/form';

        $form['header'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['views-exposed-form']
            ],
            '#prefix' => '<div id="drup-entity-overview-usage-form-header">',
            '#suffix' => '</div>'
        ];

        $isMainRoute = \Drupal::routeMatch()->getRouteName() === 'drup_entity_overview.entity_usage';

        // Global form from system menu
        if ($isMainRoute) {
            if ($form_state->getValue('entity_type') === null) {
                $form_state->setValue('entity_type', $entityType);
            }
            if ($form_state->getValue('entity') === null) {
                $form_state->setValue('entity', $entityId);
            }
            $entity = $entityId ? $this->loadEntity($form_state->getValue('entity_type'), $form_state->getValue('entity')) : null;
        }
        // Or setted with specific entity
        else {
            $args = DrupRequest::getArgs();
            /** @var EntityInterface $entity */
            $entity = current($args);

            $form_state->setValue('entity_type', $entity->getEntityTypeId());
            $form_state->setValue('entity', $entity->id());

            $form['header']['#disabled'] = 'disabled';
        }

        /** @var EntityType[] $entityTypes */
        $entityTypes = array_filter($this->entityTypeManager->getDefinitions(), static function (EntityType $item) {
            return $item->getGroup() === 'content';
        });
        $entityTypesList = ['' => $this->t('Select')];
        foreach ($entityTypes as $id => $item) {
            $entityTypesList[$id] = $item->getLabel();
        }
        $form['header']['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('The entity type'),
            '#default_value' => $form_state->getValue('entity_type'),
            '#options' => $entityTypesList,
            '#ajax' => [
                'callback' => '::ajaxFormCallback',
                'event' => 'change',
                'wrapper' => 'drup-entity-overview-usage-form',
            ],
        ];

        if (!empty($form_state->getValue('entity_type'))) {
            $form['header']['entity'] = [
                '#type' => 'entity_autocomplete',
                '#title' => $this->t('The referenced entity'),
                '#target_type' => $form_state->getValue('entity_type'),
                '#default_value' => $entity,
                '#required' => true
            ];
            $form['header']['actions'] = [
                '#type'  => 'actions'
            ];
            $form['header']['actions']['submit'] = [
                '#type'  => 'submit',
                '#value' => $this->t('Search'),
                '#attributes' => []
            ];
        }

        $form['results'] = [
            '#type' => 'container',
            '#prefix' => '<div id="drup-entity-overview-usage-form-results">',
            '#suffix' => '</div>',
            'entities' => []
        ];
        $this->getResults($form, $form_state);

        return $form;
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function getResults(array &$form, FormStateInterface $form_state) {
        $form['results']['no_results'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('This entity is not used anywhere.')
        ];

        if ($entity = $this->loadEntity($form_state->getValue('entity_type'), $form_state->getValue('entity'))) {
            if ($referencedEntities = $this->entityOverviewUsageManager->getReferencingEntities($entity->getEntityTypeId(), $entity->bundle(), $entity->id())) {
                $defaultTable = [
                    '#type' => 'table',
                    '#header' => [
                        $this->t('Title'),
                        $this->t('Status'),
                    ],
                    '#rows' => [],
                ];

                foreach ($referencedEntities as $entityType => $entities) {
                    $entityTypeLabel = $this->entityTypeManager->getDefinition($entityType)->getLabel();
                    $entityBundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType);
                    $parentEntity = null;

                    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
                    foreach ($entities as $index => $entity) {
                        if ($entity->getEntityType()->id() === 'paragraph') {
                            $parentEntity = $this->entityOverviewUsageManager::getParagraphParentEntity($entity);

                            if ($parentEntity === null) {
                                continue;
                            }
                        }

                        $bundle = $entity->bundle();
                        $field = $entityType . '__' . $bundle;

                        if (!isset($form['results']['entities'][$field])) {
                            $bundleLabel = $entityBundles[$entity->bundle()]['label'];

                            $form['results']['entities'][$field] = [
                                '#type' => 'details',
                                '#title' => $bundleLabel . ' (' . $entityTypeLabel . ')',
                                'items' => [
                                    '#type' => 'html_tag',
                                    '#tag' => 'div',
                                    '#rows' => [],
                                ]
                            ];
                        }

                        if ($parentEntity !== null) {
                            $title = $parentEntity->toLink(null, 'edit-form');
                            $status = $parentEntity->isPublished();

                        } else {
                            $title = $entity->hasLinkTemplate('canonical') ? $entity->toLink(null, 'edit-form') : $entity->label();
                            $status = $entity->isPublished();
                        }

                        if ($title instanceof Link) {
                            $title = '<a href="' . $title->getUrl()->toString() . '" target="_blank">'. $title->getText() . '</a>';
                        }

                        $form['results']['entities'][$field]['items']['#rows'][] = [
                            //$entity->id(),
                            Markup::create($title),
                            $status ? $this->t('Published') : $this->t('Unpublished'),
                        ];
                    }
                }

                foreach ($form['results']['entities'] as $index => $field) {
                    if (is_array($field['items']['#rows'])) {
                        $form['results']['entities'][$index]['#title'] .= ' - ' . count($field['items']['#rows']);

                        $themeParameters = \array_merge($defaultTable, ['#rows' => $field['items']['#rows']]);
                        $themeRendered = \Drupal::service('renderer')->renderPlain($themeParameters);
                        $form['results']['entities'][$index]['items']['#value'] = $themeRendered;
                    }
                }

                unset($form['results']['no_results']);
            }
        }
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return \Drupal\Core\Entity\EntityInterface|null
     */
    protected function loadEntity($entityType, $entityId) {
        if (is_string($entityType) && is_string($entityId)) {
            return \Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $url = Url::fromRoute('drup_entity_overview.entity_usage', [
            'entityType' => $form_state->getValue('entity_type'),
            'entityId' => $form_state->getValue('entity')
        ]);
        $form_state->setRedirectUrl($url);
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return array
     */
    public function ajaxFormCallback(array &$form, FormStateInterface $form_state) {
        return $form;
    }
}

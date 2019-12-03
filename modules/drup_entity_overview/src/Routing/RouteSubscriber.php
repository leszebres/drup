<?php

namespace Drupal\drup_entity_overview\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\drup_entity_overview\Form\EntityOverviewUsageForm;

/**
 * Class RouteSubscriber
 *
 * @package Drupal\drup_entity_overview\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

    /**
     * The entity type manager service.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new RouteSubscriber object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
     *   The entity type manager.
     */
    public function __construct(EntityTypeManagerInterface $entity_manager) {
        $this->entityTypeManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    public function alterRoutes(RouteCollection $collection) {
        foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
            if ($route = $this->getEntityLoadRoute($entity_type)) {
                $collection->add("entity.$entity_type_id.entity_overview", $route);
            }
        }
    }

    /**
     * Gets the entity load route.
     *
     * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
     *   The entity type.
     *
     * @return \Symfony\Component\Routing\Route|null
     *   The generated route, if available.
     */
    protected function getEntityLoadRoute(EntityTypeInterface $entity_type) {
        $entity_type_id = $entity_type->id();

        $route = new Route('/'.$entity_type_id.'/{'.$entity_type_id.'}/usages');
        $route
            ->addDefaults([
                '_form' => EntityOverviewUsageForm::class,
                '_title' => 'Find usage'
            ])
            ->addRequirements([
                '_permission' => 'administer drup entity overview usage',
            ])
            ->setOption('_admin_route', TRUE)
            ->setOption('parameters', [
                $entity_type_id => ['type' => 'entity:' . $entity_type_id],
            ]);

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events = parent::getSubscribedEvents();
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
        return $events;
    }

}

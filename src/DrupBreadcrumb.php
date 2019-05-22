<?php

namespace Drupal\drup;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\drup\Entity\Term;
use Drupal\drup\Helper\DrupRequest;
use Drupal\drup\Entity\DrupField;

/**
 * Class DrupBreadcrumb
 *
 * @package Drupal\drup
 */
class DrupBreadcrumb implements BreadcrumbBuilderInterface {

    /**
     * @var \Drupal\drup\DrupPageEntity
     */
    protected $drupPageEntity;

    /**
     * @var \Drupal\drup_router\DrupRouter
     */
    protected $drupRouter;

    /**
     * @var array
     */
    protected $breadcrumbItems;

    /**
     * @return array
     */
    public function buildList() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match) {
        if (!DrupRequest::isAdminRoute()) {
            $this->drupPageEntity = DrupPageEntity::loadEntity();
            $this->drupRouter = \Drupal::service('drup_router');
            $this->breadcrumbItems = [];

            $breadcrumbItems = $this->buildList();

            // current page = entité drupal
            if ($this->drupPageEntity->getEntity() !== null) {
                // Priorité à drup router si on se trouver sur une route
                if (!empty($breadcrumbItems['drup_route']) && ($routeName = $this->drupRouter->getName()) && isset($breadcrumbItems['drup_route'][$routeName])) {
                    $this->breadcrumbItems = $breadcrumbItems['drup_route'][$routeName];
                    return true;
                }
                // Sinon si on est sur une entité
                if (!empty($this->drupPageEntity->getBundle()) && !empty($this->drupPageEntity->getEntityType()) && isset($breadcrumbItems[$this->drupPageEntity->getEntityType()]) && array_key_exists($this->drupPageEntity->getBundle(), $breadcrumbItems[$this->drupPageEntity->getEntityType()])) {
                    $this->breadcrumbItems = $breadcrumbItems[$this->drupPageEntity->getEntityType()][$this->drupPageEntity->getBundle()];
                    return true;
                }
            }
            // Une route système pour finir
            elseif (isset($breadcrumbItems['system'][DrupRequest::getRouteName()])) {
                $this->breadcrumbItems = $breadcrumbItems['system'][DrupRequest::getRouteName()];
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @link http://kevinquillen.com/drupal/2017/02/16/manually-add-breadcrumb-links-in-drupal-8
     */
    public function build(RouteMatchInterface $route_match) {
        $breadcrumb = new Breadcrumb();
        $breadcrumb->addCacheContexts(['route']);
        $links = [
            Link::createFromRoute(t('Home'), '<front>')
        ];

        if (!empty($this->breadcrumbItems)) {
            foreach ($this->breadcrumbItems as $id => $type) {
                switch ($type) {
                    // DrupRouter route
                    case 'drup_route':
                        if ($entity = $this->drupRouter->getEntity($id)) {
                            $links[] = Link::fromTextAndUrl($entity->getName(), $entity->toUrl());
                        }
                        break;

                    // entity_reference
                    case 'referenced_entity':
                        $drupField = new DrupField($this->drupPageEntity->getEntity());

                        if ($entities = $drupField->getReferencedEntities($id)) {
                            $entities = array_reverse($entities);

                            if (($entity = current($entities)) && $entity !== null) {
                                $links[] = Link::fromTextAndUrl($entity->getName(), $entity->toUrl());
                            }
                        }
                        break;

                    // entity_reference taxonomy term with all parents
                    case 'referenced_taxonomy_term_parents':
                        $drupField = new DrupField($this->drupPageEntity->getEntity());

                        if ($entities = $drupField->getReferencedEntities($id)) {
                            $entities = array_reverse($entities);

                            if (($entity = current($entities)) && $entity !== null && $termParents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadAllParents($entity->id())) {
                                ksort($termParents);
                                foreach ($termParents as $term) {
                                    /** @var Term $term **/
                                    if ($term->id() !== $this->drupPageEntity->id()) {
                                        $links[] = Link::fromTextAndUrl($term->getName(), $term->toUrl());
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $breadcrumb->setLinks($links)->addCacheableDependency(0);
    }
}

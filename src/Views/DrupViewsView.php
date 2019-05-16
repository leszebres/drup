<?php

namespace Drupal\drup\Views;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/**
 * Class DrupViewsView
 *
 * @package Drupal\drup\Views
 */
class DrupViewsView extends DrupViews {

    /**
     * DrupViewsView constructor.
     *
     * @param $variables
     */
    public function __construct($variables) {
        parent::__construct($variables);

        $this->theme->controller = 'views_view_controller';
        $this->theme->path .= '/views';
    }

    /**
     * Defaults values
     */
    public function defaults() {
        $cleanViewId = Html::cleanCssIdentifier($this->view->id);
        $cleanDisplayId = Html::cleanCssIdentifier($this->view->displayId);

        // Block
        $this->variables['attributes'] = new Attribute([
            'id' => Html::getUniqueId('block--view--' . $cleanViewId . '-' .$cleanDisplayId),
            'class' => [
                'block',
                'block--view',
                'block--' . $cleanDisplayId,
                'block--' . $cleanViewId . '-' . $cleanDisplayId,
                'view',
                'view--' . $cleanViewId,
                'view-display--' . $cleanDisplayId
            ]
        ]);
        if (empty($this->variables['view']->result)) {
            $this->variables['attributes']->addClass('is-empty');
        }

        // Title
        $this->variables['title'] = null;
        $this->variables['title_tag'] = 'h2';
        $this->variables['title_attributes'] = new Attribute();
        $this->variables['title_attributes']->addClass('block-title');
    }
}

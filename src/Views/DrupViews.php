<?php

namespace Drupal\drup\Views;

use Drupal\Core\Template\Attribute;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Class DrupViews
 *
 * @package Drupal\drup
 */
class DrupViews {

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var \Drupal\Core\Theme\ActiveTheme
     */
    protected $activeTheme;

    /**
     * @var \StdClass
     */
    protected $theme;

    /**
     * @var \StdClass
     */
    protected $view;

    /**
     * DrupViews constructor.
     *
     * @param $variables
     */
    public function __construct($variables) {
        $this->variables = $variables;

        // Theme
        $this->activeTheme = \Drupal::theme()->getActiveTheme();
        $this->theme = new \StdClass;
        $this->theme->name = $this->activeTheme->getName();
        $this->theme->path = '/' . $this->activeTheme->getPath() . '/templates';

        // View
        $this->view = new \StdClass;
        $this->view->id = $this->variables['view']->id();
        $this->view->displayId = $this->variables['view']->current_display;
    }

    /**
     * Defaults values
     */
    public function defaults() {
        // Theme
        $this->variables['theme'] = $this->theme;

        // Rows container (<ul>)
        $this->variables['rows_attributes'] = new Attribute();
        $this->variables['rows_attributes']->addClass('list');
    }

    /**
     * @return bool|mixed
     */
    public function controller() {
        if (isset($this->theme->controller)) {
            // Default
            $this->defaults();
            $suggestions = [
                $this->theme->name . '_' . $this->theme->controller
            ];

            // Suggestion custom
            if (isset($this->theme->hook)) {
                $suggestions[] = $this->theme->name . '_' . $this->theme->controller . '__' . $this->theme->hook;
            }

            // ViewId and/or viewId+DisplayId
            $suggestions[] = $this->theme->name . '_' . $this->theme->controller . '__' . $this->view->id;
            $suggestions[] = $this->theme->name . '_' . $this->theme->controller . '__' . $this->view->id . '__' . $this->view->displayId;

            // Call suggestions
            foreach ($suggestions as $suggestion) {
                if (function_exists($suggestion)) {
                    $this->variables = $suggestion($this->variables);
                }
            }

            return $this->variables;
        }

        return false;
    }

    /**
     * @param $viewId
     * @param $viewDisplayId
     * @param array $arguments
     *
     * @return \Drupal\views\ViewExecutable
     */
    public static function getView($viewId, $viewDisplayId, $arguments = []) {
        $view = Views::getView($viewId);

        if ($view instanceof ViewExecutable) {
            $view->setDisplay($viewDisplayId);

            if (!empty($arguments)) {
                $arguments = [implode(',', $arguments)];
                $view->setArguments($arguments);
            }
        }

        return $view;
    }

    /**
     * @param $viewId
     * @param $viewDisplayId
     * @param array $arguments
     *
     * @return array|null
     */
    public static function buildView($viewId, $viewDisplayId, $arguments = []) {
        $view = self::getView($viewId, $viewDisplayId, $arguments);

        if ($view instanceof ViewExecutable) {
            $view->preview();

            if (!empty($view->result)) {
                return $view->result;
            }
        }

        return null;
    }

    /**
     * @param $viewId
     * @param $viewDisplayId
     * @param array $arguments
     *
     * @return string|null
     */
    public static function renderView($viewId, $viewDisplayId, $arguments = []) {
        $view = self::getView($viewId, $viewDisplayId, $arguments);

        if ($view instanceof ViewExecutable) {
            $view->execute();

            if (!empty($view->result)) {
                $rendered = $view->render();
                return \Drupal::service('renderer')->render($rendered);
            }
        }

        return null;
    }
}

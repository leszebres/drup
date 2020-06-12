<?php

namespace Drupal\drup;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\drup\Entity\DrupField;
use Drupal\drup\Entity\Node;
use Drupal\drup\Helper\DrupRequest;
use Drupal\drup\Helper\DrupString;
use Drupal\drup\Media\DrupFile;
use Drupal\drup\Media\DrupMediaImage;
use Drupal\drup_settings\DrupSettings;
use Drupal\drup_social_links\DrupSocialLinks;
use Drupal\image\Entity\ImageStyle;
use Drupal\drup\Helper\DrupUrl;

/**
 * Class DrupSEO
 *
 * @package Drupal\drup
 */
abstract class DrupSEO {

    /**
     * Nom du groupe contenant les tokens SEO
     *
     * @var string
     */
    public static $tokenType = 'seo';

    /**
     * Nom de l'image style utilisé pour les images en SEO
     *
     * @var string
     */
    public static $imageStyle = 'seo';

    /**
     * Déclaration des tokens pour le SEO
     *
     * @param $info
     */
    public static function tokensInfo(&$info) {
        // Déclaration du groupe SEO
        $info['types'][self::$tokenType] = [
            'name' => 'SEO',
            'description' => 'Données utilisées pour les métas SEO',
            'needs-data' => 'node' // Ajout l'objet Node si on est sur un node
        ];

        // Tokens associés au groupe SEO

        // Meta title/desc
        $info['tokens'][self::$tokenType]['meta:front:title'] = [
            'name' => 'Méta "title" pour la page d\'accueil'
        ];
        $info['tokens'][self::$tokenType]['meta:front:desc'] = [
            'name' => 'Méta "description" pour la page d\'accueil'
        ];
        $info['tokens'][self::$tokenType]['meta:title'] = [
            'name' => 'Meta "title" automatique'
        ];
        $info['tokens'][self::$tokenType]['meta:desc'] = [
            'name' => 'Meta "description" automatique'
        ];

        // Logo site
        $info['tokens'][self::$tokenType]['logo:url'] = [
            'name' => 'Url du logo'
        ];
        $info['tokens'][self::$tokenType]['logo:width'] = [
            'name' => 'Largeur du logo (px)'
        ];
        $info['tokens'][self::$tokenType]['logo:height'] = [
            'name' => 'Hauteur du logo (px)'
        ];
        $info['tokens'][self::$tokenType]['logo:type'] = [
            'name' => 'Type d\'image logo (image/png)'
        ];

        // Miniature node
        $info['tokens'][self::$tokenType]['thumbnail:url'] = [
            'name' => 'URL de la vignette',
            'description' => 'Avec le style d\'image "' . strtoupper(self::$imageStyle) . '"'
        ];
        $info['tokens'][self::$tokenType]['thumbnail:type'] = [
            'name' => 'Type d\'image de la vignette (image/jpg)'
        ];
        $info['tokens'][self::$tokenType]['thumbnail:width'] = [
            'name' => 'Largeur de la vignette (px)'
        ];
        $info['tokens'][self::$tokenType]['thumbnail:height'] = [
            'name' => 'Hauteur de la vignette (px)'
        ];

        // Réseaux sociaux
        $info['tokens'][self::$tokenType]['socialnetworks:link:url:comma'] = [
            'name' => 'URLs des liens vers les réseaux sociaux séparés par une virgule'
        ];

        // Coordonnées de contact
        $info['tokens'][self::$tokenType]['contact:company'] = [
            'name' => 'Société'
        ];
        $info['tokens'][self::$tokenType]['contact:phone:international'] = [
            'name' => 'N° de téléphone international'
        ];
        $info['tokens'][self::$tokenType]['contact:address'] = [
            'name' => 'Adresse'
        ];
        $info['tokens'][self::$tokenType]['contact:zipcode'] = [
            'name' => 'Code postal'
        ];
        $info['tokens'][self::$tokenType]['contact:city'] = [
            'name' => 'Ville'
        ];
        $info['tokens'][self::$tokenType]['contact:country'] = [
            'name' => 'Pays'
        ];

        // Langues
        $info['tokens'][self::$tokenType]['language:available:name:comma'] = [
            'name' => 'Liste des langues (nom) disponibles séparées par une virgule'
        ];

        // Recherche
        $info['tokens'][self::$tokenType]['search:query'] = [
            'name' => 'Url utilisée pour la recherche avec ?q=keys'
        ];
        $info['tokens'][self::$tokenType]['search:query-input'] = [
            'name' => 'Paramètres de l\'input utilisé pour la recherche'
        ];
    }

    /**
     * Contenu des tokens
     *
     * @param $replacements
     * @param $type
     * @param array $tokens
     * @param array $data
     * @param array $options
     * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
     */
    public static function tokens(&$replacements, $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
        if (DrupRequest::isAdminRoute()) {
            return;
        }

        if ($type === self::$tokenType) {
            /** @var DrupSettings $drupSettings */
            $drupSettings = \Drupal::service('drup_settings');
            $metatagManager = \Drupal::service('metatag.manager');
            $entityRepository = \Drupal::service('entity.repository');
            if (empty($options['langcode'])) {
                $options['langcode'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
            }

            $logo = false;
            if (\array_key_exists('logo:url', $tokens) || \array_key_exists('logo:width', $tokens) || \array_key_exists('logo:height', $tokens) || \array_key_exists('logo:type', $tokens)) {
                $logo = DrupFile::getLogo('png');
            }

            // Node
            $node = $drupField = false;
            if (isset($data['node']) && $data['node'] instanceof Node) {
                /** @var \Drupal\drup\Entity\Node $node */
                $node = $entityRepository->getTranslationFromContext($data['node'], $options['langcode']);
                $drupField = $node->drupField();
            }

            // Tokens
            foreach ($tokens as $name => $original) {
                // Dans un noeud
                if ($node) {
                    if ($name === 'meta:title') {
                        $tags = $metatagManager->tagsFromEntity($node);

                        if (empty($tags['title'])) {
                            $replacements[$original] = $node->getName();

                        } else {
                            $replacements[$original] = $tags['title'];
                        }

                    } elseif ($name === 'meta:desc') {
                        $tags = $metatagManager->tagsFromEntity($node);

                        if (empty($tags['description'])) {
                            if ($fieldSubtitle = $drupField->getValue('subtitle', 'value')) {
                                $description = $fieldSubtitle;

                            } elseif (($fieldDescription = $drupField->get('body_layout')) && \is_array($fieldDescription) && !empty($fieldDescription)) {
                                foreach ($fieldDescription as $paragraphItem) {
                                    if ($paragraphItem !== null) {
                                        $paragraphItem->entity = $entityRepository->getTranslationFromContext($paragraphItem->entity, $options['langcode']);

                                        if (!empty($paragraphItem->entity) && isset($paragraphItem->entity->field_body)) {
                                            $description = $paragraphItem->entity->field_body->value;
                                            break;
                                        }
                                    }
                                }

                            } elseif ($fieldBody = $drupField->getValue('body', 'value')) {
                                $description = $fieldBody;
                            }

                            if (!empty($description)) {
                                $replacements[$original] = DrupString::truncate($description);
                            }

                        } else {
                            $replacements[$original] = $tags['description'];
                        }
                    }

                } elseif ($name === 'meta:front:title' && ($title = $drupSettings->getValue('home_meta_title'))) {
                    $replacements[$original] = $title;

                } elseif ($name === 'meta:front:desc' && ($desc = $drupSettings->getValue('home_meta_desc'))) {
                    $replacements[$original] = $desc;

                } elseif ($name === 'logo:url' && $logo) {
                    $replacements[$original] = $logo->url;

                } elseif ($name === 'logo:width' && $logo) {
                    $replacements[$original] = $logo->width;

                } elseif ($name === 'logo:height' && $logo) {
                    $replacements[$original] = $logo->height;

                } elseif ($name === 'logo:type' && $logo) {
                    $replacements[$original] = $logo->mimetype;

                }

                if ($drupField !== false) {
                    if ($name === 'thumbnail:url') {
                        if ($thumbnailUrl = self::getNodeThumbnailUrl($drupField)) {
                            $replacements[$original] = $thumbnailUrl;
                        }

                    } elseif ($name === 'thumbnail:type') {
                        if ($thumbnailUrl = self::getNodeThumbnailUrl($drupField)) {
                            $extension = pathinfo($thumbnailUrl, PATHINFO_EXTENSION);
                            $extension = current(explode('?', $extension));
                            $replacements[$original] = 'image/' . $extension;
                        }

                    } elseif ($name === 'thumbnail:width' || $name === 'thumbnail:height') {
                        $imageStyle = ImageStyle::load(self::$imageStyle);

                        if ($imageStyle instanceof ImageStyle) {
                            $imageStyleEffect = current($imageStyle->getEffects()->getConfiguration());

                            $replacements[$original] = $imageStyleEffect['data'][str_replace('thumbnail:', '', $name)];
                        }
                    }
                }

                if ($name === 'socialnetworks:link:url:comma') {
                    $networks = DrupSocialLinks::getLinkItems();
                    $items = [];

                    if (!empty($networks)) {
                        foreach ($networks as $network) {
                            $items[] = $network['link_url']->toString();
                        }
                    }

                    $replacements[$original] = implode(',', $items);

                } elseif ($name === 'contact:company' && ($company = $drupSettings->getValue('contact_infos_company'))) {
                    $replacements[$original] = $company;

                } elseif ($name === 'contact:phone:international' && ($phone = $drupSettings->getValue('contact_infos_phone_number'))) {
                    if (strncmp($phone, '+', 1) !== 0) {
                        $regionCode = $drupSettings->getValue('contact_infos_country') === 'FR' ? '+33' : null;
                        $phone = $regionCode . substr($phone, 1);
                    }

                    $replacements[$original] = DrupString::cleanPhoneNumber($phone);

                } elseif ($name === 'language:available:name:comma') {
                    $languages = \Drupal::LanguageManager()->getLanguages();
                    $items = [];

                    if (!empty($languages)) {
                        foreach ($languages as $language) {
                            if (!$language->isLocked()) {
                                $items[] = $language->getName();
                            }
                        }
                    }

                    $replacements[$original] = implode(',', $items);

                } elseif ($name === 'contact:address' && ($address = $drupSettings->getValue('contact_infos_address'))) {
                    $replacements[$original] = str_replace("\r", ', ', $address);

                } elseif ($name === 'contact:zipcode' && ($zipcode = $drupSettings->getValue('contact_infos_zipcode'))) {
                    $replacements[$original] = $zipcode;

                } elseif ($name === 'contact:city' && ($city = $drupSettings->getValue('contact_infos_city'))) {
                    $replacements[$original] = $city;

                } elseif ($name === 'contact:country' && ($country = $drupSettings->getValue('contact_infos_country'))) {
                    $replacements[$original] = $country;

                } elseif ($name === 'search:query') {
                    $replacements[$original] = Url::fromUri('internal:/search', [
                        'absolute' => true
                    ])->toString() . '?q={keys}';

                } elseif ($name === 'search:query-input') {
                    $replacements[$original] = 'required name=keys';
                }
            }

        } elseif ($type === 'current-page') {
            // Tokens
            foreach ($tokens as $name => $original) {
                if ($name === 'url' && DrupRequest::isFront()) {
                    $replacements[$original] = \Drupal::request()->getSchemeAndHttpHost();
                }
            }
        }
    }

    /**
     * @param \Drupal\drup\Entity\DrupField $drupField
     *
     * @return mixed|null
     */
    protected static function getNodeThumbnailUrl(DrupField $drupField) {
        $drupMedia = null;

        if ($thumbnail = $drupField->getDrupMedia('thumbnail', 'image')) {
            $drupMedia = $thumbnail;
        } elseif ($banner = $drupField->getDrupMedia('banner', 'image')) {
            $drupMedia = $banner;
        }

        if ($drupMedia) {
            /** @var DrupMediaImage $drupMedia */
            return current($drupMedia->getMediasUrl(self::$imageStyle));
        }

        return null;
    }

    /**
     * Tokens alter
     *
     * @param $replacements
     * @param $context
     */
    public static function tokensAlter(&$replacements, &$context) {
        if ($context['type'] === self::$tokenType) {
            $metas = [
                'meta:title',
                'meta:front:title'
            ];

            foreach ($metas as $meta) {
                $metaKey = '[' . self::$tokenType . ':' . $meta . ']';

                if (isset($context['tokens'][$meta], $replacements[$metaKey])) {
                    self::addSiteTitle($replacements[$metaKey]);
                }
            }
        }
    }

    /**
     * Page attachments Alter
     *
     * @param $attachments
     */
    public static function attachmentsAlter(&$attachments) {
        if (!empty($attachments['#attached']['html_head'])) {
            foreach ($attachments['#attached']['html_head'] as $index => $attachment) {
                if (isset($attachment[1])) {
                    if ($attachment[1] === 'title') {
                        self::addSiteTitle($attachments['#attached']['html_head'][$index][0]['#attributes']['content']);

                    } elseif (\strpos($attachment[1], 'description') !== false) {
                        // Page number
                        if ($page = \Drupal::service('pager.parameters')->findPage()) {
                            $attachments['#attached']['html_head'][$index][0]['#attributes']['content'] .= ' - ' . t('Page') . ' ' . $page;
                        }

                    } elseif ($attachment[1] === 'canonical_url') {
                        $queryString = \Drupal::request()->getQueryString();

                        if ($queryString !== null) {
                            $attachments['#attached']['html_head'][$index][0]['#attributes']['href'] .= '?' . $queryString;
                        }
                    }
                }
            }
        }
    }

    /**
     * Ajoute le nom du site à la fin de la chaine fournie
     *
     * @param $string
     * @param string $separator
     */
    public static function addSiteTitle(&$string, $separator = '|') {
        if (strpos($string, $separator) === false) {
            $drupSettings = \Drupal::service('drup_settings');

            // Page number
            if ($page = \Drupal::service('pager.parameters')->findPage()) {
                $string .= ' - ' . t('Page') . ' ' . $page;
            }

            // Site title
            $string .= ' ' . $separator . ' ' . $drupSettings->getValue('site_name');
        }
    }

    /**
     * Gestionnaire de pagination
     *
     * @param $variables
     */
    public static function pagerHandler(&$variables) {
        if (isset($variables['current'])) {
            $links = [];
            $queryString = \Drupal::request()->getQueryString();
            $currentPath = Url::fromRoute('<current>')->toString();
            $currentPage = (int) $variables['current'] - 1;
            $totalPages = isset($variables['items']['last']) ? preg_replace('/^.*page=(\d+).*$/', '$1', $variables['items']['last']['href']) : count($variables['items']['pages']);

            // Prev
            if ($currentPage > 0) {
                $links['prev'] = $currentPage - 1;
            }

            // Next
            if ($currentPage < $totalPages) {
                $links['next'] = $currentPage + 1;
            }

            // Add
            if (!empty($links)) {
                foreach ($links as $link => $page) {
                    $variables['#attached']['html_head_link'][] = [
                        [
                            'rel' => $link,
                            'href' => $currentPath . DrupUrl::replaceArgument('page', $page, $queryString)
                        ],
                        true
                    ];
                }
            }
        }

        $variables['pager_views_title'] = DrupRequest::getTitle();
    }

    /**
     * Gestionnaire de pagination pour les vues
     *
     * @param $variables
     */
    public static function pagerViewsHandler(&$variables) {
        if (isset($variables['view']->pager->current_page)) {
            $links = [];
            $queryString = \Drupal::request()->getQueryString();
            $currentPath = Url::fromRoute('<current>')->toString();
            $currentPage = (int) $variables['view']->pager->current_page;
            $totalPages = (int) $variables['view']->pager->total_items;

            // Prev
            if ($currentPage > 0) {
                $links['prev'] = $currentPage - 1;
            }

            // Next
            if ($currentPage < $totalPages) {
                $links['next'] = $currentPage + 1;
            }

            // Add
            if (!empty($links)) {
                foreach ($links as $link => $page) {
                    $variables['pager']['#attached']['html_head_link'][] = [
                        [
                            'rel' => $link,
                            'href' => $currentPath . DrupUrl::replaceArgument('page', $page, $queryString)
                        ],
                        true
                    ];
                }
            }
        }
    }

    /**
     * Ajoute la vignette pour les liens du sitemap XML
     *
     * @param array  $links
     * @param string $imageField
     */
    public static function sitemapxmlAddImages(array &$links, $imageFieldname = 'thumbnail') {
        /** @var \Drupal\simple_sitemap\Simplesitemap $generator */
        $generator = \Drupal::service('simple_sitemap.generator');
        $domain = \Drupal::request()->getSchemeAndHttpHost();

        foreach ($links as $id => $link) {
            if (empty($link['images'])) {
                $url = Url::fromUri('internal:' . str_replace($domain, '', $link['url']));

                if ($url instanceof Url) {
                    // Récupération de l'entité à partir de l'url
                    $entity = DrupUrl::loadEntity($url);

                    if ($entity !== null) {
                        $bundleSettings = (array) $generator->getBundleSettings($entity->getEntityTypeId(), $entity->bundle());

                        // Si le bundle autorise l'inclusion des images
                        if ($bundleSettings['index'] && $bundleSettings['include_images']) {
                            // Ajout de l'image
                            /** @var \Drupal\drup\Media\DrupMediaImage $thumbnailMedia */
                            if (($thumbnailMedia = $entity->drupField()->getDrupMedia($imageFieldname, 'image')) && ($medias = $thumbnailMedia->getMediasData(self::$imageStyle))) {
                                $media = current($medias);

                                $links[$id]['images'][] = [
                                    'path' => $media['url'] ?? null,
                                    'alt' => $media['alt'] ?? null,
                                    'title' => $media['title'] ?? null
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

}

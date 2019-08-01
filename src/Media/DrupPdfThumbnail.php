<?php

namespace Drupal\drup\Media;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;

/**
 * Generates PDF Previews.
 */
class DrupPdfThumbnail {

    /**
     * Folder containing generated thumbnails
     *
     * @var string
     */
    protected static $directory = 'pdf-thumbnails';

    /**
     * @var array
     */
    protected static $config = [
        'extension' => 'jpg',
        'size' => '1920x1080',
        'quality' => 100
    ];

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The file system service.
     *
     * @var \Drupal\Core\File\FileSystem
     */
    protected $fileSystem;

    /**
     * The transliteration service.
     *
     * @var \Drupal\Component\Transliteration\TransliterationInterface
     */
    protected $transliteration;

    /**
     * The toolkit manager service.
     *
     * @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit
     */
    protected $toolkitManager;

    /**
     * The language manager service.
     *
     * @var \Drupal\Core\Language\LanguageManagerInterface
     */
    protected $languageManager;

    /**
     * Constructs a PDFPreviewGenerator object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The config factory.
     * @param \Drupal\Core\File\FileSystem $file_system
     *   The file system service.
     * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
     *   The transliteration service.
     * @param \Drupal\Core\ImageToolkit\ImageToolkitManager $toolkit_manager
     *   The image toolkit plugin manager service..
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     *   The language manager service.
     */
    public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system, TransliterationInterface $transliteration, ImageToolkitManager $toolkit_manager, LanguageManagerInterface $language_manager) {
        $this->configFactory = $config_factory;
        $this->fileSystem = $file_system;
        $this->transliteration = $transliteration;
        $this->toolkitManager = $toolkit_manager;
        $this->languageManager = $language_manager;
    }

    /**
     * Gets the preview image if it exists, or creates it if it doesnt.
     *
     * @param \Drupal\file\Entity\File $file
     *   The file to generate a preview for.
     *
     * @return string|null
     */
    public function getPDFPreview(File $file) {
        $destination_uri = $this->getDestinationURI($file);

        // Check if a preview already exists.
        if (file_exists($destination_uri)) {
            // Check if the existing preview is older than the file itself.
            if (filemtime($file->getFileUri()) <= filemtime($destination_uri)) {
                // The existing preview can be used, nothing to do.
                return $destination_uri;
            }

            // Delete the existing but out-of-date preview.
            $this->deletePDFPreview($file);
        }
        if ($this->createPDFPreview($file, $destination_uri)) {
            return $destination_uri;
        }

        return null;
    }

    /**
     * Deletes the preview image for a file.
     *
     * @param \Drupal\file\Entity\File $file
     *   The file to delete the preview for.
     */
    public function deletePDFPreview(File $file) {
        $uri = $this->getDestinationURI($file);
        //file_unmanaged_delete($uri);
        $this->fileSystem->delete($uri);

        image_path_flush($uri);
    }

    /**
     * Deletes the preview image for a file when the file is updated.
     *
     * @param \Drupal\file\Entity\File $file
     *   The file to delete the preview for.
     */
    public function updatePDFPreview(File $file) {
        $original = $file->original;
        if ($file->getFileUri() !== $original->getFileUri()
            || filesize($file->getFileUri()) !== filesize($original->getFileUri())) {
            $this->deletePDFPreview($original);
        }
    }

    /**
     * Creates a preview image of the first page of a PDF file.
     *
     * @param \Drupal\file\Entity\File $file
     *   The file to generate a preview for.
     * @param string $destination
     *   The URI where the preview should be created.
     *
     * @return bool
     *   TRUE if the preview was successfully generated, FALSE is otherwise.
     */
    protected function createPDFPreview(File $file, $destination) {
        $file_uri = $file->getFileUri();
        $local_path = $this->fileSystem->realpath($file_uri);
        //$config = $this->configFactory->get('pdfpreview.settings');

        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $this->toolkitManager->createInstance('imagemagick');

        $directory = $this->fileSystem->dirname($destination);
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
        $toolkit->arguments()->add('-background white');
        $toolkit->arguments()->add('-flatten');
        $toolkit->arguments()->add('-resize ' . escapeshellarg($this->getConfig('size')));
        $toolkit->arguments()->add('-quality ' . escapeshellarg($this->getConfig('quality')));

        $toolkit->arguments()->setDestinationFormat($this->getConfig('extension') === 'png' ? 'PNG' : 'JPG');
        $toolkit->arguments()->setSourceFormat('PDF');
        $toolkit->arguments()->setSourceLocalPath($local_path);
        $toolkit->arguments()->setSourceFrames('[0]');

        return $toolkit->save($destination);
    }

    /**
     * Gets the destination URI of the file.
     *
     * @param \Drupal\file\Entity\File $file
     *   The file that is being converted.
     *
     * @return string
     *   The destination URI.
     */
    protected function getDestinationURI(File $file) {
        //$config = $this->configFactory->get('pdfpreview.settings');
        $langcode = $this->languageManager->getCurrentLanguage()->getId();
        //$output_path = file_default_scheme() . '://' . $config->get('path');
        $output_path = file_default_scheme() . '://' . self::$directory;

        //if ($config->get('filenames') === 'human') {
            $filename = $this->fileSystem->basename($file->getFileUri(), '.pdf');
            $filename = $this->transliteration->transliterate($filename, $langcode);
            $filename = $file->id() . '-' . $filename;
        //}
        //else {
        //    $filename = md5('pdfpreview' . $file->id());
        //}

        //if ($config->get('type') === 'png') {
            //$extension = '.png';
        //}
        //else {
        //    $extension = '.jpg';
        //}

        $extension = '.' . $this->getConfig('extension');

        return $output_path . '/' . $filename . $extension;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    protected function getConfig($key) {
        return self::$config[$key] ?? null;
    }

}
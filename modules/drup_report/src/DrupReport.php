<?php

namespace Drupal\drup_report;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\dblog\Controller\DbLogController;
use Drupal\user\Entity\User;

/**
 * Class DrupReport
 *
 * @package Drupal\drup_report
 */
class DrupReport {

    /**
     * @var string
     */
    protected static $configName = 'drup.drup_report';

    /*
     * @return string
     */
    public static function getConfigName() {
        return self::$configName;
    }

    /**
     * @param bool $editable
     *
     * @return bool|Config|ImmutableConfig
     */
    public static function getConfig($editable = true) {
        $config = $editable ? \Drupal::service('config.factory')->getEditable(self::getConfigName()) : \Drupal::config(self::getConfigName());

        if (($editable && $config instanceof Config) || (!$editable && $config instanceof ImmutableConfig)) {
            return $config;
        }

        return false;
    }

    /**
     * @param null $userId
     *
     * @return array
     */
    public static function getConfigValuesByUser($userId = null) {
        if ($userId === null) {
            $userId = \Drupal::currentUser()->id();
        }

        $config = self::getConfig();
        return $config->get('data')[$userId] ?? [];
    }

    /**
     *
     */
    public static function sendReports() {
        $config = self::getConfig();
        $configValues = $config->get('data');

        if (!empty($configValues)) {
            foreach ($configValues as $userId => &$userConfigValues) {
                if (!is_array($userConfigValues) || empty($userConfigValues)) {
                    continue;
                }
                $currentDate = new DrupalDateTime;
                $sendDate = DrupalDateTime::createFromTimestamp($userConfigValues['date_last_send'] ?? $userConfigValues['start_date'])->modify('+1 ' . $userConfigValues['periodicity']);

                if ($sendDate->format('U') <= $currentDate->format('U')) {
                    if ($isReportSent = self::sendReport($userId, $userConfigValues, $sendDate)) {
                        $userConfigValues['date_last_send'] = $currentDate->format('U');
                    }
                }
            }
            unset($userConfigValues);

            $config->set('data', $configValues)->save();
        }
    }

    /**
     * @param array $types
     * @param \Drupal\Core\Datetime\DrupalDateTime $date
     *
     * @return array
     */
    public static function getLogs(array $types, DrupalDateTime $date) {
        $logs = [];

        // Get logs
        $query = Database::getConnection()->select('watchdog', 'w');
        $query->fields('w');

        if (!empty($types)) {
            $query->condition('w.type', $types, 'IN');
        }
        $query->condition('w.timestamp', $date->format('U'), '>=');

        $query->orderBy('w.wid', 'DESC');
        $query->range(0, 200);

        if (($result = $query->execute()) && $logs = $result->fetchAll()) {
            $dateFormatter = \Drupal::service('date.formatter');
            $dbLogController = DbLogController::create(\Drupal::getContainer());

            $classes = $dbLogController::getLogLevelClassMap();

            // Add info, formatting
            foreach ($logs as &$log) {
                $log->content = $dbLogController->formatMessage($log);
                $log->title = Unicode::truncate(Html::decodeEntities(strip_tags($log->content)), 200, TRUE, TRUE);
                $log->date = $dateFormatter->format($log->timestamp, 'custom', 'd/m/Y H:i');
                $log->url = Url::fromRoute('dblog.event', [
                    'event_id' => $log->wid,
                ]);
                $log->severity_label = $classes[$log->severity];
            }
        }

        return $logs;
    }

    /**
     * @param int $userId
     * @param array $userConfig
     * @param \Drupal\Core\Datetime\DrupalDateTime $date
     *
     * @return bool
     */
    public static function sendReport(int $userId, array $userConfig, DrupalDateTime $date) {
        if (($user = User::load($userId)) && $logs = self::getLogs($userConfig['types'], $date)) {
            $langcode = $user->getPreferredLangcode();
            $to = !empty($userConfig['email']) ?$userConfig['email'] : $user->getEmail();

            $params = [
                'headers' => []
            ];
            $renderParams = [
                '#theme' => 'drup_report__email',
                '#data' => $logs
            ];
            $params['message'] = \Drupal::service('renderer')->renderPlain($renderParams);

            if ($result = \Drupal::service('plugin.manager.mail')->mail('drup_report', 'drup_report_email', $to, $langcode, $params, null, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save in module config a list of uniquely defined database log message types.
     */
    public static function saveLogTypes() {
        $currentTypes = [];
        foreach (_dblog_get_message_types() as $type) {
            $currentTypes[$type] = t($type);
        }

        $existingTypes = self::getConfig()->get('types') ?? [];
        self::getConfig()->set('types', array_merge($currentTypes, $existingTypes))->save();
    }
}

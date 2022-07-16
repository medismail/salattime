<?php
namespace OCA\SalatTime\Service;

use \OCP\IConfig;
use OCA\SalatTime\AppInfo\Application;

class ConfigService {

    private $config;

    public function __construct(IConfig $config){
        $this->config = $config;
    }

    public function getUserValue($userId, $key) {
        return $this->config->getUserValue($userId, Application::APP_ID, $key);
    }

    public function setUserValue($userId, $key, $value) {
        $this->config->setUserValue($userId, Application::APP_ID, $key, $value);
    }

    public function getSettingsValue($userId) {
            $p_settings = explode(":", $this->config->getUserValue($userId, Application::APP_ID, 'settings'));
            if (count($p_settings) > 2) {
                    $ret['latitude'] = $p_settings['0'];
                    if ($ret['latitude'] == "")
                        $ret['latitude'] = 21.3890824;
                    $ret['longitude'] = $p_settings['1'];
                    if ($ret['longitude'] == "")
                        $ret['longitude'] = 39.8579118;
                    $ret['timezone'] = $p_settings['2'];
                    if ($ret['timezone'] == "")
                        $ret['timezone'] = '+0300';
                    if (isset($p_settings['3']) && ($p_settings['3'] != "")) {
                        $ret['elevation'] = $p_settings['3'];
                    } else {
                        $ret['elevation'] = null;
                    }
                    if (isset($p_settings['4']) && ($p_settings['4'] != "")) {
                        $ret['method'] = $p_settings['4'];
                    } else {
                        $ret['method'] = 'MWL';
                    }
                    if (isset($p_settings['5']) && ($p_settings['5'] != "")) {
                        $ret['format_12_24'] = $p_settings['5'];
                    } else {
                        $ret['format_12_24']  = '12h';
                    }
            } else {
                    $ret['latitude'] = 21.3890824;
                    $ret['longitude'] = 39.8579118;
                    $ret['timezone'] = '+0300';
                    $ret['elevation'] = null;
                    $ret['method'] = 'MWL';
                    $ret['format_12_24']  = '12h';
            }
            return $ret;
    }

    public function getAdjustmentsValue($userId) {
            $adjustments = explode(",", $this->config->getUserValue($userId, Application::APP_ID, 'adjustments'));
            if (count($adjustments) == 6) {
                    $ret['day'] = $adjustments['0'];
                    $ret['Fajr'] = $adjustments['1'];
                    $ret['Dhuhr'] = $adjustments['2'];
                    $ret['Asr'] = $adjustments['3'];
                    $ret['Maghrib'] = $adjustments['4'];
                    $ret['Isha'] = $adjustments['5'];
            } else {
                    $ret['day'] = 0;
                    $ret['Fajr'] = 0;
                    $ret['Dhuhr'] = 0;
                    $ret['Asr'] = 0;
                    $ret['Maghrib'] = 0;
                    $ret['Isha'] = 0;
            }
            return $ret;
    }
}

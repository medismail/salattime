<?php

namespace OCA\SalatTime\Tools;

use Exception;
use OCP\IUser;
use OC\Files\Filesystem;
use OC_Util;
use Psr\Log\LoggerInterface;

class Helper
{

    public static function debug($msg)
    {
        if (is_array($msg)) {
            $msg = implode(",", $msg);
        }
        $logger = \OC::$server->getLogger();
        $logger->error($msg, ['app' => 'salattime']);
    }

    public static function log($msg, $file = "/tmp/nc.log")
    {
        file_put_contents($file, print_r($msg, true), FILE_APPEND);
    }

    public static function is_function_enabled($function_name)
    {
        if (!function_exists($function_name)) {
            return false;
        }
        $ini = \OC::$server->getIniWrapper();
        $disabled = explode(',', $ini->get('disable_functions') ?: '');
        $disabled = array_map('trim', $disabled);
        if (in_array($function_name, $disabled)) {
            return false;
        }
        $disabled = explode(',', $ini->get('suhosin.executor.func.blacklist') ?: '');
        $disabled = array_map('trim', $disabled);
        if (in_array($function_name, $disabled)) {
            return false;
        }
        return true;
    }

    public static function findBinaryPath($program, $default = null)
    {
        $memcache = \OC::$server->getMemCacheFactory()->createDistributed('findBinaryPath');
        if ($memcache->hasKey($program)) {
            return $memcache->get($program);
        }

        $dataPath = \OC::$server->getSystemConfig()->getValue('datadirectory');
        $paths = ['/usr/local/sbin', '/usr/local/bin', '/usr/sbin', '/usr/bin', '/sbin', '/bin', '/opt/bin', $dataPath . "/bin"];
        $result = $default;
        $exeSniffer = new ExecutableFinder();
        // Returns null if nothing is found
        $result = $exeSniffer->find($program, $default, $paths);
        if ($result) {
            // store the value for 5 minutes
            $memcache->set($program, $result, 300);
        }
        return $result;
    }

    public static function pythonInstalled(): bool
    {
        return (self::findBinaryPath('python') || self::findBinaryPath('python3'));
    }

    public static function getAppPath(): string
    {
        return \OC::$server->getAppManager()->getAppPath('salattime');
    }

    public static function getVersion(): array
    {
        return \OC_Util::getVersion();
    }
}

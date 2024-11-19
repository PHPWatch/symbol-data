<?php

namespace PHPWatch\SymbolData\Sources;

use PHPWatch\SymbolData\DataSource;
use PHPWatch\SymbolData\DataSourceBase;
use PHPWatch\SymbolData\Output;

class ConstantsSource extends DataSourceBase implements DataSource {
    const NAME = 'const';

    /**
     * @var array
     */
    private $data;

    private static $lastError = null;

    public static $dynamicConstants = array(
        'PHP_BUILD_DATE' => true,
    );

    public function __construct(array $data) {
        $this->data = $data;
    }

    protected static function clearDeprecationLastError() {
        self::$lastError = null;
    }

    public static function callErrorHandler($noop, $message) {
        self::$lastError = $message;
    }

    public static function getLastErrorMessage() {
        return self::$lastError;
    }

    private static function isConstantDeprecated($name) {
        set_error_handler(array('\PHPWatch\SymbolData\Sources\ConstantsSource', 'callErrorHandler'), E_ALL);
        self::clearDeprecationLastError();
        @constant($name);
        restore_error_handler();

        $message = self::getLastErrorMessage();

        if (empty($message)) {
            return false;
        }

        return stripos($message, 'deprecate')
            ? $message
            : false;
    }

    public function addDataToOutput(Output $output) {
        static::handleGroupedConstantList($this->data, $output);
    }

    private static function handleGroupedConstantList(array $groupedConstList, Output $output) {
        foreach ($groupedConstList as $groupName => &$constList) {
            static::handleConstantList($groupName, $constList, $output);
        }
        unset($constList);
        $output->addData('const', $groupedConstList);
    }

    private static function handleConstantList($groupName, array &$constList, Output $output) {
        foreach ($constList as $name => &$value) {

            if (!empty(self::$dynamicConstants[$name])) {
                $value = '__DYNAMIC__';
            }

            // Handle namespaces
            $filename = str_replace('\\', '/', $name);
            $metafile = realpath(__DIR__ . '/../../meta/constants/' . $filename . '.php');

            // maybe embed custom meta data
            if ($metafile !== false && file_exists($metafile)) {
                $meta = require $metafile;
            } else {
                // embed generic meta data

                $deprecated = self::isConstantDeprecated($name);
                $meta = array(
                    'type' => 'constant',
                    'name' => $name,
                    'description' => '',
                    'keywords' => array(),
                    'added' => '0.0',
                    'deprecated' => $deprecated !== false,
                    'deprecated_message' => $deprecated !== false ? $deprecated : null,
                    'removed' => null,
                    'resources' => static::generateResources($groupName, $name),
                );
            }

            $output->addData('constants/' . $filename, array(
                'type' => 'constant',
                'name' => $name,
                'meta' => $meta,
                'value' => $value,
                'extension' => $groupName,
            ));
        }
    }

    private static function generateResources($groupName, $name) {
        $urls = array(
            'Core' => 'https://www.php.net/manual/reserved.constants.php',
            'curl' => 'https://www.php.net/manual/curl.constants.php',
            'date' => 'https://www.php.net/manual/class.datetimeinterface.php',
        );

        if (!array_key_exists($groupName, $urls)) {
            return array();
        }

        $url = $urls[$groupName];
        $anchorName = 'constant.' . $name;

        if ($groupName === 'date' && strpos($name, 'DATE_') === 0) {
            $anchorName = 'datetimeinterface.constants.' . substr($name, 5);
        }

        if ($groupName === 'date' && strpos($name, 'SUNFUNCS_') === 0) {
            $url = 'https://www.php.net/manual/function.date-sunrise.php';
            $anchorName = 'refsect1-function.date-sunrise-parameters';
        }

        $anchorName = str_replace('_', '-', strtolower($anchorName));

        return array(
            array(
                'name' => $name . ' constant (php.net)',
                'url' => $url . '#' . $anchorName,
            ),
        );
    }
}

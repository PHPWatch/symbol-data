<?php

namespace PHPWatch\SymbolData;

use ReflectionFunction;

class FunctionsListSource extends DataSourceBase implements DataSource {
    const NAME = 'function';

    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function addDataToOutput(Output $output): void
    {
        static::handleFunctionList($this->data, $output);
    }

    private static function handleFunctionList(array $functionList, Output $output)
    {
        $output->addData('function', $functionList);

        foreach ($functionList as $name) {
            $reflection = new ReflectionFunction($name);

            // Handle namespaces
            $filename = str_replace('\\', '/', $name);
            $metafile = realpath(__DIR__ . '/../meta/functions/' . $filename . '.php');

            // maybe embed custom meta data
            if ($metafile !== false && file_exists($metafile)) {
                $meta = include($metafile);
            } else {
                // embed generic meta data
                $meta = [
                    'type' => 'function',
                    'name' => $reflection->getName(),
                    'description' => '',
                    'keywords' => [],
                    'added' => '0.0',
                    'deprecated' => null,
                    'removed' => null,
                    'resources' => static::generateResources($name),
                ];
            }

            $output->addData('functions/' . $filename, [
                'type' => 'function',
                'name' => $reflection->getName(),
                'meta' => $meta,
                'parameters' => [], // #todo
                'return' => [], // #todo
                'extension' => $reflection->getExtensionName(),
            ]);
        }
    }

    private static function generateResources(string $name): array
    {
        return [
            [
                'name' => $name . ' function (php.net)',
                'url' => 'https://www.php.net/manual/function.' . str_replace('_', '-', strtolower($name)) . '.php',
            ],
        ];
    }
}

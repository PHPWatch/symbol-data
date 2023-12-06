<?php

namespace PHPWatch\SymbolData;

$PHPWatchSymbols = [
    'ext' => get_loaded_extensions(),
    'const' => get_defined_constants(true),
    'class' => get_declared_classes(),
    'trait' => get_declared_traits(),
    'interface' => get_declared_interfaces(),
    'function' => get_defined_functions()['internal'],
    'ini' => ini_get_all(),
    'attribute' => (function(): array {
        $data = [];

        if (!class_exists(\Attribute::class)) {
            return $data;
        }

        foreach (get_declared_classes() as $name) {
            $reflection = new \ReflectionClass($name);

            if ($reflection->getAttributes(\Attribute::class) !== []) {
                $data[] = $reflection->getName();
            }
        }

        return $data;
    })(),
    'phpinfo' => (function(): string {
        ob_start();
        // Do not include env of build info as they change in every build and run
        phpinfo(INFO_CREDITS|INFO_LICENSE|INFO_MODULES|INFO_CONFIGURATION);
        return ob_get_clean();
    })(),
];

require __DIR__ . '/../vendor/autoload.php';

$dumper = new Dumper(new Output());

$dumper->addSource(new ExtensionListSource($PHPWatchSymbols['ext']));
$dumper->addSource(new ConstantsSource($PHPWatchSymbols['const']));
$dumper->addSource(new ClassesListSource($PHPWatchSymbols['class']));
$dumper->addSource(new TraitsListSource($PHPWatchSymbols['trait']));
$dumper->addSource(new InterfacesListSource($PHPWatchSymbols['interface']));
$dumper->addSource(new FunctionsListSource($PHPWatchSymbols['function']));
$dumper->addSource(new INIListSource($PHPWatchSymbols['ini']));
$dumper->addSource(new AttributesListSource($PHPWatchSymbols['attribute']));
$dumper->addSource(new PHPInfoSource($PHPWatchSymbols['phpinfo']));

$dumper->dump();

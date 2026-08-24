<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use CommissionApp\Command\ProcessCsvCommand;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;

$filePath = $argv[1] ?? null;

if ($filePath === null || trim($filePath) === '') {
    fwrite(STDERR, "Usage: php script.php <input.csv>\n");
    exit(64);
}

try {
    $converter = new CurrencyConverter([
        'EUR' => 1,
        'USD' => 1.1497,
        'JPY' => 129.53,
    ]);

    (new ProcessCsvCommand(new CommissionCalculator($converter)))->execute($filePath);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

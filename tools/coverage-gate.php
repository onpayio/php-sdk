#!/usr/bin/env php
<?php
/**
 * Fails (exit 1) unless the Cobertura report covers every executable line and branch.
 *
 * Reads the integer counters from the <coverage> root instead of the rates, so a single
 * missed branch cannot hide behind rounding. Branch counters are only populated when the
 * report was generated with --path-coverage under Xdebug.
 *
 * Usage: php tools/coverage-gate.php cobertura-path.xml
 */

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, 'Usage: php tools/coverage-gate.php <cobertura.xml>' . PHP_EOL);
    exit(2);
}

$file = $argv[1];
if (!is_file($file)) {
    fwrite(STDERR, sprintf('Coverage report not found: %s%s', $file, PHP_EOL));
    exit(2);
}

$report = simplexml_load_file($file);
if ($report === false) {
    fwrite(STDERR, sprintf('Coverage report is not valid XML: %s%s', $file, PHP_EOL));
    exit(2);
}

$exitCode = 0;
foreach (['lines', 'branches'] as $metric) {
    $covered = (int) $report[$metric . '-covered'];
    $valid = (int) $report[$metric . '-valid'];

    if ($valid === 0) {
        fwrite(STDERR, sprintf('No executable %s in report; was it generated with --path-coverage?%s', $metric, PHP_EOL));
        exit(2);
    }

    printf('%s: %d/%d (%.2f%%)', ucfirst($metric), $covered, $valid, $covered / $valid * 100);
    if ($covered !== $valid) {
        echo ' - below the required 100%';
        $exitCode = 1;
    }
    echo PHP_EOL;
}

exit($exitCode);

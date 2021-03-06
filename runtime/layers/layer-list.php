<?php declare(strict_types=1);

/**
 * This script updates `layers.json` at the root of the project.
 *
 * `layers.json` contains the layer versions that Bref should use.
 */

use AsyncAws\Lambda\LambdaClient;
use AsyncAws\Lambda\ValueObject\LayerVersionsListItem;

require_once __DIR__ . '/../../vendor/autoload.php';

const LAYER_NAMES = [
    'php-73',
    'php-73-fpm',
    'console',
];

$regions = json_decode(file_get_contents(__DIR__ . '/regions.json'), true);

$export = [];
foreach ($regions as $region) {
    $layers = listLayers($region);
    foreach (LAYER_NAMES as $layerName) {
        $export[$layerName][$region] = $layers[$layerName];
    }
    echo "$region\n";
}
file_put_contents(__DIR__ . '/../../layers.json', json_encode($export, JSON_PRETTY_PRINT));
echo "Done\n";


function listLayers(string $selectedRegion): array
{

    $lambda = new LambdaClient([
        'region' => $selectedRegion,
    ]);

    // Run the API calls in parallel (thanks to async)
    $results = [];
    foreach (LAYER_NAMES as $layerName) {
        $results[$layerName] = $lambda->listLayerVersions([
            'LayerName' => sprintf('arn:aws:lambda:%s:209497400698:layer:%s', $selectedRegion, $layerName),
            'MaxItems' => 1,
        ]);
    }

    $layers = [];
    foreach ($results as $layerName => $result) {
        $versions = $result->getLayerVersions(true);
        $versionsArray = iterator_to_array($versions);
        if (! empty($versionsArray)) {
            /** @var LayerVersionsListItem $latestVersion */
            $latestVersion = end($versionsArray);
            $layers[$layerName] = $latestVersion->getVersion();
        }
    }

    return $layers;
}

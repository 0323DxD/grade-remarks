<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use Phpml\Classification\KNearestNeighbors;
use Phpml\ModelManager;

// Fetch training data
$rows = $pdo->query('SELECT student_behavior, attendance_pct, assignment_avg, quiz_score, exam_score, remark FROM training_data')->fetchAll();

if (!$rows || count($rows) < 3) {
    echo "Need at least 3 training rows in training_data table. Add more sample rows and retry.\n";
    exit(1);
}

// Build samples and labels
$samples = [];
$labels = [];

foreach ($rows as $r) {
    $samples[] = [
        (float)$r['student_behavior'],
        (float)$r['attendance_pct'],
        (float)$r['assignment_avg'],
        (float)$r['quiz_score'],
        (float)$r['exam_score'],
    ];
    $labels[] = $r['remark'];
}

// Compute mean and std for each column for scaling
$featuresCount = count($samples[0]); // 4
$means = array_fill(0, $featuresCount, 0.0);
$stds = array_fill(0, $featuresCount, 0.0);
$n = count($samples);

// compute means
for ($j = 0; $j < $featuresCount; $j++) {
    $sum = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $sum += $samples[$i][$j];
    }
    $means[$j] = $sum / $n;
}

// compute std deviations
for ($j = 0; $j < $featuresCount; $j++) {
    $sumSq = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $diff = $samples[$i][$j] - $means[$j];
        $sumSq += $diff * $diff;
    }
    $stds[$j] = sqrt($sumSq / max(1, $n - 1)); // sample std dev
    if ($stds[$j] == 0.0) {
        $stds[$j] = 1.0; // avoid division by zero
    }
}

// normalize samples: (x - mean)/std
$normSamples = [];
for ($i = 0; $i < $n; $i++) {
    $row = [];
    for ($j = 0; $j < $featuresCount; $j++) {
        $row[] = ($samples[$i][$j] - $means[$j]) / $stds[$j];
    }
    $normSamples[] = $row;
}

// Train KNN
$k = 3;
$classifier = new KNearestNeighbors($k);
$classifier->train($normSamples, $labels);

// Persist model and scaler
$modelManager = new ModelManager();

if (!is_dir(__DIR__ . '/model')) {
    mkdir(__DIR__ . '/model', 0777, true);
}

$modelFile = __DIR__ . '/model/grade-model.phpml';
$scalerFile = __DIR__ . '/model/scaler.json';

$modelManager->saveToFile($classifier, $modelFile);

$scaler = [
    'means' => $means,
    'stds'  => $stds,
    'features' => $featuresCount
];
file_put_contents($scalerFile, json_encode($scaler, JSON_PRETTY_PRINT));

echo "Training completed.\n";
echo "Samples trained: " . $n . "\n";
echo "Model saved to: $modelFile\n";
echo "Scaler saved to: $scalerFile\n";

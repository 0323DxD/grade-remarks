<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use Phpml\ModelManager;

$modelManager = new ModelManager();
$modelFile = __DIR__ . '/model/grade-model.phpml';
$scalerFile = __DIR__ . '/model/scaler.json';

$predictedRemark = '';
$error = '';
$inputValues = [
    'numeric_grade' => '',
    'attendance_pct' => '',
    'assignment_avg' => '',
    'exam_score' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare('DELETE FROM predictions WHERE id = :id');
        $stmt->execute([':id' => $deleteId]);
        // Redirect to avoid resubmission
        header("Location: predict.php");
        exit;
    }

    if (isset($_POST['numeric_grade'])) {
        $inputValues['numeric_grade'] = isset($_POST['numeric_grade']) ? floatval($_POST['numeric_grade']) : null;
        $inputValues['attendance_pct'] = isset($_POST['attendance_pct']) ? floatval($_POST['attendance_pct']) : null;
        $inputValues['assignment_avg'] = isset($_POST['assignment_avg']) ? floatval($_POST['assignment_avg']) : null;
        $inputValues['exam_score'] = isset($_POST['exam_score']) ? floatval($_POST['exam_score']) : null;

        foreach ($inputValues as $k => $v) {
            if ($v === null || $v === '') {
                $error = 'Enter valid numeric values for all fields.';
                break;
            }
        }

        if (empty($error)) {
            if (!file_exists($modelFile) || !file_exists($scalerFile)) {
                $error = 'Model or scaler file not found. Run train.php first.';
            } else {
                $estimator = $modelManager->restoreFromFile($modelFile);
                $scaler = json_decode(file_get_contents($scalerFile), true);
                if (!$scaler || !isset($scaler['means']) || !isset($scaler['stds'])) {
                    $error = 'Scaler file invalid.';
                } else {
                    $means = $scaler['means'];
                    $stds  = $scaler['stds'];

                    // Build sample in same order used for training
                    $sample = [
                        floatval($inputValues['numeric_grade']),
                        floatval($inputValues['attendance_pct']),
                        floatval($inputValues['assignment_avg']),
                        floatval($inputValues['exam_score'])
                    ];

                    // Normalize: (x - mean) / std
                    $normSample = [];
                    for ($j = 0; $j < count($sample); $j++) {
                        $normSample[] = ($sample[$j] - $means[$j]) / $stds[$j];
                    }

                    // Predict
                    $prediction = $estimator->predict([$normSample]);
                    $predictedRemark = is_array($prediction) ? $prediction[0] : $prediction;

                    // Calculate Total Grade (Simple Average of Academic Scores)
                    // Formula: (Numeric Grade + Assignment + Exam) / 3
                    $totalGrade = ($inputValues['numeric_grade'] + $inputValues['assignment_avg'] + $inputValues['exam_score']) / 3;

                    // Save to DB
                    $stmt = $pdo->prepare('INSERT INTO predictions (numeric_grade, attendance_pct, assignment_avg, exam_score, predicted_remark) VALUES (:g, :a, :asgn, :e, :r)');
                    $stmt->execute([
                        ':g' => $sample[0],
                        ':a' => $sample[1],
                        ':asgn' => $sample[2],
                        ':e' => $sample[3],
                        ':r' => $predictedRemark,
                    ]);

                    // Store result in session and redirect to prevent resubmission
                    $_SESSION['predicted_remark'] = $predictedRemark;
                    $_SESSION['total_grade'] = $totalGrade;
                    header("Location: predict.php");
                    exit;
                }
            }
        }
    }
}

// Check for flash message from session
if (isset($_SESSION['predicted_remark'])) {
    $predictedRemark = $_SESSION['predicted_remark'];
    $totalGrade = isset($_SESSION['total_grade']) ? $_SESSION['total_grade'] : null;
    unset($_SESSION['predicted_remark']);
    unset($_SESSION['total_grade']);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Remarks Generator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header>
        <h1>Grade Remarks Generator</h1>
        <p>AI-Powered Student Performance Analysis</p>
    </header>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Enter Student Details</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($predictedRemark): ?>
            <div class="result-box">
                <div class="result-label">Predicted Remark</div>
                <div class="result-value"><?= htmlspecialchars($predictedRemark) ?></div>
                <?php if (isset($totalGrade)): ?>
                    <div style="margin-top: 10px; font-size: 1.2rem; color: var(--text-muted);">
                        Total Grade: <strong><?= number_format($totalGrade, 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="predict.php">
            <div class="form-group">
                <label for="numeric_grade">Numeric Grade (0-100)</label>
                <input type="number" id="numeric_grade" name="numeric_grade" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['numeric_grade']) ?>" placeholder="e.g. 85.5" required>
            </div>

            <div class="form-group">
                <label for="attendance_pct">Attendance Percentage (0-100)</label>
                <input type="number" id="attendance_pct" name="attendance_pct" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['attendance_pct']) ?>" placeholder="e.g. 95" required>
            </div>

            <div class="form-group">
                <label for="assignment_avg">Assignment Average (0-100)</label>
                <input type="number" id="assignment_avg" name="assignment_avg" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['assignment_avg']) ?>" placeholder="e.g. 88" required>
            </div>

            <div class="form-group">
                <label for="exam_score">Exam Score (0-100)</label>
                <input type="number" id="exam_score" name="exam_score" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['exam_score']) ?>" placeholder="e.g. 92" required>
            </div>

            <button type="submit">Generate Prediction</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Predictions</h2>
        </div>
        
        <div class="table-container">
            <?php
            $results = $pdo->query('SELECT * FROM predictions ORDER BY created_at DESC LIMIT 15')->fetchAll();
            if ($results):
            ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Grade</th>
                        <th>Attendance</th>
                        <th>Assignments</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Remark</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <?php 
                        // Calculate Total on the fly
                        $rowTotal = ($r['numeric_grade'] + $r['assignment_avg'] + $r['exam_score']) / 3; 
                    ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= $r['numeric_grade'] ?></td>
                        <td><?= $r['attendance_pct'] ?>%</td>
                        <td><?= $r['assignment_avg'] ?></td>
                        <td><?= $r['exam_score'] ?></td>
                        <td><strong><?= number_format($rowTotal, 2) ?></strong></td>
                        <td>
                            <span style="font-weight: 600; color: var(--primary);">
                                <?= htmlspecialchars($r['predicted_remark']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, H:i', strtotime($r['created_at'])) ?></td>
                        <td>
                            <form method="post" action="predict.php" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; padding:0; font-size:0.875rem; font-weight:600;">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: var(--text-muted);">No predictions recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Grade Remarks Generator. Powered by PHP-ML.
    </footer>
</div>

</body>
</html>

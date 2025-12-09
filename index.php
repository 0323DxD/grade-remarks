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
    'student_name' => '',
    'student_behavior' => '',
    'attendance_pct' => '',
    'assignment_avg' => '',
    'quiz_score' => '',
    'exam_score' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare('DELETE FROM generator WHERE id = :id');
        $stmt->execute([':id' => $deleteId]);
        // Redirect to avoid resubmission
        header("Location: index.php");
        exit;
    }

    if (isset($_POST['student_behavior'])) {
        $inputValues['student_name'] = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
        $inputValues['student_behavior'] = isset($_POST['student_behavior']) ? floatval($_POST['student_behavior']) : null;
        $inputValues['attendance_pct'] = isset($_POST['attendance_pct']) ? floatval($_POST['attendance_pct']) : null;
        $inputValues['assignment_avg'] = isset($_POST['assignment_avg']) ? floatval($_POST['assignment_avg']) : null;
        $inputValues['quiz_score'] = isset($_POST['quiz_score']) ? floatval($_POST['quiz_score']) : null;
        $inputValues['exam_score'] = isset($_POST['exam_score']) ? floatval($_POST['exam_score']) : null;

        foreach ($inputValues as $k => $v) {
            if ($k !== 'student_name' && ($v === null || $v === '')) {
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
                    // Features: Behavior, Attendance, Assignment, Quiz, Exam
                    $sample = [
                        floatval($inputValues['student_behavior']),
                        floatval($inputValues['attendance_pct']),
                        floatval($inputValues['assignment_avg']),
                        floatval($inputValues['quiz_score']),
                        floatval($inputValues['exam_score'])
                    ];

                    // Normalize: (x - mean) / std
                    $normSample = [];
                    // Check if feature count matches to avoid offset error
                    if (count($sample) !== count($means)) {
                         $error = 'Model mismatch: Feature count changed. Please RETRAIN the model.';
                    } else {
                        for ($j = 0; $j < count($sample); $j++) {
                            $normSample[] = ($sample[$j] - $means[$j]) / $stds[$j];
                        }
                    }

                    if (empty($error)) {
                        // Predict
                        $prediction = $estimator->predict([$normSample]);
                        $predictedRemark = is_array($prediction) ? $prediction[0] : $prediction;

                        // Feedback Messages
                        $feedbackMap = [
                            'Excellent' => ['Outstanding performance.', 'Shows mastery of concepts.', 'Consistently excellent work.', 'Exceptional effort and creativity.'],
                            'Very Good' => ['Strong performance with only minor errors.', 'Demonstrates clear understanding.', 'Well done, keep it up.'],
                            'Good' => ['Solid effort.', 'Meets expectations.', 'Shows steady progress.', 'Reliable and consistent work.'],
                            'Average' => ['Adequate performance.', 'Meets minimum requirements.', 'Needs improvement in some areas.', 'Satisfactory but room for growth.'],
                            'Fair' => ['Basic understanding.', 'Struggles with consistency.', 'Requires guidance and more practice.'],
                            'Poor' => ['Limited understanding.', 'Frequent errors.', 'Needs significant improvement.', 'Shows lack of effort.'],
                        ];

                        $feedbackMsg = '';
                        if (isset($feedbackMap[$predictedRemark])) {
                            $msgs = $feedbackMap[$predictedRemark];
                            $feedbackMsg = $msgs[array_rand($msgs)];
                        }

                        // Save to DB
                        $stmt = $pdo->prepare('INSERT INTO generator (student_name, student_behavior, attendance_pct, assignment_avg, quiz_score, exam_score, total_grade, predicted_remark) VALUES (:name, :b, :a, :asgn, :q, :e, :t, :r)');
                        $stmt->execute([
                            ':name' => $inputValues['student_name'],
                            ':b' => $sample[0],
                            ':a' => $sample[1],
                            ':asgn' => $sample[2],
                            ':q' => $sample[3],
                            ':e' => $sample[4],
                            ':t' => $totalGrade,
                            ':r' => $predictedRemark,
                        ]);

                        // Store result in session and redirect to prevent resubmission
                        $_SESSION['predicted_remark'] = $predictedRemark;
                        $_SESSION['total_grade'] = $totalGrade;
                        $_SESSION['feedback_msg'] = $feedbackMsg;
                        header("Location: index.php");
                        exit;
                    }
                }
            }
        }
    }
}

// Check for flash message from session
if (isset($_SESSION['predicted_remark'])) {
    $predictedRemark = $_SESSION['predicted_remark'];
    $totalGrade = isset($_SESSION['total_grade']) ? $_SESSION['total_grade'] : null;
    $feedbackMsg = isset($_SESSION['feedback_msg']) ? $_SESSION['feedback_msg'] : '';
    unset($_SESSION['predicted_remark']);
    unset($_SESSION['total_grade']);
    unset($_SESSION['feedback_msg']);
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
                <div class="result-label">Generated Remark</div>
                <div class="result-value"><?= htmlspecialchars($predictedRemark) ?></div>
                <?php if ($feedbackMsg): ?>
                    <div style="margin-top: 5px; font-size: 1rem; color: var(--primary); font-style: italic;">
                        "<?= htmlspecialchars($feedbackMsg) ?>"
                    </div>
                <?php endif; ?>
                <?php if (isset($totalGrade)): ?>
                    <div style="margin-top: 15px; font-size: 1.2rem; color: var(--text-muted);">
                        Total Grade: <strong><?= number_format($totalGrade, 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php">
            <div class="form-group">
                <label for="student_name">Student Name</label>
                <input type="text" id="student_name" name="student_name" 
                       value="<?= htmlspecialchars($inputValues['student_name']) ?>" placeholder="e.g. Pogi Aguiluz" 
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border); font-family: inherit; font-size: 1rem; background: #f8fafc;" required>
            </div>

            <div class="form-group">
                <label for="student_behavior">Student Behavior (0-100)</label>
                <input type="number" id="student_behavior" name="student_behavior" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['student_behavior']) ?>" placeholder="e.g. 85.5" required>
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
                <label for="quiz_score">Quiz Score (0-100)</label>
                <input type="number" id="quiz_score" name="quiz_score" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['quiz_score']) ?>" placeholder="e.g. 75" required>
            </div>

            <div class="form-group">
                <label for="exam_score">Exam Score (0-100)</label>
                <input type="number" id="exam_score" name="exam_score" step="0.01" min="0" max="100" 
                       value="<?= htmlspecialchars($inputValues['exam_score']) ?>" placeholder="e.g. 92" required>
            </div>

            <button type="submit">Generate Grade</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Grades</h2>
        </div>
        
        <div class="table-container">
            <?php
            // Check if column exists to avoid error if SQL not run yet
            // This is a quick fix, ideally we trust the user ran the SQL
            try {
                // Attempt to select from 'generator'
                $results = $pdo->query('SELECT * FROM generator ORDER BY created_at DESC LIMIT 15')->fetchAll();
            } catch (Exception $e) {
                // Fallback or empty if table doesn't exist yet
                $results = [];
            }

            if ($results):
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Behavior</th>
                        <th>Attendance</th>
                        <th>Assignment</th>
                        <th>Quiz</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <?php 
                        // Use stored total_grade if available, otherwise calculate it (for old records)
                        if (isset($r['total_grade']) && $r['total_grade'] !== null) {
                            $rowTotal = $r['total_grade'];
                        } else {
                            // Fallback for old data might be tricky since columns changed
                            // Use 0 if key missing
                            $quizObj = isset($r['quiz_score']) ? $r['quiz_score'] : 0;
                            $rowTotal = ($quizObj + $r['assignment_avg'] + $r['exam_score']) / 3; 
                        }
                        $name = isset($r['student_name']) ? $r['student_name'] : '-';
                        // Handle column rename for display
                        $behav = isset($r['student_behavior']) ? $r['student_behavior'] : (isset($r['numeric_grade']) ? $r['numeric_grade'] : '-');
                        $quizVal = isset($r['quiz_score']) ? $r['quiz_score'] : '-';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= $behav ?></td>
                        <td><?= $r['attendance_pct'] ?>%</td>
                        <td><?= $r['assignment_avg'] ?></td>
                        <td><?= $quizVal ?></td>
                        <td><?= $r['exam_score'] ?></td>
                        <td><strong><?= number_format($rowTotal, 2) ?></strong></td>
                        <td>
                            <span style="font-weight: 600; color: var(--primary);">
                                <?= htmlspecialchars($r['predicted_remark']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="index.php" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; padding:0; font-size:0.75rem; font-weight:600;">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: var(--text-muted);">No predictions recorded yet (or table 'generator' not found).</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Grade Remarks Generator. Powered by PHP-ML.
    </footer>
</div>

</body>
</html>

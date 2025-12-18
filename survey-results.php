<?php
require_once 'db-init.php';

$survey_id = $_GET['id'] ?? 0;

// Информация об опросе
$survey = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$survey->execute([$survey_id]);
$survey_data = $survey->fetch(PDO::FETCH_ASSOC);

if (!$survey_data) {
    die("Опрос не найден.");
}

// Вопросы опроса
$questions = $pdo->prepare("
    SELECT q.*, 
           json_agg(json_build_object('text', o.option_text, 'order', o.option_order)) as options
    FROM survey_questions q
    LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.survey_id = ?
    GROUP BY q.id
    ORDER BY q.question_order
");
$questions->execute([$survey_id]);
$questions_data = $questions->fetchAll(PDO::FETCH_ASSOC);

// Статистика по каждому вопросу
foreach ($questions_data as &$question) {
    $stats = $pdo->prepare("
        SELECT answer, COUNT(*) as count 
        FROM user_responses 
        WHERE survey_id = ? AND question_id = ? 
        GROUP BY answer 
        ORDER BY count DESC
    ");
    $stats->execute([$survey_id, $question['id']]);
    $question['stats'] = $stats->fetchAll();
    
    $total = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as total FROM user_responses WHERE survey_id = ? AND question_id = ?");
    $total->execute([$survey_id, $question['id']]);
    $question['total_responses'] = $total->fetchColumn();
}

// Общее количество участников
$total_participants = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_responses WHERE survey_id = ?");
$total_participants->execute([$survey_id]);
$total_participants_count = $total_participants->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результаты: <?php echo htmlspecialchars($survey_data['title']); ?></title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .question-stats { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .stat-item { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .stat-bar { height: 20px; background: #007bff; border-radius: 3px; margin-top: 5px; }
        .total-participants { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Результаты опроса: <?php echo htmlspecialchars($survey_data['title']); ?></h1>
        <a href="surveys-list.php">← Вернуться к списку опросов</a>
        
        <div class="total-participants">
            <h3>Всего участников: <?php echo $total_participants_count; ?></h3>
        </div>
        
        <?php if ($total_participants_count == 0): ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                Пока никто не прошел этот опрос.
            </p>
        <?php else: ?>
            <?php foreach ($questions_data as $question): ?>
                <div class="question-stats">
                    <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                    <p><strong>Ответов:</strong> <?php echo $question['total_responses']; ?></p>
                    
                    <?php if (!empty($question['stats'])): ?>
                        <?php foreach ($question['stats'] as $stat): ?>
                            <div class="stat-item">
                                <div><strong><?php echo htmlspecialchars($stat['answer']); ?></strong></div>
                                <div>
                                    <?php echo $stat['count']; ?> 
                                    (<?php echo $question['total_responses'] > 0 ? round(($stat['count'] / $question['total_responses']) * 100, 1) : 0; ?>%)
                                </div>
                                <?php if ($question['total_responses'] > 0): ?>
                                    <div class="stat-bar" style="width: <?php echo ($stat['count'] / $question['total_responses']) * 100; ?>%;"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Нет данных по ответам на этот вопрос.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="take-survey.php?id=<?php echo $survey_id; ?>">Пройти этот опрос</a>
        </div>
    </div>
</body>
</html>

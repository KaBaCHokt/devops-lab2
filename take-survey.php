<?php
require_once 'db-init.php';

$survey_id = $_GET['id'] ?? 0;

// Получаем информацию об опросе
$survey = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND is_active = TRUE");
$survey->execute([$survey_id]);
$survey_data = $survey->fetch(PDO::FETCH_ASSOC);

if (!$survey_data) {
    die("<div style='padding: 40px; text-align: center;'>
            <h2>Опрос не найден</h2>
            <p>Возможно опрос был удален или деактивирован.</p>
            <a href='surveys-list.php'>Вернуться к списку опросов</a>
         </div>");
}

// Получаем вопросы опроса с вариантами ответов
$questions = $pdo->prepare("
    SELECT q.*, 
           json_agg(json_build_object('id', o.id, 'text', o.option_text, 'order', o.option_order)) as options
    FROM survey_questions q
    LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.survey_id = ?
    GROUP BY q.id
    ORDER BY q.question_order
");
$questions->execute([$survey_id]);
$questions_data = $questions->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = uniqid('user_', true);
    
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['answers'] as $question_id => $answer) {
            if (is_array($answer)) {
                $answer = implode(', ', array_filter($answer));
            }
            
            if (!empty($answer)) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_responses (survey_id, question_id, user_id, answer) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$survey_id, $question_id, $user_id, trim($answer)]);
            }
        }
        
        $pdo->commit();
        
        header("Location: survey-thanks.php?survey_id=" . $survey_id);
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Ошибка сохранения ответов: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Опрос: <?php echo htmlspecialchars($survey_data['title']); ?></title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .question-block { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6; }
        .option-item { margin: 10px 0; padding: 10px; background: white; border-radius: 4px; }
        .option-item label { margin-left: 10px; cursor: pointer; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; }
        .submit-btn { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($survey_data['title']); ?></h1>
        <?php if (!empty($survey_data['description'])): ?>
            <p><?php echo htmlspecialchars($survey_data['description']); ?></p>
        <?php endif; ?>
        
        <p><a href="surveys-list.php">← Вернуться к списку опросов</a></p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php foreach ($questions_data as $question): 
                $options = json_decode($question['options'], true);
                $options = array_filter($options, function($opt) { return $opt['id'] !== null; });
            ?>
                <div class="question-block">
                    <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                    
                    <?php if (!empty($options)): ?>
                        <?php foreach ($options as $option): ?>
                            <div class="option-item">
                                <input type="radio" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       value="<?php echo htmlspecialchars($option['text']); ?>" 
                                       id="option_<?php echo $option['id']; ?>"
                                       required>
                                <label for="option_<?php echo $option['id']; ?>">
                                    <?php echo htmlspecialchars($option['text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <textarea name="answers[<?php echo $question['id']; ?>]" 
                                  style="width: 100%; padding: 10px;" 
                                  rows="4" 
                                  placeholder="Введите ваш ответ..." 
                                  required></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="submit-btn">Отправить ответы</button>
            </div>
        </form>
    </div>
</body>
</html>

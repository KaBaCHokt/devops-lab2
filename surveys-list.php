<?php
require_once 'db-init.php';

// Получаем все активные опросы с количеством участников
$surveys = $pdo->query("
    SELECT 
        s.id, 
        s.title, 
        s.description, 
        s.created_at,
        COUNT(DISTINCT r.user_id) as participants
    FROM surveys s 
    LEFT JOIN user_responses r ON s.id = r.survey_id 
    WHERE s.is_active = TRUE
    GROUP BY s.id, s.title, s.description, s.created_at
    ORDER BY s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список опросов</title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .survey-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .survey-title { font-size: 1.5em; margin-bottom: 10px; color: #333; }
        .survey-desc { color: #666; margin-bottom: 15px; }
        .survey-meta { color: #888; font-size: 0.9em; margin-bottom: 15px; }
        .survey-actions { display: flex; gap: 10px; }
        .action-btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 14px; }
        .take-btn { background: #007bff; color: white; }
        .results-btn { background: #6c757d; color: white; }
        .no-data { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Доступные опросы</h1>
        <a href="index.html">← На главную</a>
        
        <?php if (empty($surveys)): ?>
            <div class="no-data">
                <h3>Нет доступных опросов</h3>
                <p>Будьте первым, кто создаст опрос!</p>
                <a href="create-survey.php" style="margin-top: 15px; display: inline-block;">Создать опрос</a>
            </div>
        <?php else: ?>
            <?php foreach ($surveys as $survey): ?>
                <div class="survey-card">
                    <div class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></div>
                    <?php if (!empty($survey['description'])): ?>
                        <div class="survey-desc"><?php echo htmlspecialchars($survey['description']); ?></div>
                    <?php endif; ?>
                    <div class="survey-meta">
                        Создан: <?php echo date('d.m.Y H:i', strtotime($survey['created_at'])); ?> | 
                        Участников: <?php echo $survey['participants']; ?>
                    </div>
                    <div class="survey-actions">
                        <a href="take-survey.php?id=<?php echo $survey['id']; ?>" class="action-btn take-btn">
                            Пройти опрос
                        </a>
                        <a href="survey-results.php?id=<?php echo $survey['id']; ?>" class="action-btn results-btn">
                            Посмотреть результаты
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="create-survey.php">+ Создать новый опрос</a>
        </div>
    </div>
</body>
</html>

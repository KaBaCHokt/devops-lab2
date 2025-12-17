<?php
require_once 'db-init.php';

// Общая статистика
$total_surveys = $pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$total_responses = $pdo->query("SELECT COUNT(DISTINCT user_id, survey_id) FROM user_responses")->fetchColumn();
$popular_survey = $pdo->query("
    SELECT s.title, COUNT(DISTINCT r.user_id) as participants 
    FROM surveys s 
    LEFT JOIN user_responses r ON s.id = r.survey_id 
    GROUP BY s.id 
    ORDER BY participants DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Все опросы с количеством участников
$all_surveys = $pdo->query("
    SELECT s.id, s.title, COUNT(DISTINCT r.user_id) as participants 
    FROM surveys s 
    LEFT JOIN user_responses r ON s.id = r.survey_id 
    GROUP BY s.id 
    ORDER BY participants DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Общая статистика</title>
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        .survey-list {
            margin: 30px 0;
        }
        .survey-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Общая статистика системы</h1>
        <a href="index.html">← На главную</a>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Всего опросов</h3>
                <div class="stat-number"><?php echo $total_surveys; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Всего ответов</h3>
                <div class="stat-number"><?php echo $total_responses; ?></div>
            </div>
            
            <?php if ($popular_survey): ?>
            <div class="stat-card">
                <h3>Самый популярный</h3>
                <div><?php echo htmlspecialchars($popular_survey['title']); ?></div>
                <div class="stat-number"><?php echo $popular_survey['participants']; ?></div>
                <div>участников</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="survey-list">
            <h2>Все опросы</h2>
            <?php foreach ($all_surveys as $survey): ?>
                <div class="survey-item">
                    <a href="survey-results.php?id=<?php echo $survey['id']; ?>">
                        <?php echo htmlspecialchars($survey['title']); ?>
                    </a>
                    <span style="float: right;">
                        <?php echo $survey['participants']; ?> участников
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

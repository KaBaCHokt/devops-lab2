<?php
// Настройки подключения к PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'surveys_db';
$username = 'survey_user';
$password = 'survey123';

try {
    // Подключаемся к PostgreSQL
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Создаем таблицы если их нет
    $sql_commands = [
        // Таблица опросов
        "CREATE TABLE IF NOT EXISTS surveys (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )",
        
        // Таблица вопросов
        "CREATE TABLE IF NOT EXISTS survey_questions (
            id SERIAL PRIMARY KEY,
            survey_id INTEGER NOT NULL,
            question_text TEXT NOT NULL,
            question_type VARCHAR(50) DEFAULT 'radio',
            question_order INTEGER DEFAULT 0,
            FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
        )",
        
        // Таблица вариантов ответов
        "CREATE TABLE IF NOT EXISTS question_options (
            id SERIAL PRIMARY KEY,
            question_id INTEGER NOT NULL,
            option_text TEXT NOT NULL,
            option_order INTEGER DEFAULT 0,
            FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
        )",
        
        // Таблица ответов пользователей
        "CREATE TABLE IF NOT EXISTS user_responses (
            id SERIAL PRIMARY KEY,
            survey_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            user_id VARCHAR(100),
            answer TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Индексы для ускорения запросов
        "CREATE INDEX IF NOT EXISTS idx_survey_questions_survey_id ON survey_questions(survey_id)",
        "CREATE INDEX IF NOT EXISTS idx_question_options_question_id ON question_options(question_id)",
        "CREATE INDEX IF NOT EXISTS idx_user_responses_survey_id ON user_responses(survey_id)"
    ];
    
    foreach ($sql_commands as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Игнорируем ошибку "таблица уже существует"
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("SQL Error: " . $e->getMessage());
            }
        }
    }
    
} catch (PDOException $e) {
    die("<h3>Ошибка подключения к базе данных PostgreSQL</h3>
         <p><strong>Сообщение:</strong> " . $e->getMessage() . "</p>
         <p><strong>Проверь:</strong></p>
         <ol>
            <li>Запущен ли PostgreSQL: <code>sudo systemctl status postgresql</code></li>
            <li>Существует ли БД 'surveys_db'</li>
            <li>Правильные ли логин/пароль: survey_user / survey123</li>
            <li>Разрешены ли подключения в pg_hba.conf</li>
         </ol>");
}
?>

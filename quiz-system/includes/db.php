<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        try {
            $this->db = new PDO('sqlite:' . DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initDatabase();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    private function initDatabase() {
        // Create tables if they don't exist
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS topics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT,
                enabled INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS quizzes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                time_limit INTEGER DEFAULT 600,
                questions_per_quiz INTEGER DEFAULT 10,
                total_questions INTEGER DEFAULT 0,
                enabled INTEGER DEFAULT 1,
                qr_code_path TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                question_text TEXT NOT NULL,
                option_a TEXT NOT NULL,
                option_b TEXT NOT NULL,
                option_c TEXT NOT NULL,
                option_d TEXT NOT NULL,
                correct_answer TEXT NOT NULL,
                explanation TEXT,
                difficulty TEXT DEFAULT 'medium',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS quiz_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id INTEGER NOT NULL,
                session_code TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'waiting',
                started_at DATETIME,
                ended_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS participants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                color_name TEXT NOT NULL,
                color_hex TEXT NOT NULL,
                color_text TEXT NOT NULL,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME,
                score INTEGER DEFAULT 0,
                FOREIGN KEY (session_id) REFERENCES quiz_sessions(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS participant_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                participant_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                question_order INTEGER NOT NULL,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS answers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                participant_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                selected_answer TEXT NOT NULL,
                is_correct INTEGER NOT NULL,
                answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
            )
        ");

        // Create indexes for better performance
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_quiz_topic ON quizzes(topic_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_question_quiz ON questions(quiz_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_session_quiz ON quiz_sessions(quiz_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_participant_session ON participants(session_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_answer_participant ON answers(participant_id)");
    }

    // Topics CRUD
    public function createTopic($name, $description = '') {
        $stmt = $this->db->prepare("INSERT INTO topics (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        return $this->db->lastInsertId();
    }

    public function getTopics($enabledOnly = false) {
        $sql = "SELECT * FROM topics";
        if ($enabledOnly) {
            $sql .= " WHERE enabled = 1";
        }
        $sql .= " ORDER BY name";
        return $this->db->query($sql)->fetchAll();
    }

    public function getTopic($id) {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateTopic($id, $name, $description, $enabled) {
        $stmt = $this->db->prepare("
            UPDATE topics
            SET name = ?, description = ?, enabled = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $enabled, $id]);
    }

    public function deleteTopic($id) {
        $stmt = $this->db->prepare("DELETE FROM topics WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleTopicStatus($id) {
        $stmt = $this->db->prepare("UPDATE topics SET enabled = NOT enabled WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Quizzes CRUD
    public function createQuiz($topicId, $name, $description, $timeLimit, $questionsPerQuiz) {
        $stmt = $this->db->prepare("
            INSERT INTO quizzes (topic_id, name, description, time_limit, questions_per_quiz)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$topicId, $name, $description, $timeLimit, $questionsPerQuiz]);
        return $this->db->lastInsertId();
    }

    public function getQuizzes($topicId = null, $enabledOnly = false) {
        $sql = "SELECT q.*, t.name as topic_name FROM quizzes q
                JOIN topics t ON q.topic_id = t.id WHERE 1=1";
        $params = [];

        if ($topicId) {
            $sql .= " AND q.topic_id = ?";
            $params[] = $topicId;
        }

        if ($enabledOnly) {
            $sql .= " AND q.enabled = 1 AND t.enabled = 1";
        }

        $sql .= " ORDER BY t.name, q.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getQuiz($id) {
        $stmt = $this->db->prepare("
            SELECT q.*, t.name as topic_name
            FROM quizzes q
            JOIN topics t ON q.topic_id = t.id
            WHERE q.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateQuiz($id, $name, $description, $timeLimit, $questionsPerQuiz, $enabled) {
        $stmt = $this->db->prepare("
            UPDATE quizzes
            SET name = ?, description = ?, time_limit = ?,
                questions_per_quiz = ?, enabled = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $timeLimit, $questionsPerQuiz, $enabled, $id]);
    }

    public function deleteQuiz($id) {
        $stmt = $this->db->prepare("DELETE FROM quizzes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleQuizStatus($id) {
        $stmt = $this->db->prepare("UPDATE quizzes SET enabled = NOT enabled WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateQuizQRCode($id, $qrPath) {
        $stmt = $this->db->prepare("UPDATE quizzes SET qr_code_path = ? WHERE id = ?");
        return $stmt->execute([$qrPath, $id]);
    }

    // Questions CRUD
    public function addQuestion($quizId, $questionData) {
        $stmt = $this->db->prepare("
            INSERT INTO questions
            (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $quizId,
            $questionData['question_text'],
            $questionData['option_a'],
            $questionData['option_b'],
            $questionData['option_c'],
            $questionData['option_d'],
            $questionData['correct_answer'],
            $questionData['explanation'] ?? '',
            $questionData['difficulty'] ?? 'medium'
        ]);

        // Update total questions count
        $this->updateQuizQuestionCount($quizId);

        return $result;
    }

    public function getQuestions($quizId) {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
        $stmt->execute([$quizId]);
        return $stmt->fetchAll();
    }

    public function getRandomQuestions($quizId, $count) {
        $stmt = $this->db->prepare("
            SELECT * FROM questions WHERE quiz_id = ? ORDER BY RANDOM() LIMIT ?
        ");
        $stmt->execute([$quizId, $count]);
        return $stmt->fetchAll();
    }

    public function deleteAllQuestions($quizId) {
        $stmt = $this->db->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $result = $stmt->execute([$quizId]);
        $this->updateQuizQuestionCount($quizId);
        return $result;
    }

    private function updateQuizQuestionCount($quizId) {
        $stmt = $this->db->prepare("
            UPDATE quizzes
            SET total_questions = (SELECT COUNT(*) FROM questions WHERE quiz_id = ?)
            WHERE id = ?
        ");
        return $stmt->execute([$quizId, $quizId]);
    }

    // Quiz Sessions
    public function createSession($quizId) {
        $sessionCode = generate_session_code();
        $stmt = $this->db->prepare("
            INSERT INTO quiz_sessions (quiz_id, session_code)
            VALUES (?, ?)
        ");
        $stmt->execute([$quizId, $sessionCode]);
        return $this->db->lastInsertId();
    }

    public function getSession($sessionId) {
        $stmt = $this->db->prepare("SELECT * FROM quiz_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    }

    public function getSessionByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM quiz_sessions WHERE session_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function startSession($sessionId) {
        $stmt = $this->db->prepare("
            UPDATE quiz_sessions
            SET status = 'active', started_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$sessionId]);
    }

    public function endSession($sessionId) {
        $stmt = $this->db->prepare("
            UPDATE quiz_sessions
            SET status = 'completed', ended_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$sessionId]);
    }

    // Participants
    public function addParticipant($sessionId, $color) {
        $stmt = $this->db->prepare("
            INSERT INTO participants (session_id, color_name, color_hex, color_text)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$sessionId, $color['name'], $color['hex'], $color['text']]);
        return $this->db->lastInsertId();
    }

    public function getParticipants($sessionId) {
        $stmt = $this->db->prepare("SELECT * FROM participants WHERE session_id = ? ORDER BY joined_at");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    public function getUsedColors($sessionId) {
        $stmt = $this->db->prepare("SELECT color_name FROM participants WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        return array_column($stmt->fetchAll(), 'color_name');
    }

    public function completeParticipant($participantId, $score) {
        $stmt = $this->db->prepare("
            UPDATE participants
            SET completed_at = CURRENT_TIMESTAMP, score = ?
            WHERE id = ?
        ");
        return $stmt->execute([$score, $participantId]);
    }

    // Participant Questions Assignment
    public function assignQuestionsToParticipant($participantId, $questionIds) {
        $stmt = $this->db->prepare("
            INSERT INTO participant_questions (participant_id, question_id, question_order)
            VALUES (?, ?, ?)
        ");

        foreach ($questionIds as $order => $questionId) {
            $stmt->execute([$participantId, $questionId, $order + 1]);
        }
    }

    public function getParticipantQuestions($participantId) {
        $stmt = $this->db->prepare("
            SELECT q.*, pq.question_order
            FROM participant_questions pq
            JOIN questions q ON pq.question_id = q.id
            WHERE pq.participant_id = ?
            ORDER BY pq.question_order
        ");
        $stmt->execute([$participantId]);
        return $stmt->fetchAll();
    }

    // Answers
    public function submitAnswer($participantId, $questionId, $selectedAnswer, $isCorrect) {
        $stmt = $this->db->prepare("
            INSERT INTO answers (participant_id, question_id, selected_answer, is_correct)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$participantId, $questionId, $selectedAnswer, $isCorrect]);
    }

    public function getParticipantAnswers($participantId) {
        $stmt = $this->db->prepare("SELECT * FROM answers WHERE participant_id = ? ORDER BY answered_at");
        $stmt->execute([$participantId]);
        return $stmt->fetchAll();
    }

    // Analytics
    public function getSessionStats($sessionId) {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_participants,
                COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_participants,
                AVG(CASE WHEN completed_at IS NOT NULL THEN score END) as average_score,
                MAX(score) as highest_score,
                MIN(CASE WHEN completed_at IS NOT NULL THEN score END) as lowest_score
            FROM participants
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    }

    public function getQuizStats($quizId) {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT qs.id) as total_sessions,
                COUNT(DISTINCT p.id) as total_participants,
                AVG(p.score) as average_score
            FROM quiz_sessions qs
            LEFT JOIN participants p ON qs.id = p.session_id
            WHERE qs.quiz_id = ? AND qs.status = 'completed'
        ");
        $stmt->execute([$quizId]);
        return $stmt->fetch();
    }
}

?>

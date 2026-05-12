-- Feature extension migration for premium lessons, subscriptions, applications, and email logging

ALTER TABLE module_lessons
    ADD COLUMN IF NOT EXISTS lesson_file VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) NOT NULL DEFAULT 0,
    MODIFY COLUMN content_type ENUM('video','pdf','text','article') NOT NULL;

CREATE TABLE IF NOT EXISTS lesson_progress (
    lesson_progress_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (lesson_progress_id),
    UNIQUE KEY uq_lesson_progress_user_lesson (user_id, lesson_id),
    CONSTRAINT fk_lesson_progress_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_subscriptions (
    subscription_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    plan VARCHAR(100) NOT NULL,
    status ENUM('active','cancelled','expired') NOT NULL DEFAULT 'active',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    PRIMARY KEY (subscription_id),
    KEY idx_student_subscriptions_user (user_id),
    CONSTRAINT fk_student_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_applications (
    application_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    role ENUM('mentor','employer') NOT NULL,
    organization_name VARCHAR(255) DEFAULT NULL,
    application_note TEXT DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (application_id),
    UNIQUE KEY uq_user_applications_user (user_id),
    CONSTRAINT fk_user_applications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
    email_log_id INT(11) NOT NULL AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'sent',
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email_log_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_quizzes (
    quiz_id INT(11) NOT NULL AUTO_INCREMENT,
    lesson_id INT(11) NOT NULL,
    title VARCHAR(180) NOT NULL,
    passing_score INT(11) NOT NULL DEFAULT 70,
    PRIMARY KEY (quiz_id),
    KEY idx_lesson_quizzes_lesson (lesson_id),
    CONSTRAINT fk_lesson_quizzes_lesson FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_quiz_questions (
    question_id INT(11) NOT NULL AUTO_INCREMENT,
    quiz_id INT(11) NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
    PRIMARY KEY (question_id),
    CONSTRAINT fk_lesson_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES lesson_quizzes(quiz_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_quiz_choices (
    choice_id INT(11) NOT NULL AUTO_INCREMENT,
    question_id INT(11) NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (choice_id),
    CONSTRAINT fk_lesson_quiz_choices_question FOREIGN KEY (question_id) REFERENCES lesson_quiz_questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_quiz_attempts (
    attempt_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    quiz_id INT(11) NOT NULL,
    score INT(11) NOT NULL DEFAULT 0,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attempt_id),
    UNIQUE KEY uq_lesson_quiz_attempt_user_quiz (user_id, quiz_id),
    CONSTRAINT fk_lesson_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES lesson_quizzes(quiz_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

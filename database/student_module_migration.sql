SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS users (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','mentor','employer','admin') NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  profile_photo VARCHAR(255) DEFAULT NULL,
  profile_completed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_profiles (
  profile_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  course VARCHAR(100) DEFAULT NULL,
  year_level VARCHAR(50) DEFAULT NULL,
  skills TEXT DEFAULT NULL,
  interests VARCHAR(100) DEFAULT NULL,
  dream_job VARCHAR(100) DEFAULT NULL,
  career_path VARCHAR(100) DEFAULT NULL,
  readiness_score INT(11) DEFAULT 0,
  completed_skills INT(11) DEFAULT 0,
  missing_skills INT(11) DEFAULT 0,
  portfolio_projects INT(11) DEFAULT 0,
  target_industry VARCHAR(100) DEFAULT NULL,
  ai_summary TEXT DEFAULT NULL,
  career_ready TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (profile_id),
  UNIQUE KEY uq_student_profiles_user (user_id),
  CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_paths (
  career_id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  average_salary_ph VARCHAR(100) NOT NULL,
  industry VARCHAR(120) NOT NULL,
  icon VARCHAR(80) NOT NULL DEFAULT 'fa-route',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (career_id),
  UNIQUE KEY uq_career_paths_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_career_matches (
  match_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  career_id INT(11) NOT NULL,
  match_percentage INT(11) NOT NULL DEFAULT 0,
  current_progress INT(11) NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (match_id),
  UNIQUE KEY uq_student_career_match (user_id, career_id),
  KEY idx_student_career_matches_user (user_id),
  KEY idx_student_career_matches_career (career_id),
  CONSTRAINT fk_student_career_matches_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_career_matches_career FOREIGN KEY (career_id) REFERENCES career_paths(career_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learning_paths (
  path_id INT(11) NOT NULL AUTO_INCREMENT,
  career_id INT(11) NOT NULL,
  title VARCHAR(160) NOT NULL,
  phase_order INT(11) NOT NULL,
  description TEXT NOT NULL,
  PRIMARY KEY (path_id),
  UNIQUE KEY uq_learning_path_phase (career_id, phase_order),
  KEY idx_learning_paths_career (career_id),
  CONSTRAINT fk_learning_paths_career FOREIGN KEY (career_id) REFERENCES career_paths(career_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roadmap_tasks (
  task_id INT(11) NOT NULL AUTO_INCREMENT,
  path_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  task_type ENUM('lesson','project','quiz','mentor_review','certification') NOT NULL,
  points INT(11) NOT NULL DEFAULT 0,
  estimated_hours INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (task_id),
  UNIQUE KEY uq_roadmap_task_title (path_id, title),
  KEY idx_roadmap_tasks_path (path_id),
  CONSTRAINT fk_roadmap_tasks_path FOREIGN KEY (path_id) REFERENCES learning_paths(path_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_tasks (
  student_task_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  task_id INT(11) NOT NULL,
  status ENUM('locked','available','in_progress','submitted','completed') NOT NULL DEFAULT 'locked',
  progress_percent INT(11) NOT NULL DEFAULT 0,
  mentor_feedback TEXT DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (student_task_id),
  UNIQUE KEY uq_student_task (user_id, task_id),
  KEY idx_student_tasks_user_status (user_id, status),
  KEY idx_student_tasks_task (task_id),
  CONSTRAINT fk_student_tasks_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_tasks_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessments (
  assessment_id INT(11) NOT NULL AUTO_INCREMENT,
  task_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT DEFAULT NULL,
  passing_score INT(11) NOT NULL DEFAULT 70,
  PRIMARY KEY (assessment_id),
  UNIQUE KEY uq_assessments_task (task_id),
  CONSTRAINT fk_assessments_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_questions (
  question_id INT(11) NOT NULL AUTO_INCREMENT,
  assessment_id INT(11) NOT NULL,
  question_text TEXT NOT NULL,
  question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
  PRIMARY KEY (question_id),
  UNIQUE KEY uq_assessment_question (assessment_id, question_text(191)),
  KEY idx_assessment_questions_assessment (assessment_id),
  CONSTRAINT fk_assessment_questions_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_choices (
  choice_id INT(11) NOT NULL AUTO_INCREMENT,
  question_id INT(11) NOT NULL,
  choice_text TEXT NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (choice_id),
  UNIQUE KEY uq_assessment_choice (question_id, choice_text(191)),
  KEY idx_assessment_choices_question (question_id),
  CONSTRAINT fk_assessment_choices_question FOREIGN KEY (question_id) REFERENCES assessment_questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_attempts (
  attempt_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  assessment_id INT(11) NOT NULL,
  score INT(11) NOT NULL DEFAULT 0,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attempt_id),
  KEY idx_assessment_attempts_user (user_id),
  KEY idx_assessment_attempts_assessment (assessment_id),
  CONSTRAINT fk_assessment_attempts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_assessment_attempts_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_projects (
  project_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  github_link VARCHAR(255) DEFAULT NULL,
  live_demo_link VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  mentor_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id),
  KEY idx_portfolio_projects_user (user_id),
  CONSTRAINT fk_portfolio_projects_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_assignments (
  assignment_id INT(11) NOT NULL AUTO_INCREMENT,
  student_id INT(11) NOT NULL,
  mentor_id INT(11) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'active',
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (assignment_id),
  UNIQUE KEY uq_mentor_assignment_active (student_id, mentor_id),
  KEY idx_mentor_assignments_student (student_id),
  KEY idx_mentor_assignments_mentor (mentor_id),
  CONSTRAINT fk_mentor_assignments_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_assignments_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_feedback (
  feedback_id INT(11) NOT NULL AUTO_INCREMENT,
  assignment_id INT(11) NOT NULL,
  task_id INT(11) DEFAULT NULL,
  comments TEXT NOT NULL,
  rating INT(11) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (feedback_id),
  KEY idx_mentor_feedback_assignment (assignment_id),
  KEY idx_mentor_feedback_task (task_id),
  CONSTRAINT fk_mentor_feedback_assignment FOREIGN KEY (assignment_id) REFERENCES mentor_assignments(assignment_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_feedback_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(task_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_messages (
  message_id INT(11) NOT NULL AUTO_INCREMENT,
  assignment_id INT(11) NOT NULL,
  sender_id INT(11) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  KEY idx_mentor_messages_assignment (assignment_id),
  KEY idx_mentor_messages_sender (sender_id),
  CONSTRAINT fk_mentor_messages_assignment FOREIGN KEY (assignment_id) REFERENCES mentor_assignments(assignment_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_messages_sender FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE student_profiles ADD COLUMN career_ready TINYINT(1) DEFAULT 0',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'student_profiles'
  AND COLUMN_NAME = 'career_ready'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE student_profiles ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'student_profiles'
  AND COLUMN_NAME = 'updated_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 1,
    'ALTER TABLE users MODIFY confirm_password VARCHAR(50) NULL',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'confirm_password'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

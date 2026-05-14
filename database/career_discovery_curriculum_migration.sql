SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS users (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  confirm_password VARCHAR(50) DEFAULT NULL,
  role ENUM('student','mentor','employer','admin') NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  profile_photo VARCHAR(255) DEFAULT NULL,
  profile_completed TINYINT(1) DEFAULT 0,
  career_path VARCHAR(120) DEFAULT NULL,
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
  student_type VARCHAR(80) DEFAULT NULL,
  favorite_subjects JSON DEFAULT NULL,
  activity_preferences JSON DEFAULT NULL,
  work_style VARCHAR(80) DEFAULT NULL,
  career_path_id INT(11) DEFAULT NULL,
  career_match_percentage INT(11) DEFAULT 0,
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
  CONSTRAINT fk_student_profiles_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_paths (
  path_id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  icon VARCHAR(80) NOT NULL DEFAULT 'fa-route',
  category VARCHAR(100) DEFAULT NULL,
  average_salary_ph VARCHAR(100) DEFAULT NULL,
  industry VARCHAR(120) DEFAULT 'Philippine IT Industry',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (path_id),
  UNIQUE KEY uq_career_paths_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_years (
  year_id INT(11) NOT NULL AUTO_INCREMENT,
  path_id INT(11) NOT NULL,
  year_number TINYINT(1) NOT NULL,
  PRIMARY KEY (year_id),
  UNIQUE KEY uq_career_year (path_id, year_number),
  CONSTRAINT fk_career_years_path FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_semesters (
  semester_id INT(11) NOT NULL AUTO_INCREMENT,
  year_id INT(11) NOT NULL,
  semester_number TINYINT(1) NOT NULL,
  PRIMARY KEY (semester_id),
  UNIQUE KEY uq_career_semester (year_id, semester_number),
  CONSTRAINT fk_career_semesters_year FOREIGN KEY (year_id) REFERENCES career_years(year_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_subjects (
  subject_id INT(11) NOT NULL AUTO_INCREMENT,
  semester_id INT(11) NOT NULL,
  subject_code VARCHAR(40) NOT NULL,
  subject_title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  subject_order INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (subject_id),
  UNIQUE KEY uq_career_subject_code (semester_id, subject_code),
  CONSTRAINT fk_career_subjects_semester FOREIGN KEY (semester_id) REFERENCES career_semesters(semester_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_subjects (
  enrollment_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  subject_id INT(11) NOT NULL,
  learning_mode ENUM('free','premium') NOT NULL DEFAULT 'free',
  status ENUM('locked','available','in_progress','completed') NOT NULL DEFAULT 'locked',
  progress INT(11) NOT NULL DEFAULT 0,
  enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (enrollment_id),
  UNIQUE KEY uq_student_subject (user_id, subject_id),
  CONSTRAINT fk_student_subjects_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_subjects_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subject_modules (
  module_id INT(11) NOT NULL AUTO_INCREMENT,
  subject_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  module_order INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (module_id),
  UNIQUE KEY uq_subject_module_order (subject_id, module_order),
  CONSTRAINT fk_subject_modules_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_lessons (
  lesson_id INT(11) NOT NULL AUTO_INCREMENT,
  module_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  content_type ENUM('video','pdf','text') NOT NULL DEFAULT 'text',
  content_url VARCHAR(255) DEFAULT NULL,
  lesson_order INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (lesson_id),
  UNIQUE KEY uq_module_lesson_order (module_id, lesson_order),
  CONSTRAINT fk_module_lessons_module FOREIGN KEY (module_id) REFERENCES subject_modules(module_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_tasks (
  task_id INT(11) NOT NULL AUTO_INCREMENT,
  module_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  task_type ENUM('quiz','assignment','project') NOT NULL,
  points INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (task_id),
  UNIQUE KEY uq_module_task_title (module_id, title),
  CONSTRAINT fk_module_tasks_module FOREIGN KEY (module_id) REFERENCES subject_modules(module_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_submissions (
  submission_id INT(11) NOT NULL AUTO_INCREMENT,
  task_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  submission_file VARCHAR(255) DEFAULT NULL,
  score INT(11) DEFAULT NULL,
  feedback TEXT DEFAULT NULL,
  status ENUM('submitted','reviewed','completed') NOT NULL DEFAULT 'submitted',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (submission_id),
  UNIQUE KEY uq_task_submission_user (task_id, user_id),
  CONSTRAINT fk_task_submissions_task FOREIGN KEY (task_id) REFERENCES module_tasks(task_id) ON DELETE CASCADE,
  CONSTRAINT fk_task_submissions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS learning_paths (
  path_id INT(11) NOT NULL AUTO_INCREMENT,
  career_id INT(11) DEFAULT NULL,
  title VARCHAR(160) NOT NULL,
  phase_order INT(11) NOT NULL,
  description TEXT NOT NULL,
  PRIMARY KEY (path_id),
  UNIQUE KEY uq_learning_path_phase (career_id, phase_order)
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
  CONSTRAINT fk_roadmap_tasks_legacy_path FOREIGN KEY (path_id) REFERENCES learning_paths(path_id) ON DELETE CASCADE
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
  UNIQUE KEY uq_student_task_legacy (user_id, task_id),
  CONSTRAINT fk_student_tasks_legacy_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_tasks_legacy_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessments (
  assessment_id INT(11) NOT NULL AUTO_INCREMENT,
  task_id INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT DEFAULT NULL,
  passing_score INT(11) NOT NULL DEFAULT 70,
  PRIMARY KEY (assessment_id),
  UNIQUE KEY uq_assessments_task (task_id),
  CONSTRAINT fk_assessments_legacy_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_questions (
  question_id INT(11) NOT NULL AUTO_INCREMENT,
  assessment_id INT(11) NOT NULL,
  question_text TEXT NOT NULL,
  question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice',
  PRIMARY KEY (question_id),
  CONSTRAINT fk_assessment_questions_legacy_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_choices (
  choice_id INT(11) NOT NULL AUTO_INCREMENT,
  question_id INT(11) NOT NULL,
  choice_text TEXT NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (choice_id),
  CONSTRAINT fk_assessment_choices_legacy_question FOREIGN KEY (question_id) REFERENCES assessment_questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_attempts (
  attempt_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  assessment_id INT(11) NOT NULL,
  score INT(11) NOT NULL DEFAULT 0,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attempt_id),
  CONSTRAINT fk_assessment_attempts_legacy_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_assessment_attempts_legacy_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_roadmaps (
  roadmap_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  semester VARCHAR(100) DEFAULT NULL,
  task_title VARCHAR(255) DEFAULT NULL,
  task_type VARCHAR(100) DEFAULT NULL,
  task_status VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (roadmap_id),
  KEY idx_student_roadmaps_user (user_id),
  CONSTRAINT fk_student_roadmaps_legacy_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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
  CONSTRAINT fk_portfolio_projects_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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
  CONSTRAINT fk_mentor_assignments_student_full FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_assignments_mentor_full FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
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
  CONSTRAINT fk_mentor_feedback_assignment_full FOREIGN KEY (assignment_id) REFERENCES mentor_assignments(assignment_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_feedback_task_full FOREIGN KEY (task_id) REFERENCES module_tasks(task_id) ON DELETE SET NULL
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
  CONSTRAINT fk_mentor_messages_assignment_full FOREIGN KEY (assignment_id) REFERENCES mentor_assignments(assignment_id) ON DELETE CASCADE,
  CONSTRAINT fk_mentor_messages_sender_full FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employer_profiles (
  employer_profile_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  company_name VARCHAR(180) NOT NULL,
  company_website VARCHAR(255) DEFAULT NULL,
  industry VARCHAR(120) DEFAULT NULL,
  company_size VARCHAR(80) DEFAULT NULL,
  location VARCHAR(180) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (employer_profile_id),
  UNIQUE KEY uq_employer_profiles_user (user_id),
  CONSTRAINT fk_employer_profiles_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_profiles (
  mentor_profile_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  headline VARCHAR(180) DEFAULT NULL,
  expertise TEXT DEFAULT NULL,
  years_experience INT(11) DEFAULT 0,
  linkedin_url VARCHAR(255) DEFAULT NULL,
  portfolio_url VARCHAR(255) DEFAULT NULL,
  verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (mentor_profile_id),
  UNIQUE KEY uq_mentor_profiles_user (user_id),
  CONSTRAINT fk_mentor_profiles_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_posts (
  job_id INT(11) NOT NULL AUTO_INCREMENT,
  employer_id INT(11) NOT NULL,
  path_id INT(11) DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  employment_type ENUM('internship','part_time','full_time','contract') NOT NULL DEFAULT 'internship',
  location VARCHAR(180) DEFAULT NULL,
  remote_type ENUM('onsite','hybrid','remote') NOT NULL DEFAULT 'onsite',
  description TEXT NOT NULL,
  requirements TEXT DEFAULT NULL,
  salary_range VARCHAR(120) DEFAULT NULL,
  status ENUM('draft','open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  KEY idx_job_posts_employer (employer_id),
  KEY idx_job_posts_path (path_id),
  CONSTRAINT fk_job_posts_employer_full FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_posts_path_full FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_applications (
  application_id INT(11) NOT NULL AUTO_INCREMENT,
  job_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  cover_letter TEXT DEFAULT NULL,
  resume_file VARCHAR(255) DEFAULT NULL,
  status ENUM('submitted','reviewing','shortlisted','rejected','hired') NOT NULL DEFAULT 'submitted',
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  UNIQUE KEY uq_job_application (job_id, user_id),
  CONSTRAINT fk_job_applications_job_full FOREIGN KEY (job_id) REFERENCES job_posts(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_applications_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certifications (
  certification_id INT(11) NOT NULL AUTO_INCREMENT,
  path_id INT(11) DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  provider VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  certification_url VARCHAR(255) DEFAULT NULL,
  level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (certification_id),
  KEY idx_certifications_path (path_id),
  CONSTRAINT fk_certifications_path_full FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_certifications (
  student_certification_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  certification_id INT(11) NOT NULL,
  status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned',
  certificate_file VARCHAR(255) DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (student_certification_id),
  UNIQUE KEY uq_student_certification (user_id, certification_id),
  CONSTRAINT fk_student_certifications_user_full FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_certifications_cert_full FOREIGN KEY (certification_id) REFERENCES certifications(certification_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_career_matches (
  match_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  path_id INT(11) NULL,
  match_percentage INT(11) NOT NULL DEFAULT 0,
  current_progress INT(11) NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (match_id),
  UNIQUE KEY uq_student_career_match_path (user_id, path_id),
  KEY idx_student_career_matches_user (user_id),
  KEY idx_student_career_matches_path (path_id),
  CONSTRAINT fk_student_career_matches_user_new FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_career_matches_path_new FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, email, password, role, status, profile_completed)
VALUES
('System Administrator', 'admin@mapmyfuture.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCIB4MG2EFIxLB2Jh5g2', 'admin', 'approved', 0)
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
role = VALUES(role),
status = VALUES(status);

INSERT INTO career_paths (title, description, icon, category, average_salary_ph, industry)
VALUES
('Software Engineer', 'Builds reliable applications, APIs, databases, and production software systems for real users.', 'fa-code', 'Engineering', 'PHP 30,000 - PHP 90,000', 'Software Development'),
('UI/UX Designer', 'Designs usable digital products through research, information architecture, prototyping, and interface systems.', 'fa-pen-nib', 'Design', 'PHP 25,000 - PHP 60,000', 'Product Design'),
('Data Analyst', 'Turns raw data into cleaned datasets, dashboards, insights, and business recommendations.', 'fa-chart-simple', 'Analytics', 'PHP 28,000 - PHP 75,000', 'Data Analytics'),
('Cybersecurity Analyst', 'Protects systems by identifying risks, monitoring threats, and improving security controls.', 'fa-shield-halved', 'Security', 'PHP 35,000 - PHP 95,000', 'Cybersecurity')
ON DUPLICATE KEY UPDATE
description = VALUES(description),
icon = VALUES(icon),
category = VALUES(category),
average_salary_ph = VALUES(average_salary_ph),
industry = VALUES(industry);

INSERT IGNORE INTO career_years (path_id, year_number)
SELECT cp.path_id, y.year_number
FROM career_paths cp
JOIN (SELECT 1 AS year_number UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) y
WHERE cp.title IN ('Software Engineer','UI/UX Designer','Data Analyst','Cybersecurity Analyst');

INSERT IGNORE INTO career_semesters (year_id, semester_number)
SELECT cy.year_id, s.semester_number
FROM career_years cy
JOIN (SELECT 1 AS semester_number UNION ALL SELECT 2) s;

INSERT IGNORE INTO career_subjects (semester_id, subject_code, subject_title, description, subject_order)
SELECT csem.semester_id, seed.subject_code, seed.subject_title, seed.description, seed.subject_order
FROM (
  SELECT 'Software Engineer' career, 1 yr, 1 sem, 1 subject_order, 'SE101' subject_code, 'Programming Fundamentals' subject_title, 'Core programming concepts, control flow, functions, debugging, and computational thinking.' description
  UNION ALL SELECT 'Software Engineer',1,1,2,'SE102','Computer Fundamentals','Computer systems, operating systems, files, networks, and productivity tools for IT learners.'
  UNION ALL SELECT 'Software Engineer',1,1,3,'SE103','Mathematics for Computing','Discrete structures, logic, sets, and applied mathematics used in software development.'
  UNION ALL SELECT 'Software Engineer',1,2,1,'SE104','Object-Oriented Programming','Classes, objects, encapsulation, inheritance, and maintainable application design.'
  UNION ALL SELECT 'Software Engineer',1,2,2,'SE105','Web Fundamentals','HTML, CSS, JavaScript, accessibility basics, and responsive page construction.'
  UNION ALL SELECT 'Software Engineer',1,2,3,'SE106','Data Structures Basics','Arrays, lists, stacks, queues, maps, and problem-solving patterns.'
  UNION ALL SELECT 'Software Engineer',2,1,1,'SE201','Database Systems','Relational design, SQL queries, normalization, indexes, and transaction basics.'
  UNION ALL SELECT 'Software Engineer',2,1,2,'SE202','Server-Side Programming','PHP application structure, validation, sessions, prepared statements, and secure forms.'
  UNION ALL SELECT 'Software Engineer',2,1,3,'SE203','Software Design Patterns','Common design patterns, modularity, maintainability, and refactoring practice.'
  UNION ALL SELECT 'Software Engineer',2,2,1,'SE204','API Development','RESTful services, JSON contracts, authentication, error handling, and documentation.'
  UNION ALL SELECT 'Software Engineer',2,2,2,'SE205','Data Structures and Algorithms','Searching, sorting, recursion, complexity, trees, graphs, and coding interviews.'
  UNION ALL SELECT 'Software Engineer',2,2,3,'SE206','Version Control and Collaboration','Git workflows, pull requests, issue tracking, and team development habits.'
  UNION ALL SELECT 'Software Engineer',3,1,1,'SE301','Software Engineering Practices','Requirements, architecture, testing strategy, agile delivery, and technical documentation.'
  UNION ALL SELECT 'Software Engineer',3,1,2,'SE302','Cloud Deployment Basics','Linux, hosting, environment configuration, CI/CD concepts, and deployment monitoring.'
  UNION ALL SELECT 'Software Engineer',3,1,3,'SE303','Application Security','Input validation, access control, secure sessions, hashing, and OWASP fundamentals.'
  UNION ALL SELECT 'Software Engineer',3,2,1,'SE304','Mobile and Progressive Web Apps','Mobile-first interaction, offline-ready features, APIs, and installable web applications.'
  UNION ALL SELECT 'Software Engineer',3,2,2,'SE305','Quality Assurance and Testing','Unit tests, integration tests, test cases, bug reports, and regression workflows.'
  UNION ALL SELECT 'Software Engineer',3,2,3,'SE306','Capstone Planning','Problem selection, scope control, architecture plan, and portfolio project proposal.'
  UNION ALL SELECT 'Software Engineer',4,1,1,'SE401','Capstone Development 1','Build the first production-ready version of a software portfolio project.'
  UNION ALL SELECT 'Software Engineer',4,1,2,'SE402','Systems Integration','Integrate APIs, databases, third-party services, and deployment pipelines.'
  UNION ALL SELECT 'Software Engineer',4,1,3,'SE403','Professional Engineering Workshop','Code review, technical writing, estimation, and engineering communication.'
  UNION ALL SELECT 'Software Engineer',4,2,1,'SE404','Capstone Development 2','Finalize, test, document, and present a complete deployed software system.'
  UNION ALL SELECT 'Software Engineer',4,2,2,'SE405','Internship Readiness','Resume, portfolio, interview practice, workplace expectations, and job search strategy.'
  UNION ALL SELECT 'Software Engineer',4,2,3,'SE406','Production Maintenance','Monitoring, incident response, bug triage, versioning, and post-launch improvement.'
  UNION ALL SELECT 'UI/UX Designer',1,1,1,'UX101','Design Foundations','Visual hierarchy, layout, typography, color, usability, and inclusive design principles.'
  UNION ALL SELECT 'UI/UX Designer',1,1,2,'UX102','Computer and Web Fundamentals','Digital product vocabulary, web structure, browser behavior, and collaboration tools.'
  UNION ALL SELECT 'UI/UX Designer',1,1,3,'UX103','Human Behavior and Design Thinking','Empathy, problem framing, ideation, and decision-making in product design.'
  UNION ALL SELECT 'UI/UX Designer',1,2,1,'UX104','User Research Basics','Interviewing, surveys, observation, personas, journey maps, and research synthesis.'
  UNION ALL SELECT 'UI/UX Designer',1,2,2,'UX105','Interface Design Fundamentals','Components, spacing, grids, states, affordances, and screen composition.'
  UNION ALL SELECT 'UI/UX Designer',1,2,3,'UX106','Web Prototyping','Clickable flows, responsive mockups, and prototype testing for web products.'
  UNION ALL SELECT 'UI/UX Designer',2,1,1,'UX201','Information Architecture','Navigation systems, content models, user flows, card sorting, and findability.'
  UNION ALL SELECT 'UI/UX Designer',2,1,2,'UX202','Interaction Design','Microinteractions, feedback, form design, task completion, and error recovery.'
  UNION ALL SELECT 'UI/UX Designer',2,1,3,'UX203','Design Systems','Tokens, components, documentation, consistency, and scalable interface systems.'
  UNION ALL SELECT 'UI/UX Designer',2,2,1,'UX204','Usability Testing','Test planning, facilitation, observation, metrics, and design iteration.'
  UNION ALL SELECT 'UI/UX Designer',2,2,2,'UX205','Accessibility for Digital Products','WCAG fundamentals, contrast, keyboard flows, screen reader considerations, and inclusive patterns.'
  UNION ALL SELECT 'UI/UX Designer',2,2,3,'UX206','Frontend Collaboration','Working with HTML, CSS, developers, handoff specs, and implementation constraints.'
  UNION ALL SELECT 'UI/UX Designer',3,1,1,'UX301','Product Strategy','Business goals, product metrics, prioritization, and value proposition design.'
  UNION ALL SELECT 'UI/UX Designer',3,1,2,'UX302','UX Writing and Content Design','Clear labels, empty states, error messages, onboarding copy, and content hierarchy.'
  UNION ALL SELECT 'UI/UX Designer',3,1,3,'UX303','Portfolio Case Study Workshop','Problem framing, process storytelling, artifacts, outcomes, and critique.'
  UNION ALL SELECT 'UI/UX Designer',3,2,1,'UX304','Service Design','Blueprints, touchpoints, stakeholder maps, and end-to-end experience improvement.'
  UNION ALL SELECT 'UI/UX Designer',3,2,2,'UX305','Advanced Prototyping','Interactive prototypes, design variables, usability scenarios, and stakeholder demos.'
  UNION ALL SELECT 'UI/UX Designer',3,2,3,'UX306','Capstone Planning','Research plan, design scope, prototype milestones, and portfolio-ready outcomes.'
  UNION ALL SELECT 'UI/UX Designer',4,1,1,'UX401','Capstone Design 1','Research, synthesize, wireframe, and validate a substantial digital product concept.'
  UNION ALL SELECT 'UI/UX Designer',4,1,2,'UX402','Design Operations','Design critiques, documentation, collaboration rituals, and design quality control.'
  UNION ALL SELECT 'UI/UX Designer',4,1,3,'UX403','Product Analytics for Designers','Funnels, usability metrics, experimentation basics, and evidence-based iteration.'
  UNION ALL SELECT 'UI/UX Designer',4,2,1,'UX404','Capstone Design 2','Finalize high-fidelity prototypes, testing evidence, and a polished case study.'
  UNION ALL SELECT 'UI/UX Designer',4,2,2,'UX405','Internship Readiness','Portfolio review, interview practice, design challenge preparation, and job search strategy.'
  UNION ALL SELECT 'UI/UX Designer',4,2,3,'UX406','Professional Client Presentation','Present design rationale, tradeoffs, research evidence, and implementation guidance.'
  UNION ALL SELECT 'Data Analyst',1,1,1,'DA101','Mathematics for Data','Statistics foundations, probability, descriptive measures, and quantitative reasoning.'
  UNION ALL SELECT 'Data Analyst',1,1,2,'DA102','Computer Fundamentals','Files, spreadsheets, databases, cloud storage, and data handling tools.'
  UNION ALL SELECT 'Data Analyst',1,1,3,'DA103','Business Problem Solving','Problem framing, stakeholder questions, KPIs, and decision-focused analysis.'
  UNION ALL SELECT 'Data Analyst',1,2,1,'DA104','Spreadsheet Analytics','Data cleaning, formulas, pivot tables, charts, validation, and reporting workflows.'
  UNION ALL SELECT 'Data Analyst',1,2,2,'DA105','SQL Fundamentals','SELECT queries, filtering, grouping, joins, and database thinking.'
  UNION ALL SELECT 'Data Analyst',1,2,3,'DA106','Data Visualization Basics','Chart selection, visual encoding, dashboard layout, and narrative clarity.'
  UNION ALL SELECT 'Data Analyst',2,1,1,'DA201','Data Cleaning and Preparation','Missing values, duplicates, outliers, standardization, and reproducible cleaning steps.'
  UNION ALL SELECT 'Data Analyst',2,1,2,'DA202','Database Reporting','Aggregations, window functions, reporting tables, and dashboard-ready SQL.'
  UNION ALL SELECT 'Data Analyst',2,1,3,'DA203','Python for Analysis','Python basics, notebooks, pandas, dataframes, and exploratory analysis.'
  UNION ALL SELECT 'Data Analyst',2,2,1,'DA204','Dashboard Development','Interactive dashboards, filters, drilldowns, layout, and audience-specific reporting.'
  UNION ALL SELECT 'Data Analyst',2,2,2,'DA205','Applied Statistics','Sampling, hypothesis testing, correlation, confidence intervals, and statistical communication.'
  UNION ALL SELECT 'Data Analyst',2,2,3,'DA206','Data Ethics and Privacy','Responsible data use, consent, minimization, anonymization, and bias awareness.'
  UNION ALL SELECT 'Data Analyst',3,1,1,'DA301','Business Intelligence Systems','BI architecture, data marts, semantic layers, metrics catalogs, and governance.'
  UNION ALL SELECT 'Data Analyst',3,1,2,'DA302','Predictive Analytics Basics','Regression, classification concepts, model evaluation, and practical prediction workflows.'
  UNION ALL SELECT 'Data Analyst',3,1,3,'DA303','Analytics Storytelling','Insight writing, executive summaries, recommendations, and presentation structure.'
  UNION ALL SELECT 'Data Analyst',3,2,1,'DA304','Data Engineering Basics','Pipelines, ETL, APIs, batch jobs, and data quality checks.'
  UNION ALL SELECT 'Data Analyst',3,2,2,'DA305','Portfolio Analytics Project','Build a complete analysis from raw data to dashboard and written recommendations.'
  UNION ALL SELECT 'Data Analyst',3,2,3,'DA306','Capstone Planning','Select a data problem, define metrics, source data, and plan deliverables.'
  UNION ALL SELECT 'Data Analyst',4,1,1,'DA401','Capstone Analytics 1','Acquire, clean, model, and analyze a substantial real-world dataset.'
  UNION ALL SELECT 'Data Analyst',4,1,2,'DA402','Advanced Visualization','Complex dashboards, accessibility, performance, and decision-support design.'
  UNION ALL SELECT 'Data Analyst',4,1,3,'DA403','Stakeholder Analytics Workshop','Requirements gathering, presentation, revision cycles, and business alignment.'
  UNION ALL SELECT 'Data Analyst',4,2,1,'DA404','Capstone Analytics 2','Finalize dashboard, analysis report, recommendations, and portfolio case study.'
  UNION ALL SELECT 'Data Analyst',4,2,2,'DA405','Internship Readiness','Resume, SQL practice, case interviews, portfolio review, and job search strategy.'
  UNION ALL SELECT 'Data Analyst',4,2,3,'DA406','Analytics Operations','Monitoring reports, data refreshes, documentation, and continuous improvement.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,1,1,'CY101','Security Foundations','Confidentiality, integrity, availability, threats, vulnerabilities, and security mindset.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,1,2,'CY102','Computer and Network Fundamentals','Operating systems, TCP/IP, devices, services, and network troubleshooting.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,1,3,'CY103','Logic and Scripting Basics','Command line, simple scripts, automation logic, and technical documentation.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,2,1,'CY104','Networking Essentials','Switching, routing, IP addressing, DNS, HTTP, and network tools.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,2,2,'CY105','Linux and Windows Administration','Users, permissions, services, logs, processes, and baseline hardening.'
  UNION ALL SELECT 'Cybersecurity Analyst',1,2,3,'CY106','Cyber Ethics and Law','Responsible disclosure, Philippine cybercrime context, privacy, and professional conduct.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,1,1,'CY201','Threats and Vulnerabilities','Malware, social engineering, web threats, misconfiguration, and vulnerability lifecycle.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,1,2,'CY202','Secure Web Applications','Authentication, sessions, input validation, access control, and OWASP Top 10.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,1,3,'CY203','Security Tools and Labs','Packet analysis, scanning, log review, sandbox labs, and evidence capture.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,2,1,'CY204','Incident Response Basics','Triage, containment, eradication, recovery, lessons learned, and reporting.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,2,2,'CY205','Risk Management','Asset inventory, risk scoring, controls, policies, and compliance basics.'
  UNION ALL SELECT 'Cybersecurity Analyst',2,2,3,'CY206','Cloud Security Fundamentals','Identity, storage, network rules, monitoring, and secure cloud configuration.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,1,1,'CY301','Security Monitoring','SIEM concepts, alerts, dashboards, event correlation, and escalation workflow.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,1,2,'CY302','Digital Forensics Basics','Evidence handling, disk and memory concepts, timeline analysis, and reporting.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,1,3,'CY303','Penetration Testing Foundations','Reconnaissance, scanning, exploitation ethics, reporting, and remediation guidance.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,2,1,'CY304','Identity and Access Management','MFA, RBAC, least privilege, account lifecycle, and privileged access.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,2,2,'CY305','Security Automation','Scripted checks, log parsing, alert enrichment, and repeatable response actions.'
  UNION ALL SELECT 'Cybersecurity Analyst',3,2,3,'CY306','Capstone Planning','Choose a security scenario, define scope, lab plan, evidence, and final report.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,1,1,'CY401','Capstone Security Lab 1','Assess, document, and improve security for a realistic system environment.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,1,2,'CY402','Governance and Compliance','Policies, audits, control mapping, documentation, and organizational risk communication.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,1,3,'CY403','Threat Intelligence','Indicators, tactics, techniques, procedures, feeds, and defensive decision-making.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,2,1,'CY404','Capstone Security Lab 2','Finalize findings, remediation plan, evidence pack, and professional security report.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,2,2,'CY405','Internship Readiness','SOC interview practice, certification planning, resume, lab portfolio, and job search strategy.'
  UNION ALL SELECT 'Cybersecurity Analyst',4,2,3,'CY406','Security Operations Practice','Shift handoff, playbooks, metrics, continuous monitoring, and improvement cycles.'
) seed
JOIN career_paths cp ON cp.title = seed.career
JOIN career_years cy ON cy.path_id = cp.path_id AND cy.year_number = seed.yr
JOIN career_semesters csem ON csem.year_id = cy.year_id AND csem.semester_number = seed.sem;

INSERT IGNORE INTO subject_modules (subject_id, title, module_order)
SELECT subject_id, CONCAT(subject_title, ' Concepts'), 1 FROM career_subjects;

INSERT IGNORE INTO subject_modules (subject_id, title, module_order)
SELECT subject_id, CONCAT(subject_title, ' Lab'), 2 FROM career_subjects;

INSERT IGNORE INTO module_lessons (module_id, title, content_type, content_url, lesson_order)
SELECT sm.module_id, CONCAT('Introduction to ', cs.subject_title), 'text', NULL, 1
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 1;

INSERT IGNORE INTO module_lessons (module_id, title, content_type, content_url, lesson_order)
SELECT sm.module_id, CONCAT('Guided practice for ', cs.subject_title), 'text', NULL, 2
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 1;

INSERT IGNORE INTO module_lessons (module_id, title, content_type, content_url, lesson_order)
SELECT sm.module_id, CONCAT('Applied lab: ', cs.subject_title), 'text', NULL, 1
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 2;

INSERT IGNORE INTO module_tasks (module_id, title, task_type, points)
SELECT sm.module_id, CONCAT(cs.subject_title, ' Checkpoint Quiz'), 'quiz', 25
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 1;

INSERT IGNORE INTO module_tasks (module_id, title, task_type, points)
SELECT sm.module_id, CONCAT(cs.subject_title, ' Applied Assignment'), 'assignment', 35
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 2;

INSERT IGNORE INTO module_tasks (module_id, title, task_type, points)
SELECT sm.module_id, CONCAT(cs.subject_title, ' Portfolio Project'), 'project', 40
FROM subject_modules sm
JOIN career_subjects cs ON cs.subject_id = sm.subject_id
WHERE sm.module_order = 2;

COMMIT;

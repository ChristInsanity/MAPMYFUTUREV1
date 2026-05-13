-- Map My Future finalization migration
-- Adds production tables required by lesson progression, premium subscription, and mentor workflows.

ALTER TABLE module_lessons
    ADD COLUMN IF NOT EXISTS lesson_file VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) NOT NULL DEFAULT 0,
    MODIFY COLUMN content_type ENUM('video','pdf','text','article') NOT NULL;

CREATE TABLE IF NOT EXISTS lesson_progress (
    progress_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    status ENUM('completed') NOT NULL DEFAULT 'completed',
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (progress_id),
    UNIQUE KEY uq_lesson_progress_user_lesson (user_id, lesson_id),
    KEY idx_lesson_progress_subject (subject_id),
    CONSTRAINT fk_lesson_progress_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_lesson_final FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE,
    CONSTRAINT fk_lesson_progress_subject_final FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    attempt_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    score INT(11) DEFAULT NULL,
    status ENUM('ready','completed') NOT NULL DEFAULT 'ready',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (attempt_id),
    UNIQUE KEY uq_quiz_attempt_user_lesson (user_id, lesson_id),
    KEY idx_quiz_attempts_subject (subject_id),
    CONSTRAINT fk_quiz_attempts_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_lesson_final FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_subject_final FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_subscriptions (
    subscription_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    plan VARCHAR(100) NOT NULL DEFAULT 'free',
    plan_type VARCHAR(50) NOT NULL DEFAULT 'free',
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_months INT(11) NOT NULL DEFAULT 0,
    status ENUM('active','cancelled','expired') NOT NULL DEFAULT 'active',
    payment_method VARCHAR(80) DEFAULT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    PRIMARY KEY (subscription_id),
    UNIQUE KEY uq_student_subscriptions_user (user_id),
    CONSTRAINT fk_student_subscriptions_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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
    CONSTRAINT fk_user_applications_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_profiles (
    mentor_profile_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    age INT(11) DEFAULT NULL,
    degree VARCHAR(180) DEFAULT NULL,
    specialization VARCHAR(180) DEFAULT NULL,
    years_experience INT(11) DEFAULT 0,
    industry VARCHAR(180) DEFAULT NULL,
    resume_upload VARCHAR(255) DEFAULT NULL,
    certifications TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    github_url VARCHAR(255) DEFAULT NULL,
    behance_url VARCHAR(255) DEFAULT NULL,
    portfolio_url VARCHAR(255) DEFAULT NULL,
    experience TEXT DEFAULT NULL,
    verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mentor_profile_id),
    UNIQUE KEY uq_mentor_profiles_user (user_id),
    CONSTRAINT fk_mentor_profiles_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_certifications (
    certification_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    title VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (certification_id),
    KEY idx_mentor_certifications_user (user_id),
    CONSTRAINT fk_mentor_certifications_user_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employer_profiles (
    employer_profile_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    company_name VARCHAR(180) NOT NULL,
    business_email VARCHAR(180) DEFAULT NULL,
    industry VARCHAR(120) DEFAULT NULL,
    company_size VARCHAR(80) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    business_registration_number VARCHAR(120) DEFAULT NULL,
    business_permit_upload VARCHAR(255) DEFAULT NULL,
    company_profile_pdf VARCHAR(255) DEFAULT NULL,
    contact_person VARCHAR(180) DEFAULT NULL,
    contact_position VARCHAR(120) DEFAULT NULL,
    contact_number VARCHAR(80) DEFAULT NULL,
    office_address TEXT DEFAULT NULL,
    verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (employer_profile_id),
    UNIQUE KEY uq_employer_profiles_user (user_id),
    CONSTRAINT fk_employer_profiles_user_phase2_final FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_career_assignments (
    assignment_id INT(11) NOT NULL AUTO_INCREMENT,
    mentor_id INT(11) NOT NULL,
    career_path_id INT(11) NOT NULL,
    assigned_by_admin INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (assignment_id),
    UNIQUE KEY uq_mentor_career_assignment (mentor_id, career_path_id),
    CONSTRAINT fk_mentor_career_assignments_mentor_final FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_career_assignments_path_final FOREIGN KEY (career_path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_career_assignments_admin_final FOREIGN KEY (assigned_by_admin) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_student_requests (
    request_id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    mentor_id INT(11) NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_mentor_student_request (student_id, mentor_id),
    CONSTRAINT fk_mentor_student_requests_student_final FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_student_requests_mentor_final FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_enrollment_requests (
    request_id INT(11) NOT NULL AUTO_INCREMENT,
    mentor_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_mentor_request (mentor_id, student_id, subject_id),
    CONSTRAINT fk_mentor_requests_mentor_final FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_requests_student_final FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_requests_subject_final FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_students (
    mentor_student_id INT(11) NOT NULL AUTO_INCREMENT,
    mentor_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    status ENUM('active','completed','removed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (mentor_student_id),
    UNIQUE KEY uq_mentor_student_subject (mentor_id, student_id, subject_id),
    CONSTRAINT fk_mentor_students_mentor_final FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_students_student_final FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_students_subject_final FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_tasks (
    mentor_task_id INT(11) NOT NULL AUTO_INCREMENT,
    mentor_id INT(11) NOT NULL,
    path_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    title VARCHAR(180) NOT NULL,
    instructions TEXT NOT NULL,
    resources TEXT DEFAULT NULL,
    deadline DATE DEFAULT NULL,
    points INT(11) NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (mentor_task_id),
    CONSTRAINT fk_mentor_tasks_mentor_final FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_tasks_path_final FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_tasks_subject_final FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_tasks_lesson_final FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mentor_task_submissions (
    submission_id INT(11) NOT NULL AUTO_INCREMENT,
    mentor_task_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    submission_file VARCHAR(255) DEFAULT NULL,
    submission_link VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    score INT(11) DEFAULT NULL,
    status ENUM('submitted','approved','revision_requested') NOT NULL DEFAULT 'submitted',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (submission_id),
    UNIQUE KEY uq_mentor_task_submission (mentor_task_id, student_id),
    CONSTRAINT fk_mentor_task_submissions_task_final FOREIGN KEY (mentor_task_id) REFERENCES mentor_tasks(mentor_task_id) ON DELETE CASCADE,
    CONSTRAINT fk_mentor_task_submissions_student_final FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

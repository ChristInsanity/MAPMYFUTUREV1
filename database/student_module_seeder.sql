START TRANSACTION;

INSERT INTO career_paths (title, category, description, average_salary_ph, industry, icon)
VALUES
('UI/UX Designer', 'Design', 'Designs usable digital products through research, wireframes, prototypes, and interface systems.', 'PHP 25,000 - PHP 60,000', 'Product Design', 'fa-pen-nib'),
('Software Engineer', 'Engineering', 'Builds reliable applications, APIs, databases, and production software systems.', 'PHP 30,000 - PHP 90,000', 'Software Development', 'fa-code'),
('Data Analyst', 'Analytics', 'Turns raw data into dashboards, insights, and practical business recommendations.', 'PHP 28,000 - PHP 75,000', 'Data Analytics', 'fa-chart-simple'),
('Cybersecurity Analyst', 'Security', 'Protects systems by identifying risks, monitoring threats, and improving security controls.', 'PHP 35,000 - PHP 95,000', 'Cybersecurity', 'fa-shield-halved')
ON DUPLICATE KEY UPDATE
category = VALUES(category),
description = VALUES(description),
average_salary_ph = VALUES(average_salary_ph),
industry = VALUES(industry),
icon = VALUES(icon);

INSERT INTO learning_paths (career_id, title, phase_order, description)
SELECT path_id, 'Phase 1: Foundations', 1, 'Build the core vocabulary, tools, and habits needed for this career track.'
FROM career_paths
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description);

INSERT INTO learning_paths (career_id, title, phase_order, description)
SELECT path_id, 'Phase 2: Projects', 2, 'Apply the fundamentals through hands-on portfolio work and guided practice.'
FROM career_paths
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description);

INSERT INTO learning_paths (career_id, title, phase_order, description)
SELECT path_id, 'Phase 3: Internship Preparation', 3, 'Prepare proof of skill, feedback loops, and career-ready artifacts.'
FROM career_paths
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description);

INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Learn design thinking and UX research basics', 'lesson', 80, 3
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'UI/UX Designer') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'UX Foundations Quiz', 'quiz', 120, 1
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'UI/UX Designer') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Create a wireframe and clickable prototype', 'project', 220, 8
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'UI/UX Designer') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Submit prototype for mentor critique', 'mentor_review', 160, 2
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'UI/UX Designer') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Accessibility and Usability Quiz', 'quiz', 120, 1
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'UI/UX Designer') AND phase_order = 3;

INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Programming foundations with PHP and JavaScript', 'lesson', 100, 5
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Software Engineer') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Programming Logic Quiz', 'quiz', 120, 1
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Software Engineer') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Build a PHP and MySQL CRUD app', 'project', 260, 10
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Software Engineer') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Request mentor code review', 'mentor_review', 170, 2
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Software Engineer') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Deploy and document your application', 'certification', 180, 4
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Software Engineer') AND phase_order = 3;

INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Learn spreadsheet, SQL, and data cleaning basics', 'lesson', 90, 4
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Data Analyst') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Data Literacy Quiz', 'quiz', 120, 1
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Data Analyst') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Build a student outcomes dashboard', 'project', 250, 8
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Data Analyst') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Submit dashboard for mentor insight review', 'mentor_review', 160, 2
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Data Analyst') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Publish an analytics case study', 'certification', 180, 4
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Data Analyst') AND phase_order = 3;

INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Security fundamentals and threat vocabulary', 'lesson', 100, 5
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Cybersecurity Analyst') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Security Foundations Quiz', 'quiz', 130, 1
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Cybersecurity Analyst') AND phase_order = 1;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Create a threat model for a student app', 'project', 260, 8
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Cybersecurity Analyst') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Submit security findings for mentor review', 'mentor_review', 170, 2
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Cybersecurity Analyst') AND phase_order = 2;
INSERT IGNORE INTO roadmap_tasks (path_id, title, task_type, points, estimated_hours)
SELECT path_id, 'Prepare an entry-level security certification plan', 'certification', 190, 4
FROM learning_paths WHERE career_id = (SELECT path_id FROM career_paths WHERE title = 'Cybersecurity Analyst') AND phase_order = 3;

INSERT INTO assessments (task_id, title, description, passing_score)
SELECT task_id, title, 'Answer the checkpoint questions to unlock the next roadmap items.', 70
FROM roadmap_tasks
WHERE task_type = 'quiz'
ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), passing_score = VALUES(passing_score);

INSERT INTO users (full_name, email, password, role, status, profile_completed)
VALUES
('Alyssa Reyes', 'student@mapmyfuture.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCIB4MG2EFIxLB2Jh5g2', 'student', 'approved', 1),
('Maria Santos', 'mentor@mapmyfuture.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCIB4MG2EFIxLB2Jh5g2', 'mentor', 'approved', 0)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), status = VALUES(status), profile_completed = VALUES(profile_completed);

INSERT INTO student_profiles (user_id, course, year_level, skills, interests, dream_job, career_path, readiness_score, completed_skills, missing_skills, portfolio_projects, target_industry, ai_summary)
SELECT user_id, 'BS Information Technology', '3rd Year', 'HTML, CSS, design research, basic JavaScript', 'creative', 'UI/UX Designer', 'UI/UX Designer', 0, 0, 0, 1, 'Product Design',
'Your creative interest and IT background line up well with product design. Start by proving UX fundamentals, then build portfolio projects that show research, wireframes, prototypes, and usability thinking.'
FROM users WHERE email = 'student@mapmyfuture.test'
ON DUPLICATE KEY UPDATE
course = VALUES(course),
year_level = VALUES(year_level),
skills = VALUES(skills),
interests = VALUES(interests),
dream_job = VALUES(dream_job),
career_path = VALUES(career_path),
target_industry = VALUES(target_industry),
ai_summary = VALUES(ai_summary);

INSERT INTO student_career_matches (user_id, path_id, match_percentage, current_progress, is_primary)
SELECT u.user_id, c.path_id,
CASE c.title
  WHEN 'UI/UX Designer' THEN 92
  WHEN 'Software Engineer' THEN 84
  WHEN 'Data Analyst' THEN 78
  ELSE 70
END,
0,
CASE WHEN c.title = 'UI/UX Designer' THEN 1 ELSE 0 END
FROM users u
JOIN career_paths c
WHERE u.email = 'student@mapmyfuture.test'
ON DUPLICATE KEY UPDATE match_percentage = VALUES(match_percentage), is_primary = VALUES(is_primary);

INSERT INTO student_tasks (user_id, task_id, status, progress_percent, completed_at)
SELECT u.user_id, rt.task_id,
CASE
  WHEN rt.title IN ('Learn design thinking and UX research basics', 'UX Foundations Quiz') THEN 'completed'
  WHEN rt.title = 'Create a wireframe and clickable prototype' THEN 'in_progress'
  WHEN lp.phase_order = 1 THEN 'available'
  ELSE 'locked'
END,
CASE
  WHEN rt.title IN ('Learn design thinking and UX research basics', 'UX Foundations Quiz') THEN 100
  WHEN rt.title = 'Create a wireframe and clickable prototype' THEN 45
  ELSE 0
END,
CASE
  WHEN rt.title IN ('Learn design thinking and UX research basics', 'UX Foundations Quiz') THEN NOW()
  ELSE NULL
END
FROM users u
JOIN career_paths c ON c.title = 'UI/UX Designer'
JOIN learning_paths lp ON lp.career_id = c.path_id
JOIN roadmap_tasks rt ON rt.path_id = lp.path_id
WHERE u.email = 'student@mapmyfuture.test'
ON DUPLICATE KEY UPDATE status = VALUES(status), progress_percent = VALUES(progress_percent), completed_at = VALUES(completed_at);

INSERT INTO mentor_assignments (student_id, mentor_id, status)
SELECT s.user_id, m.user_id, 'active'
FROM users s
JOIN users m ON m.email = 'mentor@mapmyfuture.test'
WHERE s.email = 'student@mapmyfuture.test'
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO mentor_feedback (assignment_id, task_id, comments, rating)
SELECT ma.assignment_id, rt.task_id, 'Strong start. Add two user personas and explain why each screen supports their needs.', 4
FROM mentor_assignments ma
JOIN users s ON s.user_id = ma.student_id AND s.email = 'student@mapmyfuture.test'
JOIN roadmap_tasks rt ON rt.title = 'Create a wireframe and clickable prototype'
WHERE NOT EXISTS (
  SELECT 1 FROM mentor_feedback mf WHERE mf.assignment_id = ma.assignment_id AND mf.task_id = rt.task_id
);

INSERT INTO portfolio_projects (user_id, title, description, github_link, live_demo_link, image, mentor_verified)
SELECT user_id, 'Student Services App Prototype', 'A responsive prototype for improving student appointment booking and support requests.', 'https://github.com/example/student-services-prototype', 'https://example.com/student-services-prototype', NULL, 1
FROM users
WHERE email = 'student@mapmyfuture.test'
AND NOT EXISTS (
  SELECT 1 FROM portfolio_projects pp WHERE pp.user_id = users.user_id AND pp.title = 'Student Services App Prototype'
);

SET @ux_quiz = (SELECT assessment_id FROM assessments WHERE title = 'UX Foundations Quiz' LIMIT 1);
SET @access_quiz = (SELECT assessment_id FROM assessments WHERE title = 'Accessibility and Usability Quiz' LIMIT 1);
SET @code_quiz = (SELECT assessment_id FROM assessments WHERE title = 'Programming Logic Quiz' LIMIT 1);
SET @data_quiz = (SELECT assessment_id FROM assessments WHERE title = 'Data Literacy Quiz' LIMIT 1);
SET @sec_quiz = (SELECT assessment_id FROM assessments WHERE title = 'Security Foundations Quiz' LIMIT 1);

INSERT IGNORE INTO assessment_questions (assessment_id, question_text, question_type)
VALUES
(@ux_quiz, 'What is the main purpose of user research before designing screens?', 'multiple_choice'),
(@ux_quiz, 'Which artifact helps test a product flow before development?', 'multiple_choice'),
(@access_quiz, 'Why should interfaces include sufficient color contrast?', 'multiple_choice'),
(@access_quiz, 'What is a usability test meant to reveal?', 'multiple_choice'),
(@code_quiz, 'What does a prepared statement help prevent?', 'multiple_choice'),
(@code_quiz, 'Which structure repeats logic while a condition is true?', 'multiple_choice'),
(@data_quiz, 'What is the purpose of cleaning data before analysis?', 'multiple_choice'),
(@data_quiz, 'Which chart is usually best for showing a trend over time?', 'multiple_choice'),
(@sec_quiz, 'What is a threat model used for?', 'multiple_choice'),
(@sec_quiz, 'Which practice protects accounts even if a password leaks?', 'multiple_choice');

INSERT IGNORE INTO assessment_choices (question_id, choice_text, is_correct)
SELECT question_id, 'To understand user needs, pain points, and context before choosing a solution', 1 FROM assessment_questions WHERE question_text = 'What is the main purpose of user research before designing screens?'
UNION ALL SELECT question_id, 'To pick colors faster', 0 FROM assessment_questions WHERE question_text = 'What is the main purpose of user research before designing screens?'
UNION ALL SELECT question_id, 'To skip stakeholder feedback', 0 FROM assessment_questions WHERE question_text = 'What is the main purpose of user research before designing screens?'
UNION ALL SELECT question_id, 'A clickable prototype', 1 FROM assessment_questions WHERE question_text = 'Which artifact helps test a product flow before development?'
UNION ALL SELECT question_id, 'A production server', 0 FROM assessment_questions WHERE question_text = 'Which artifact helps test a product flow before development?'
UNION ALL SELECT question_id, 'A payroll report', 0 FROM assessment_questions WHERE question_text = 'Which artifact helps test a product flow before development?'
UNION ALL SELECT question_id, 'It helps more users read and use the interface clearly', 1 FROM assessment_questions WHERE question_text = 'Why should interfaces include sufficient color contrast?'
UNION ALL SELECT question_id, 'It reduces database size', 0 FROM assessment_questions WHERE question_text = 'Why should interfaces include sufficient color contrast?'
UNION ALL SELECT question_id, 'It replaces navigation', 0 FROM assessment_questions WHERE question_text = 'Why should interfaces include sufficient color contrast?'
UNION ALL SELECT question_id, 'Where real users struggle or get confused', 1 FROM assessment_questions WHERE question_text = 'What is a usability test meant to reveal?'
UNION ALL SELECT question_id, 'Only the designer preference', 0 FROM assessment_questions WHERE question_text = 'What is a usability test meant to reveal?'
UNION ALL SELECT question_id, 'Server uptime', 0 FROM assessment_questions WHERE question_text = 'What is a usability test meant to reveal?'
UNION ALL SELECT question_id, 'SQL injection', 1 FROM assessment_questions WHERE question_text = 'What does a prepared statement help prevent?'
UNION ALL SELECT question_id, 'Responsive design', 0 FROM assessment_questions WHERE question_text = 'What does a prepared statement help prevent?'
UNION ALL SELECT question_id, 'Image compression', 0 FROM assessment_questions WHERE question_text = 'What does a prepared statement help prevent?'
UNION ALL SELECT question_id, 'A loop', 1 FROM assessment_questions WHERE question_text = 'Which structure repeats logic while a condition is true?'
UNION ALL SELECT question_id, 'A constant', 0 FROM assessment_questions WHERE question_text = 'Which structure repeats logic while a condition is true?'
UNION ALL SELECT question_id, 'A comment', 0 FROM assessment_questions WHERE question_text = 'Which structure repeats logic while a condition is true?'
UNION ALL SELECT question_id, 'To remove errors, inconsistencies, and duplicates that distort insights', 1 FROM assessment_questions WHERE question_text = 'What is the purpose of cleaning data before analysis?'
UNION ALL SELECT question_id, 'To make files larger', 0 FROM assessment_questions WHERE question_text = 'What is the purpose of cleaning data before analysis?'
UNION ALL SELECT question_id, 'To hide the source', 0 FROM assessment_questions WHERE question_text = 'What is the purpose of cleaning data before analysis?'
UNION ALL SELECT question_id, 'Line chart', 1 FROM assessment_questions WHERE question_text = 'Which chart is usually best for showing a trend over time?'
UNION ALL SELECT question_id, 'Profile photo', 0 FROM assessment_questions WHERE question_text = 'Which chart is usually best for showing a trend over time?'
UNION ALL SELECT question_id, 'Password field', 0 FROM assessment_questions WHERE question_text = 'Which chart is usually best for showing a trend over time?'
UNION ALL SELECT question_id, 'To identify likely risks, attackers, assets, and mitigations', 1 FROM assessment_questions WHERE question_text = 'What is a threat model used for?'
UNION ALL SELECT question_id, 'To choose typography', 0 FROM assessment_questions WHERE question_text = 'What is a threat model used for?'
UNION ALL SELECT question_id, 'To calculate salaries', 0 FROM assessment_questions WHERE question_text = 'What is a threat model used for?'
UNION ALL SELECT question_id, 'Multi-factor authentication', 1 FROM assessment_questions WHERE question_text = 'Which practice protects accounts even if a password leaks?'
UNION ALL SELECT question_id, 'Using the same password everywhere', 0 FROM assessment_questions WHERE question_text = 'Which practice protects accounts even if a password leaks?'
UNION ALL SELECT question_id, 'Sharing login codes', 0 FROM assessment_questions WHERE question_text = 'Which practice protects accounts even if a password leaks?';

COMMIT;

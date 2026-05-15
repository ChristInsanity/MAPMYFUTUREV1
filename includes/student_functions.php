<?php

function dbFetchAll($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die('Database error: ' . e($conn->error));
    }

    if ($types !== '') {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function dbFetchOne($conn, $sql, $types = '', $params = []) {
    $rows = dbFetchAll($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function dbExecute($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die('Database error: ' . e($conn->error));
    }

    if ($types !== '') {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    return $stmt->execute();
}

function dbTableExists($conn, $table) {
    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS found FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        "s",
        [$table]
    );

    return (int)($row['found'] ?? 0) > 0;
}

function dbColumnExists($conn, $table, $column) {
    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        "ss",
        [$table, $column]
    );

    return (int)($row['found'] ?? 0) > 0;
}

function dbIndexExists($conn, $table, $index) {
    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS found FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
        "ss",
        [$table, $index]
    );

    return (int)($row['found'] ?? 0) > 0;
}

function ensureColumn($conn, $table, $column, $definition) {
    if (!dbColumnExists($conn, $table, $column)) {
        $conn->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function ensurePlatformTables($conn) {
    ensureModuleLessonsSchema($conn);
    ensureLessonProgressTable($conn);
    ensureQuizAttemptsTable($conn);
    ensureStudentSubscriptionsTable($conn);
    ensureMentorTables($conn);
}

function getStudentProfile($conn, $userId) {
    return dbFetchOne(
        $conn,
        "SELECT sp.*, u.full_name, u.email, u.profile_photo, u.profile_completed
         FROM student_profiles sp
         JOIN users u ON u.user_id = sp.user_id
         WHERE sp.user_id = ?",
        "i",
        [$userId]
    );
}

function getAllCareers($conn) {
    return dbFetchAll($conn, "SELECT * FROM career_paths ORDER BY title");
}

function decodeProfileList($value) {
    $decoded = json_decode($value ?? '[]', true);
    return is_array($decoded) ? array_values(array_filter($decoded)) : [];
}

function containsChoice($choices, $needle) {
    return in_array($needle, $choices, true);
}

function logEmail($conn, $recipientEmail, $subject, $body, $status = 'sent') {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS email_logs (
            email_log_id INT(11) NOT NULL AUTO_INCREMENT,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'sent',
            sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (email_log_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    return dbExecute(
        $conn,
        "INSERT INTO email_logs (recipient_email, subject, body, status) VALUES (?, ?, ?, ?)",
        "ssss",
        [$recipientEmail, $subject, $body, $status]
    );
}

function careerRuleScore($careerTitle, $subjects, $activities, $workStyle, $dreamJob) {
    $score = 58;

    if ($careerTitle === 'Software Engineer') {
        $score += containsChoice($subjects, 'Technology') ? 12 : 0;
        $score += containsChoice($subjects, 'Mathematics') ? 8 : 0;
        $score += containsChoice($subjects, 'Problem Solving') ? 10 : 0;
        $score += containsChoice($activities, 'Building Apps') ? 18 : 0;
        $score += containsChoice($activities, 'Managing Teams') ? 4 : 0;
        $score += $workStyle === 'Technical' ? 10 : 0;
        $score += $workStyle === 'Analytical' ? 5 : 0;
    }

    if ($careerTitle === 'UI/UX Designer') {
        $score += containsChoice($subjects, 'Design') ? 18 : 0;
        $score += containsChoice($subjects, 'Business') ? 5 : 0;
        $score += containsChoice($subjects, 'Problem Solving') ? 6 : 0;
        $score += containsChoice($activities, 'Designing Interfaces') ? 20 : 0;
        $score += containsChoice($activities, 'Building Apps') ? 5 : 0;
        $score += $workStyle === 'Creative' ? 12 : 0;
        $score += $workStyle === 'Collaborative' ? 6 : 0;
    }

    if ($careerTitle === 'Data Analyst') {
        $score += containsChoice($subjects, 'Mathematics') ? 18 : 0;
        $score += containsChoice($subjects, 'Technology') ? 7 : 0;
        $score += containsChoice($subjects, 'Business') ? 6 : 0;
        $score += containsChoice($subjects, 'Problem Solving') ? 10 : 0;
        $score += containsChoice($activities, 'Analyzing Data') ? 22 : 0;
        $score += containsChoice($activities, 'Managing Teams') ? 4 : 0;
        $score += $workStyle === 'Analytical' ? 14 : 0;
    }

    if ($careerTitle === 'Cybersecurity Analyst') {
        $score += containsChoice($subjects, 'Technology') ? 12 : 0;
        $score += containsChoice($subjects, 'Mathematics') ? 5 : 0;
        $score += containsChoice($subjects, 'Problem Solving') ? 12 : 0;
        $score += containsChoice($activities, 'Securing Systems') ? 24 : 0;
        $score += containsChoice($activities, 'Building Apps') ? 4 : 0;
        $score += $workStyle === 'Technical' ? 12 : 0;
        $score += $workStyle === 'Analytical' ? 8 : 0;
    }

    if ($dreamJob !== '' && $dreamJob === $careerTitle) {
        return max(90, min(99, $score + 15));
    }

    return max(62, min(94, $score));
}

function generateCareerMatches($conn, $answers) {
    $subjects = $answers['favorite_subjects'] ?? [];
    $activities = $answers['activity_preferences'] ?? [];
    $workStyle = $answers['work_style'] ?? '';
    $dreamJob = $answers['dream_job'] ?? '';
    $careers = getAllCareers($conn);
    $matches = [];

    foreach ($careers as $career) {
        $matches[] = [
            'path_id' => (int)$career['path_id'],
            'title' => $career['title'],
            'description' => $career['description'],
            'icon' => $career['icon'],
            'match_percentage' => careerRuleScore($career['title'], $subjects, $activities, $workStyle, $dreamJob)
        ];
    }

    usort($matches, function ($a, $b) {
        return $b['match_percentage'] <=> $a['match_percentage'];
    });

    return $matches;
}

function buildAiSummary($answers, $topMatch) {
    $studentType = $answers['student_type'];
    $subjects = implode(', ', $answers['favorite_subjects']);
    $activities = implode(', ', $answers['activity_preferences']);
    $career = $topMatch['title'];
    $score = (int)$topMatch['match_percentage'];

    return "As a {$studentType}, your strongest signals are {$subjects} and activities like {$activities}. Map My Future currently ranks {$career} as your strongest IT pathway at {$score}% because it connects your learning preferences with skills demanded in the Philippine tech market.";
}

function saveCareerMatches($conn, $userId, $matches) {
    dbExecute($conn, "DELETE FROM student_career_matches WHERE user_id = ?", "i", [$userId]);

    foreach ($matches as $index => $match) {
        dbExecute(
            $conn,
            "INSERT INTO student_career_matches (user_id, path_id, match_percentage, current_progress, is_primary)
             VALUES (?, ?, ?, 0, ?)",
            "iiii",
            [$userId, $match['path_id'], $match['match_percentage'], $index === 0 ? 1 : 0]
        );
    }
}

function saveDiscoveryProfile($conn, $userId, $answers) {
    $matches = generateCareerMatches($conn, $answers);
    $topMatch = $matches[0];
    $favoriteSubjects = json_encode($answers['favorite_subjects'], JSON_UNESCAPED_SLASHES);
    $activityPreferences = json_encode($answers['activity_preferences'], JSON_UNESCAPED_SLASHES);
    $skills = implode(', ', array_merge($answers['favorite_subjects'], $answers['activity_preferences']));
    $aiSummary = buildAiSummary($answers, $topMatch);

    dbExecute(
        $conn,
        "INSERT INTO student_profiles
         (user_id, student_type, favorite_subjects, activity_preferences, work_style, dream_job, skills, interests, career_path, readiness_score, career_match_percentage, target_industry, ai_summary)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'Philippine IT Industry', ?)
         ON DUPLICATE KEY UPDATE
            student_type = VALUES(student_type),
            favorite_subjects = VALUES(favorite_subjects),
            activity_preferences = VALUES(activity_preferences),
            work_style = VALUES(work_style),
            dream_job = VALUES(dream_job),
            skills = VALUES(skills),
            interests = VALUES(interests),
            career_path = VALUES(career_path),
            career_match_percentage = VALUES(career_match_percentage),
            target_industry = VALUES(target_industry),
            ai_summary = VALUES(ai_summary)",
        "issssssssis",
        [
            $userId,
            $answers['student_type'],
            $favoriteSubjects,
            $activityPreferences,
            $answers['work_style'],
            $answers['dream_job'],
            $skills,
            $answers['work_style'],
            $topMatch['title'],
            $topMatch['match_percentage'],
            $aiSummary
        ]
    );

    saveCareerMatches($conn, $userId, $matches);
}

function getCareerMatches($conn, $userId) {
    return dbFetchAll(
        $conn,
        "SELECT scm.*, cp.title, cp.description, cp.icon
         FROM student_career_matches scm
         JOIN career_paths cp ON cp.path_id = scm.path_id
         WHERE scm.user_id = ?
         ORDER BY scm.match_percentage DESC, cp.title",
        "i",
        [$userId]
    );
}

function getPrimaryCareerMatch($conn, $userId) {
    return dbFetchOne(
        $conn,
        "SELECT scm.*, cp.title, cp.description, cp.icon
         FROM student_career_matches scm
         JOIN career_paths cp ON cp.path_id = scm.path_id
         WHERE scm.user_id = ?
         ORDER BY scm.is_primary DESC, scm.match_percentage DESC
         LIMIT 1",
        "i",
        [$userId]
    );
}

function finalizeStudentCareer($conn, $userId, $pathId) {
    $match = dbFetchOne(
        $conn,
        "SELECT scm.*, cp.title
         FROM student_career_matches scm
         JOIN career_paths cp ON cp.path_id = scm.path_id
         WHERE scm.user_id = ? AND scm.path_id = ?",
        "ii",
        [$userId, $pathId]
    );

    if (!$match) {
        return false;
    }

    dbExecute($conn, "UPDATE student_career_matches SET is_primary = 0 WHERE user_id = ?", "i", [$userId]);
    dbExecute(
        $conn,
        "UPDATE student_career_matches SET is_primary = 1 WHERE user_id = ? AND path_id = ?",
        "ii",
        [$userId, $pathId]
    );
    dbExecute(
        $conn,
        "UPDATE student_profiles
         SET career_path_id = ?, career_path = ?, career_match_percentage = ?, readiness_score = 0
         WHERE user_id = ?",
        "isii",
        [$pathId, $match['title'], $match['match_percentage'], $userId]
    );
    dbExecute(
        $conn,
        "UPDATE users SET profile_completed = 1, career_path = ? WHERE user_id = ?",
        "si",
        [$match['title'], $userId]
    );

    initializeStudentSubjects($conn, $userId, $pathId);
    calculateReadiness($conn, $userId);
    return true;
}

function getStudentCareerPathId($conn, $userId) {
    $profile = getStudentProfile($conn, $userId);

    if (!$profile) {
        return 0;
    }

    if ((int)($profile['career_path_id'] ?? 0) > 0) {
        return (int)$profile['career_path_id'];
    }

    $primary = getPrimaryCareerMatch($conn, $userId);
    return (int)($primary['path_id'] ?? 0);
}

function ensureStudentSubscriptionsTable($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS student_subscriptions (
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
            KEY idx_student_subscriptions_user (user_id),
            CONSTRAINT fk_student_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensureColumn($conn, 'student_subscriptions', 'plan_type', "VARCHAR(50) NOT NULL DEFAULT 'free'");
    ensureColumn($conn, 'student_subscriptions', 'amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    ensureColumn($conn, 'student_subscriptions', 'duration_months', "INT(11) NOT NULL DEFAULT 0");
    ensureColumn($conn, 'student_subscriptions', 'payment_method', "VARCHAR(80) DEFAULT NULL");

    if (!dbIndexExists($conn, 'student_subscriptions', 'uq_student_subscriptions_user')) {
        $conn->query(
            "DELETE s1 FROM student_subscriptions s1
             JOIN student_subscriptions s2
             ON s1.user_id = s2.user_id
             AND s1.subscription_id < s2.subscription_id"
        );
        $conn->query("ALTER TABLE student_subscriptions ADD UNIQUE KEY uq_student_subscriptions_user (user_id)");
    }

    return true;
}

function ensureLessonProgressTable($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS lesson_progress (
            progress_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            lesson_id INT(11) NOT NULL,
            subject_id INT(11) NOT NULL,
            status ENUM('completed') NOT NULL DEFAULT 'completed',
            completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (progress_id),
            UNIQUE KEY uq_lesson_progress_user_lesson (user_id, lesson_id),
            KEY idx_lesson_progress_subject (subject_id),
            CONSTRAINT fk_lesson_progress_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_lesson_progress_lesson FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE,
            CONSTRAINT fk_lesson_progress_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (dbColumnExists($conn, 'lesson_progress', 'lesson_progress_id') && !dbColumnExists($conn, 'lesson_progress', 'progress_id')) {
        $conn->query("ALTER TABLE lesson_progress CHANGE lesson_progress_id progress_id INT(11) NOT NULL AUTO_INCREMENT");
    }

    ensureColumn($conn, 'lesson_progress', 'subject_id', "INT(11) NOT NULL DEFAULT 0");
    ensureColumn($conn, 'lesson_progress', 'status', "ENUM('completed') NOT NULL DEFAULT 'completed'");
    return true;
}

function ensureQuizAttemptsTable($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS quiz_attempts (
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
            CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_quiz_attempts_lesson FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE,
            CONSTRAINT fk_quiz_attempts_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    return true;
}

function ensureModuleLessonsSchema($conn) {
    ensureColumn($conn, 'module_lessons', 'lesson_file', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'module_lessons', 'is_premium', "TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("ALTER TABLE module_lessons MODIFY COLUMN content_type ENUM('video','pdf','text','article') NOT NULL");
}

function getActiveSubscription($conn, $userId) {
    ensureStudentSubscriptionsTable($conn);

    return dbFetchOne(
        $conn,
        "SELECT * FROM student_subscriptions WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY started_at DESC LIMIT 1",
        "i",
        [$userId]
    );
}

function hasPremiumAccess($conn, $userId) {
    return getActiveSubscription($conn, $userId) !== null;
}

function activatePremiumSubscription($conn, $userId, $planType, $amount, $durationMonths, $paymentMethod) {
    ensureStudentSubscriptionsTable($conn);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)$durationMonths . ' months'));

    return dbExecute(
        $conn,
        "INSERT INTO student_subscriptions (user_id, plan, plan_type, amount, duration_months, status, payment_method, started_at, expires_at)
         VALUES (?, 'premium', ?, ?, ?, 'active', ?, NOW(), ?)
         ON DUPLICATE KEY UPDATE
            plan = VALUES(plan),
            plan_type = VALUES(plan_type),
            amount = VALUES(amount),
            duration_months = VALUES(duration_months),
            status = 'active',
            payment_method = VALUES(payment_method),
            started_at = NOW(),
            expires_at = VALUES(expires_at)",
        "isdiis",
        [$userId, $planType, $amount, $durationMonths, $paymentMethod, $expiresAt]
    );
}

function getCompletedLessonCount($conn, $userId) {
    ensureLessonProgressTable($conn);
    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(lp.progress_id) AS completed_lessons
         FROM lesson_progress lp
         WHERE lp.user_id = ? AND lp.status = 'completed'",
        "i",
        [$userId]
    );

    return (int)($row['completed_lessons'] ?? 0);
}

function getPendingLessonQuizCount($conn, $userId) {
    ensureLessonProgressTable($conn);
    ensureQuizAttemptsTable($conn);

    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS pending_quizzes
         FROM quiz_attempts
         WHERE user_id = ? AND status = 'ready'",
        "i",
        [$userId]
    );

    return (int)($row['pending_quizzes'] ?? 0);
}

function getCompletedLessons($conn, $userId) {
    ensureLessonProgressTable($conn);
    return dbFetchAll(
        $conn,
        "SELECT ml.*
         FROM lesson_progress lp
         JOIN module_lessons ml ON ml.lesson_id = lp.lesson_id
         WHERE lp.user_id = ?
         ORDER BY ml.lesson_order",
        "i",
        [$userId]
    );
}

function initializeStudentSubjects($conn, $userId, $pathId) {
    $subjects = dbFetchAll(
        $conn,
        "SELECT cs.subject_id, cy.year_number, csem.semester_number
         FROM career_subjects cs
         JOIN career_semesters csem ON csem.semester_id = cs.semester_id
         JOIN career_years cy ON cy.year_id = csem.year_id
         WHERE cy.path_id = ?
         ORDER BY cy.year_number, csem.semester_number, cs.subject_order",
        "i",
        [$pathId]
    );

    foreach ($subjects as $subject) {
        $status = ((int)$subject['year_number'] === 1 && (int)$subject['semester_number'] === 1) ? 'available' : 'locked';

        dbExecute(
            $conn,
            "INSERT INTO student_subjects (user_id, subject_id, learning_mode, status, progress)
             VALUES (?, ?, 'free', ?, 0)
             ON DUPLICATE KEY UPDATE learning_mode = learning_mode",
            "iis",
            [$userId, $subject['subject_id'], $status]
        );
    }
}

function ensureStudentRoadmap($conn, $userId) {
    $pathId = getStudentCareerPathId($conn, $userId);

    if ($pathId > 0) {
        initializeStudentSubjects($conn, $userId, $pathId);
        unlockNextSubjects($conn, $userId);
    }
}

function unlockNextSubjects($conn, $userId) {
    $pathId = getStudentCareerPathId($conn, $userId);

    if ($pathId === 0) {
        return;
    }

    $semesters = dbFetchAll(
        $conn,
        "SELECT cy.year_number, csem.semester_number,
                COUNT(ss.enrollment_id) AS total_subjects,
                SUM(CASE WHEN ss.status = 'completed' THEN 1 ELSE 0 END) AS completed_subjects
         FROM career_semesters csem
         JOIN career_years cy ON cy.year_id = csem.year_id
         JOIN career_subjects cs ON cs.semester_id = csem.semester_id
         JOIN student_subjects ss ON ss.subject_id = cs.subject_id AND ss.user_id = ?
         WHERE cy.path_id = ?
         GROUP BY cy.year_number, csem.semester_number
         ORDER BY cy.year_number, csem.semester_number",
        "ii",
        [$userId, $pathId]
    );

    foreach ($semesters as $index => $semester) {
        if ($index === 0) {
            continue;
        }

        $previous = $semesters[$index - 1];
        $previousComplete = (int)$previous['total_subjects'] > 0
            && (int)$previous['total_subjects'] === (int)$previous['completed_subjects'];

        if ($previousComplete) {
            dbExecute(
                $conn,
                "UPDATE student_subjects ss
                 JOIN career_subjects cs ON cs.subject_id = ss.subject_id
                 JOIN career_semesters csem ON csem.semester_id = cs.semester_id
                 JOIN career_years cy ON cy.year_id = csem.year_id
                 SET ss.status = 'available'
                 WHERE ss.user_id = ?
                 AND cy.path_id = ?
                 AND cy.year_number = ?
                 AND csem.semester_number = ?
                 AND ss.status = 'locked'",
                "iiii",
                [$userId, $pathId, $semester['year_number'], $semester['semester_number']]
            );
        }
    }
}

function getStudentSubjectRows($conn, $userId) {
    ensureStudentRoadmap($conn, $userId);
    $pathId = getStudentCareerPathId($conn, $userId);

    if ($pathId === 0) {
        return [];
    }

    return dbFetchAll(
        $conn,
        "SELECT ss.*, cs.subject_code, cs.subject_title, cs.description, cs.subject_order,
                csem.semester_number, cy.year_number, cp.title AS career_title, cp.icon
         FROM student_subjects ss
         JOIN career_subjects cs ON cs.subject_id = ss.subject_id
         JOIN career_semesters csem ON csem.semester_id = cs.semester_id
         JOIN career_years cy ON cy.year_id = csem.year_id
         JOIN career_paths cp ON cp.path_id = cy.path_id
         WHERE ss.user_id = ? AND cy.path_id = ?
         ORDER BY cy.year_number, csem.semester_number, cs.subject_order",
        "ii",
        [$userId, $pathId]
    );
}

function getCurrentAcademicPosition($subjects) {
    foreach ($subjects as $subject) {
        if ($subject['status'] !== 'completed') {
            return [
                'year' => (int)$subject['year_number'],
                'semester' => (int)$subject['semester_number'],
                'subject' => $subject['subject_title'],
                'subject_id' => (int)$subject['subject_id']
            ];
        }
    }

    $last = end($subjects);
    return [
        'year' => (int)($last['year_number'] ?? 1),
        'semester' => (int)($last['semester_number'] ?? 1),
        'subject' => $last['subject_title'] ?? 'Roadmap Complete',
        'subject_id' => (int)($last['subject_id'] ?? 0)
    ];
}

function calculateReadiness($conn, $userId) {
    unlockNextSubjects($conn, $userId);

    $stats = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS total_subjects,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_subjects,
                AVG(progress) AS average_progress
         FROM student_subjects
         WHERE user_id = ?",
        "i",
        [$userId]
    );

    $total = (int)($stats['total_subjects'] ?? 0);
    $completed = (int)($stats['completed_subjects'] ?? 0);
    $average = (int)round((float)($stats['average_progress'] ?? 0));
    $readiness = $total > 0 ? (int)round((($completed / $total) * 70) + ($average * .30)) : 0;
    $missing = max(0, $total - $completed);

    dbExecute(
        $conn,
        "UPDATE student_profiles
         SET readiness_score = ?, completed_skills = ?, missing_skills = ?, career_ready = ?
         WHERE user_id = ?",
        "iiiii",
        [$readiness, $completed, $missing, ($total > 0 && $completed === $total) ? 1 : 0, $userId]
    );

    $pathId = getStudentCareerPathId($conn, $userId);

    if ($pathId > 0) {
        dbExecute(
            $conn,
            "UPDATE student_career_matches SET current_progress = ? WHERE user_id = ? AND path_id = ?",
            "iii",
            [$readiness, $userId, $pathId]
        );
    }

    return $readiness;
}

function getPendingTaskCount($conn, $userId) {
    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(mt.task_id) AS pending_tasks
         FROM student_subjects ss
         JOIN subject_modules sm ON sm.subject_id = ss.subject_id
         JOIN module_tasks mt ON mt.module_id = sm.module_id
         LEFT JOIN task_submissions ts ON ts.task_id = mt.task_id AND ts.user_id = ss.user_id AND ts.status = 'completed'
         WHERE ss.user_id = ?
         AND ss.status IN ('available', 'in_progress')
         AND ts.submission_id IS NULL",
        "i",
        [$userId]
    );

    return (int)($row['pending_tasks'] ?? 0);
}

function getMentorStatus($conn, $userId) {
    $mentor = getFeaturedMentor($conn, $userId);
    return $mentor ? 'Assigned' : 'Not assigned';
}

function getStudentProgress($conn, $userId) {
    $subjects = getStudentSubjectRows($conn, $userId);
    $readiness = calculateReadiness($conn, $userId);
    $total = count($subjects);
    $completed = count(array_filter($subjects, fn($subject) => $subject['status'] === 'completed'));
    $available = count(array_filter($subjects, fn($subject) => in_array($subject['status'], ['available', 'in_progress'], true)));
    $locked = count(array_filter($subjects, fn($subject) => $subject['status'] === 'locked'));
    $current = getCurrentAcademicPosition($subjects);
    $portfolio = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS total_projects,
                SUM(CASE WHEN mentor_verified = 1 THEN 1 ELSE 0 END) AS verified_projects
         FROM portfolio_projects
         WHERE user_id = ?",
        "i",
        [$userId]
    );

    return [
        'readiness' => $readiness,
        'overall_progress' => $total > 0 ? (int)round(($completed / $total) * 100) : 0,
        'total_subjects' => $total,
        'completed_subjects' => $completed,
        'available_subjects' => $available,
        'locked_subjects' => $locked,
        'pending_tasks' => getPendingTaskCount($conn, $userId),
        'pending_quizzes' => getPendingLessonQuizCount($conn, $userId),
        'completed_lessons' => getCompletedLessonCount($conn, $userId),
        'mentor_status' => getMentorStatus($conn, $userId),
        'premium_status' => hasPremiumAccess($conn, $userId) ? 'Premium Active' : 'Free Plan',
        'current_year' => $current['year'],
        'current_semester' => $current['semester'],
        'current_subject' => $current['subject'],
        'current_subject_id' => $current['subject_id'],
        'portfolio_projects' => (int)($portfolio['total_projects'] ?? 0),
        'verified_projects' => (int)($portfolio['verified_projects'] ?? 0)
    ];
}

function getRoadmapByYear($conn, $userId) {
    $subjects = getStudentSubjectRows($conn, $userId);
    $grouped = [];

    foreach ($subjects as $subject) {
        $year = (int)$subject['year_number'];
        $semester = (int)$subject['semester_number'];

        if (!isset($grouped[$year])) {
            $grouped[$year] = [];
        }

        if (!isset($grouped[$year][$semester])) {
            $grouped[$year][$semester] = [];
        }

        $grouped[$year][$semester][] = $subject;
    }

    return $grouped;
}

function getSubjectLearningData($conn, $userId, $subjectId) {
    ensureLessonProgressTable($conn);
    ensureModuleLessonsSchema($conn);
    ensureQuizAttemptsTable($conn);

    $subject = dbFetchOne(
        $conn,
        "SELECT ss.*, cs.subject_code, cs.subject_title, cs.description,
                csem.semester_number, cy.year_number, cp.title AS career_title
         FROM student_subjects ss
         JOIN career_subjects cs ON cs.subject_id = ss.subject_id
         JOIN career_semesters csem ON csem.semester_id = cs.semester_id
         JOIN career_years cy ON cy.year_id = csem.year_id
         JOIN career_paths cp ON cp.path_id = cy.path_id
         WHERE ss.user_id = ? AND ss.subject_id = ?",
        "ii",
        [$userId, $subjectId]
    );

    if (!$subject) {
        return null;
    }

    $modules = dbFetchAll(
        $conn,
        "SELECT * FROM subject_modules WHERE subject_id = ? ORDER BY module_order",
        "i",
        [$subjectId]
    );

    $previousLessonAllowsNext = true;

    foreach ($modules as $moduleIndex => $module) {
        $lessons = dbFetchAll(
            $conn,
            "SELECT * FROM module_lessons WHERE module_id = ? ORDER BY lesson_order",
            "i",
            [$module['module_id']]
        );

        foreach ($lessons as $lessonIndex => $lesson) {
            $progress = dbFetchOne(
                $conn,
                "SELECT 1 AS completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ? AND status = 'completed'",
                "ii",
                [$userId, $lesson['lesson_id']]
            );
            $lessons[$lessonIndex]['completed'] = (bool)($progress['completed'] ?? false);
            $quizAttempt = dbFetchOne(
                $conn,
                "SELECT * FROM quiz_attempts WHERE user_id = ? AND lesson_id = ?",
                "ii",
                [$userId, $lesson['lesson_id']]
            );
            $lessons[$lessonIndex]['quiz_status'] = $quizAttempt['status'] ?? null;
            $lessons[$lessonIndex]['quiz_score'] = $quizAttempt['score'] ?? null;
            $lessons[$lessonIndex]['lesson_available'] = $previousLessonAllowsNext || $lessons[$lessonIndex]['completed'];
            $previousLessonAllowsNext = $lessons[$lessonIndex]['completed'] && (($quizAttempt['status'] ?? '') === 'completed');
        }

        $modules[$moduleIndex]['lessons'] = $lessons;
        $modules[$moduleIndex]['tasks'] = dbFetchAll(
            $conn,
            "SELECT mt.*, ts.status AS submission_status, ts.score, ts.feedback
             FROM module_tasks mt
             LEFT JOIN task_submissions ts ON ts.task_id = mt.task_id AND ts.user_id = ?
             WHERE mt.module_id = ?
             ORDER BY mt.task_id",
            "ii",
            [$userId, $module['module_id']]
        );
    }

    return [
        'subject' => $subject,
        'modules' => $modules
    ];
}

function isLessonAvailableInSequence($conn, $userId, $lessonId) {
    $lesson = dbFetchOne(
        $conn,
        "SELECT ml.lesson_id, sm.subject_id
         FROM module_lessons ml
         JOIN subject_modules sm ON sm.module_id = ml.module_id
         WHERE ml.lesson_id = ?",
        "i",
        [$lessonId]
    );

    if (!$lesson) {
        return false;
    }

    $data = getSubjectLearningData($conn, $userId, (int)$lesson['subject_id']);

    if (!$data) {
        return false;
    }

    foreach ($data['modules'] as $module) {
        foreach ($module['lessons'] as $item) {
            if ((int)$item['lesson_id'] === $lessonId) {
                return !empty($item['lesson_available']);
            }
        }
    }

    return false;
}

function recalculateSubjectProgress($conn, $userId, $subjectId) {
    ensureLessonProgressTable($conn);
    ensureQuizAttemptsTable($conn);

    $stats = dbFetchOne(
        $conn,
        "SELECT
                COUNT(DISTINCT ml.lesson_id) AS total_lessons,
                COUNT(DISTINCT CASE WHEN lp.progress_id IS NOT NULL THEN ml.lesson_id END) AS completed_lessons,
                COUNT(DISTINCT ml.lesson_id) AS total_quizzes,
                COUNT(DISTINCT CASE WHEN qa.status = 'completed' THEN ml.lesson_id END) AS completed_quizzes
         FROM subject_modules sm
         LEFT JOIN module_lessons ml ON ml.module_id = sm.module_id
         LEFT JOIN lesson_progress lp ON lp.lesson_id = ml.lesson_id AND lp.user_id = ? AND lp.status = 'completed'
         LEFT JOIN quiz_attempts qa ON qa.lesson_id = ml.lesson_id AND qa.user_id = ?
         WHERE sm.subject_id = ?",
        "iii",
        [$userId, $userId, $subjectId]
    );

    $totalLessons = (int)($stats['total_lessons'] ?? 0);
    $completedLessons = (int)($stats['completed_lessons'] ?? 0);
    $totalQuizzes = (int)($stats['total_quizzes'] ?? 0);
    $completedQuizzes = (int)($stats['completed_quizzes'] ?? 0);

    $totalItems = $totalLessons + $totalQuizzes;
    $completedItems = $completedLessons + $completedQuizzes;
    $progress = $totalItems > 0 ? (int)round(($completedItems / $totalItems) * 100) : 0;

    dbExecute(
        $conn,
        "UPDATE student_subjects
         SET progress = ?, status = CASE
             WHEN ? = 100 THEN 'completed'
             WHEN status = 'available' AND ? > 0 THEN 'in_progress'
             ELSE status
         END,
         completed_at = CASE WHEN ? = 100 THEN NOW() ELSE completed_at END
         WHERE user_id = ? AND subject_id = ? AND status <> 'locked'",
        "iiiiii",
        [$progress, $progress, $progress, $progress, $userId, $subjectId]
    );

    unlockNextSubjects($conn, $userId);
    calculateReadiness($conn, $userId);
}

function startSubject($conn, $userId, $subjectId) {
    dbExecute(
        $conn,
        "UPDATE student_subjects
         SET status = 'in_progress', progress = GREATEST(progress, 10)
         WHERE user_id = ? AND subject_id = ? AND status = 'available'",
        "ii",
        [$userId, $subjectId]
    );
}

function markLessonComplete($conn, $userId, $lessonId) {
    ensureLessonProgressTable($conn);
    ensureModuleLessonsSchema($conn);
    ensureQuizAttemptsTable($conn);

    $lesson = dbFetchOne(
        $conn,
        "SELECT ml.lesson_id, ml.is_premium, sm.subject_id, ss.status AS subject_status
         FROM module_lessons ml
         JOIN subject_modules sm ON sm.module_id = ml.module_id
         JOIN student_subjects ss ON ss.subject_id = sm.subject_id AND ss.user_id = ?
         WHERE ml.lesson_id = ?",
        "ii",
        [$userId, $lessonId]
    );

    if (!$lesson || $lesson['subject_status'] === 'locked') {
        return false;
    }

    if (!isLessonAvailableInSequence($conn, $userId, $lessonId)) {
        return false;
    }

    if ((int)$lesson['is_premium'] === 1 && !hasPremiumAccess($conn, $userId)) {
        return false;
    }

    dbExecute(
        $conn,
        "INSERT INTO lesson_progress (user_id, lesson_id, subject_id, status, completed_at)
         VALUES (?, ?, ?, 'completed', NOW())
         ON DUPLICATE KEY UPDATE
            subject_id = VALUES(subject_id),
            status = 'completed',
            completed_at = NOW()",
        "iii",
        [$userId, $lessonId, (int)$lesson['subject_id']]
    );

    dbExecute(
        $conn,
        "INSERT INTO quiz_attempts (user_id, lesson_id, subject_id, status, created_at)
         VALUES (?, ?, ?, 'ready', NOW())
         ON DUPLICATE KEY UPDATE
            subject_id = VALUES(subject_id),
            status = IF(status = 'completed', status, 'ready')",
        "iii",
        [$userId, $lessonId, (int)$lesson['subject_id']]
    );

    recalculateSubjectProgress($conn, $userId, (int)$lesson['subject_id']);
    return true;
}

function completeModuleTask($conn, $userId, $taskId) {
    $task = dbFetchOne(
        $conn,
        "SELECT mt.*, sm.subject_id, ss.status AS subject_status
         FROM module_tasks mt
         JOIN subject_modules sm ON sm.module_id = mt.module_id
         JOIN student_subjects ss ON ss.subject_id = sm.subject_id AND ss.user_id = ?
         WHERE mt.task_id = ?",
        "ii",
        [$userId, $taskId]
    );

    if (!$task || $task['subject_status'] === 'locked') {
        return false;
    }

    $submissionStatus = $task['task_type'] === 'assignment' || $task['task_type'] === 'project' ? 'submitted' : 'completed';
    $score = $submissionStatus === 'completed' ? (int)$task['points'] : null;

    dbExecute(
        $conn,
        "INSERT INTO task_submissions (task_id, user_id, score, status)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE score = VALUES(score), status = VALUES(status), submitted_at = NOW()",
        "iiis",
        [$taskId, $userId, $score, $submissionStatus]
    );

    if ($submissionStatus === 'submitted') {
        dbExecute(
            $conn,
            "UPDATE task_submissions
             SET status = 'completed', score = ?, feedback = 'Auto-completed in free learning mode. Premium review can add mentor feedback later.'
             WHERE task_id = ? AND user_id = ?",
            "iii",
            [$task['points'], $taskId, $userId]
        );
    }

    recalculateSubjectProgress($conn, $userId, (int)$task['subject_id']);
    return true;
}

function ensureMentorTables($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS user_applications (
            application_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            role ENUM('mentor','employer') NOT NULL,
            organization_name VARCHAR(255) DEFAULT NULL,
            application_note TEXT DEFAULT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (application_id),
            UNIQUE KEY uq_user_applications_user (user_id),
            CONSTRAINT fk_user_applications_user_mentor_flow FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_profiles (
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
            CONSTRAINT fk_mentor_profiles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensureColumn($conn, 'mentor_profiles', 'age', "INT(11) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'degree', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'specialization', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'industry', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'resume_upload', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'certifications', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'bio', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'linkedin_url', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'github_url', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'behance_url', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'portfolio_url', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'mentor_profiles', 'experience', "TEXT DEFAULT NULL");

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_certifications (
            certification_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            title VARCHAR(180) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (certification_id),
            KEY idx_mentor_certifications_user (user_id),
            CONSTRAINT fk_mentor_certifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS employer_profiles (
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
            CONSTRAINT fk_employer_profiles_user_phase2 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensureColumn($conn, 'employer_profiles', 'business_email', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'website', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'business_registration_number', "VARCHAR(120) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'business_permit_upload', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'company_profile_pdf', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'contact_person', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'contact_position', "VARCHAR(120) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'contact_number', "VARCHAR(80) DEFAULT NULL");
    ensureColumn($conn, 'employer_profiles', 'office_address', "TEXT DEFAULT NULL");

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_career_assignments (
            assignment_id INT(11) NOT NULL AUTO_INCREMENT,
            mentor_id INT(11) NOT NULL,
            career_path_id INT(11) NOT NULL,
            assigned_by_admin INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (assignment_id),
            UNIQUE KEY uq_mentor_career_assignment (mentor_id, career_path_id),
            CONSTRAINT fk_mentor_career_assignments_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_career_assignments_path FOREIGN KEY (career_path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_career_assignments_admin FOREIGN KEY (assigned_by_admin) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_student_requests (
            request_id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) NOT NULL,
            mentor_id INT(11) NOT NULL,
            status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (request_id),
            UNIQUE KEY uq_mentor_student_request (student_id, mentor_id),
            CONSTRAINT fk_mentor_student_requests_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_student_requests_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_enrollment_requests (
            request_id INT(11) NOT NULL AUTO_INCREMENT,
            mentor_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            subject_id INT(11) NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (request_id),
            UNIQUE KEY uq_mentor_request (mentor_id, student_id, subject_id),
            CONSTRAINT fk_mentor_requests_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_requests_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_requests_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_students (
            mentor_student_id INT(11) NOT NULL AUTO_INCREMENT,
            mentor_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            subject_id INT(11) NOT NULL,
            status ENUM('active','completed','removed') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (mentor_student_id),
            UNIQUE KEY uq_mentor_student_subject (mentor_id, student_id, subject_id),
            CONSTRAINT fk_mentor_students_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_students_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_students_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_tasks (
            mentor_task_id INT(11) NOT NULL AUTO_INCREMENT,
            mentor_id INT(11) NOT NULL,
            assigned_student_id INT(11) DEFAULT NULL,
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
            KEY idx_mentor_tasks_assigned_student (assigned_student_id),
            CONSTRAINT fk_mentor_tasks_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_tasks_assigned_student FOREIGN KEY (assigned_student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_tasks_path FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_tasks_subject FOREIGN KEY (subject_id) REFERENCES career_subjects(subject_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_tasks_lesson FOREIGN KEY (lesson_id) REFERENCES module_lessons(lesson_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensureColumn($conn, 'mentor_tasks', 'assigned_student_id', "INT(11) DEFAULT NULL");

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_task_submissions (
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
            CONSTRAINT fk_mentor_task_submissions_task FOREIGN KEY (mentor_task_id) REFERENCES mentor_tasks(mentor_task_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_task_submissions_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_assignments (
            assignment_id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) NOT NULL,
            mentor_id INT(11) NOT NULL,
            status ENUM('active','completed','removed') NOT NULL DEFAULT 'active',
            assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (assignment_id),
            UNIQUE KEY uq_mentor_assignment_pair (student_id, mentor_id),
            KEY idx_mentor_assignments_student (student_id),
            KEY idx_mentor_assignments_mentor (mentor_id),
            CONSTRAINT fk_mentor_assignments_student_runtime FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_assignments_mentor_runtime FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_messages (
            message_id INT(11) NOT NULL AUTO_INCREMENT,
            assignment_id INT(11) NOT NULL,
            sender_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id),
            KEY idx_mentor_messages_assignment (assignment_id),
            KEY idx_mentor_messages_sender (sender_id),
            CONSTRAINT fk_mentor_messages_assignment_runtime FOREIGN KEY (assignment_id) REFERENCES mentor_assignments(assignment_id) ON DELETE CASCADE,
            CONSTRAINT fk_mentor_messages_sender_runtime FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mentor_portfolio_items (
            item_id INT(11) NOT NULL AUTO_INCREMENT,
            mentor_id INT(11) NOT NULL,
            item_type ENUM('education','experience','skill','project') NOT NULL,
            title VARCHAR(180) NOT NULL,
            description TEXT DEFAULT NULL,
            link_url VARCHAR(255) DEFAULT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (item_id),
            KEY idx_mentor_portfolio_items_mentor (mentor_id, item_type),
            CONSTRAINT fk_mentor_portfolio_items_mentor FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS employer_invites (
            invite_id INT(11) NOT NULL AUTO_INCREMENT,
            employer_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            message TEXT DEFAULT NULL,
            status ENUM('sent','accepted','declined') NOT NULL DEFAULT 'sent',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (invite_id),
            UNIQUE KEY uq_employer_invite (employer_id, student_id),
            CONSTRAINT fk_employer_invites_employer FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_employer_invites_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS job_posts (
            job_id INT(11) NOT NULL AUTO_INCREMENT,
            employer_id INT(11) NOT NULL,
            path_id INT(11) DEFAULT NULL,
            title VARCHAR(180) NOT NULL,
            department VARCHAR(180) DEFAULT NULL,
            work_setup ENUM('onsite','hybrid','remote') NOT NULL DEFAULT 'onsite',
            salary VARCHAR(120) DEFAULT NULL,
            location VARCHAR(180) DEFAULT NULL,
            employment_type VARCHAR(80) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            responsibilities TEXT DEFAULT NULL,
            qualifications TEXT DEFAULT NULL,
            required_experience VARCHAR(120) DEFAULT NULL,
            education VARCHAR(180) DEFAULT NULL,
            required_skills TEXT DEFAULT NULL,
            preferred_skills TEXT DEFAULT NULL,
            optional_skills TEXT DEFAULT NULL,
            application_deadline DATE DEFAULT NULL,
            max_applicants INT(11) DEFAULT NULL,
            hiring_process TEXT DEFAULT NULL,
            status ENUM('active','closed') NOT NULL DEFAULT 'active',
            posting_status ENUM('draft','open','closed','archived') NOT NULL DEFAULT 'open',
            views INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (job_id),
            KEY idx_job_posts_employer (employer_id),
            CONSTRAINT fk_job_posts_employer_runtime FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_job_posts_path_runtime FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    ensureColumn($conn, 'job_posts', 'department', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'work_setup', "ENUM('onsite','hybrid','remote') NOT NULL DEFAULT 'onsite'");
    ensureColumn($conn, 'job_posts', 'salary', "VARCHAR(120) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'location', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'employment_type', "VARCHAR(80) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'responsibilities', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'qualifications', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'required_experience', "VARCHAR(120) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'education', "VARCHAR(180) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'required_skills', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'preferred_skills', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'optional_skills', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'application_deadline', "DATE DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'max_applicants', "INT(11) DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'hiring_process', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'job_posts', 'status', "ENUM('active','closed') NOT NULL DEFAULT 'active'");
    ensureColumn($conn, 'job_posts', 'views', "INT(11) NOT NULL DEFAULT 0");
    ensureColumn($conn, 'job_posts', 'posting_status', "ENUM('draft','open','closed','archived') NOT NULL DEFAULT 'open'");

    $conn->query(
        "CREATE TABLE IF NOT EXISTS job_applications (
            application_id INT(11) NOT NULL AUTO_INCREMENT,
            job_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            status ENUM('submitted','reviewing','shortlisted','interview','assessment','hired','rejected') NOT NULL DEFAULT 'submitted',
            resume_path VARCHAR(255) DEFAULT NULL,
            cover_letter_path VARCHAR(255) DEFAULT NULL,
            cover_letter TEXT DEFAULT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (application_id),
            UNIQUE KEY uq_job_application (job_id, user_id),
            CONSTRAINT fk_job_applications_job_runtime FOREIGN KEY (job_id) REFERENCES job_posts(job_id) ON DELETE CASCADE,
            CONSTRAINT fk_job_applications_user_runtime FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $conn->query("UPDATE job_applications SET status = 'reviewing' WHERE status IN ('reviewed','invited')");
    $conn->query("ALTER TABLE job_applications MODIFY COLUMN status ENUM('submitted','reviewing','shortlisted','interview','assessment','hired','rejected') NOT NULL DEFAULT 'submitted'");
    ensureColumn($conn, 'job_applications', 'resume_path', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'job_applications', 'cover_letter_path', "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, 'job_applications', 'updated_at', "DATETIME DEFAULT NULL");

    $conn->query(
        "CREATE TABLE IF NOT EXISTS student_employment_history (
            employment_id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) NOT NULL,
            employer_id INT(11) NOT NULL,
            job_id INT(11) NOT NULL,
            position VARCHAR(180) NOT NULL,
            hire_date DATE NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (employment_id),
            UNIQUE KEY uq_student_job_hire (student_id, job_id),
            CONSTRAINT fk_student_employment_student FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_student_employment_employer FOREIGN KEY (employer_id) REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_student_employment_job FOREIGN KEY (job_id) REFERENCES job_posts(job_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    return true;
}

function getFeaturedMentor($conn, $userId) {
    ensureMentorTables($conn);
    return dbFetchOne(
        $conn,
        "SELECT ma.assignment_id, ma.student_id, ma.mentor_id, ma.status, ma.assigned_at,
                u.full_name, u.email, u.profile_photo
         FROM mentor_assignments ma
         JOIN users u ON u.user_id = ma.mentor_id
         WHERE ma.student_id = ?
         AND ma.status = 'active'
         ORDER BY ma.assigned_at DESC
         LIMIT 1",
        "i",
        [$userId]
    );
}

function getMentorModuleData($conn, $userId) {
    ensureMentorTables($conn);
    $mentor = getFeaturedMentor($conn, $userId);

    if (!$mentor) {
        return [
            'mentor' => null,
            'messages' => [],
            'feedback' => []
        ];
    }

    $messages = dbFetchAll(
        $conn,
        "SELECT mm.*, u.full_name
         FROM mentor_messages mm
         JOIN users u ON u.user_id = mm.sender_id
         WHERE mm.assignment_id = ?
         ORDER BY mm.created_at DESC
         LIMIT 8",
        "i",
        [$mentor['assignment_id']]
    );

    $feedback = dbFetchAll(
        $conn,
        "SELECT mf.*, mt.title AS task_title, mt.task_type
         FROM mentor_feedback mf
         LEFT JOIN module_tasks mt ON mt.task_id = mf.task_id
         WHERE mf.assignment_id = ?
         ORDER BY mf.created_at DESC
         LIMIT 8",
        "i",
        [$mentor['assignment_id']]
    );

    return [
        'mentor' => $mentor,
        'messages' => $messages,
        'feedback' => $feedback
    ];
}

function sendMentorQuestion($conn, $userId, $message) {
    $mentor = getFeaturedMentor($conn, $userId);

    if (!$mentor || trim($message) === '') {
        return false;
    }

    return dbExecute(
        $conn,
        "INSERT INTO mentor_messages (assignment_id, sender_id, message)
         VALUES (?, ?, ?)",
        "iis",
        [$mentor['assignment_id'], $userId, $message]
    );
}

function getApprovedMentors($conn) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT u.user_id, u.full_name, u.email, u.profile_photo, mp.degree, mp.specialization, mp.years_experience, mp.industry, mp.bio
         FROM users u
         LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
         WHERE u.role = 'mentor' AND u.status = 'approved'
         ORDER BY u.full_name"
    );
}

function getMentorCareerAssignments($conn, $mentorId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT mca.*, cp.title, cp.icon
         FROM mentor_career_assignments mca
         JOIN career_paths cp ON cp.path_id = mca.career_path_id
         WHERE mca.mentor_id = ?
         ORDER BY cp.title",
        "i",
        [$mentorId]
    );
}

function mentorCanServeCareer($conn, $mentorId, $careerPathId) {
    ensureMentorTables($conn);
    $row = dbFetchOne(
        $conn,
        "SELECT assignment_id FROM mentor_career_assignments WHERE mentor_id = ? AND career_path_id = ?",
        "ii",
        [$mentorId, $careerPathId]
    );
    return $row !== null;
}

function getMentorsForStudentCareer($conn, $studentId) {
    ensureMentorTables($conn);
    $careerPathId = getStudentCareerPathId($conn, $studentId);

    if ($careerPathId === 0) {
        return [];
    }

    $mentors = dbFetchAll(
        $conn,
        "SELECT u.user_id, u.full_name, u.email, u.profile_photo,
                mp.degree, mp.specialization, mp.industry, mp.years_experience, mp.bio,
                mp.linkedin_url, mp.github_url, mp.behance_url, mp.portfolio_url,
                GROUP_CONCAT(cp.title ORDER BY cp.title SEPARATOR ', ') AS assigned_careers,
                msr.status AS request_status,
                COUNT(DISTINCT ms.mentor_student_id) AS total_students
         FROM mentor_career_assignments mca
         JOIN users u ON u.user_id = mca.mentor_id AND u.role = 'mentor' AND u.status = 'approved'
         LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
         JOIN career_paths cp ON cp.path_id = mca.career_path_id
         LEFT JOIN mentor_student_requests msr ON msr.mentor_id = u.user_id AND msr.student_id = ?
         LEFT JOIN mentor_assignments ma ON ma.mentor_id = u.user_id AND ma.student_id = ? AND ma.status = 'active'
         LEFT JOIN mentor_students ms ON ms.mentor_id = u.user_id AND ms.status = 'active'
         WHERE mca.career_path_id = ?
         AND ma.assignment_id IS NULL
         AND (msr.status IS NULL OR msr.status <> 'accepted')
         GROUP BY u.user_id, u.full_name, u.email, u.profile_photo, mp.degree, mp.specialization,
                  mp.industry, mp.years_experience, mp.bio, mp.linkedin_url, mp.github_url, mp.behance_url,
                  mp.portfolio_url, msr.status
         ORDER BY u.full_name",
        "iii",
        [$studentId, $studentId, $careerPathId]
    );

    return $mentors;
}

function requestMentorEnrollment($conn, $studentId, $mentorId, $subjectId = 0) {
    ensureMentorTables($conn);

    if (!hasPremiumAccess($conn, $studentId)) {
        return false;
    }

    $careerPathId = getStudentCareerPathId($conn, $studentId);
    $mentor = dbFetchOne($conn, "SELECT user_id FROM users WHERE user_id = ? AND role = 'mentor' AND status = 'approved'", "i", [$mentorId]);

    if (!$mentor || $careerPathId === 0 || !mentorCanServeCareer($conn, $mentorId, $careerPathId)) {
        return false;
    }

    dbExecute(
        $conn,
        "INSERT INTO mentor_student_requests (student_id, mentor_id, status)
         VALUES (?, ?, 'pending')
         ON DUPLICATE KEY UPDATE status = IF(status = 'rejected', 'pending', status), created_at = NOW()",
        "ii",
        [$studentId, $mentorId]
    );

    return dbExecute(
        $conn,
        "INSERT INTO mentor_enrollment_requests (mentor_id, student_id, subject_id, status)
         VALUES (?, ?, ?, 'pending')
         ON DUPLICATE KEY UPDATE status = IF(status = 'rejected', 'pending', status), requested_at = NOW()",
        "iii",
        [$mentorId, $studentId, $subjectId ?: getCurrentAcademicPosition(getStudentSubjectRows($conn, $studentId))['subject_id']]
    );
}

function getStudentMentorRequests($conn, $studentId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT mer.*, u.full_name AS mentor_name, cs.subject_title
         FROM mentor_enrollment_requests mer
         JOIN users u ON u.user_id = mer.mentor_id
         JOIN career_subjects cs ON cs.subject_id = mer.subject_id
         WHERE mer.student_id = ?
         ORDER BY mer.requested_at DESC",
        "i",
        [$studentId]
    );
}

function getMentorIncomingRequests($conn, $mentorId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT msr.*, u.full_name, u.email, sp.career_path, sp.career_path_id
         FROM mentor_student_requests msr
         JOIN users u ON u.user_id = msr.student_id
         LEFT JOIN student_profiles sp ON sp.user_id = msr.student_id
         WHERE msr.mentor_id = ?
         ORDER BY msr.created_at DESC",
        "i",
        [$mentorId]
    );
}

function respondMentorStudentRequest($conn, $mentorId, $requestId, $status) {
    ensureMentorTables($conn);

    if (!in_array($status, ['accepted', 'rejected'], true)) {
        return false;
    }

    $request = dbFetchOne(
        $conn,
        "SELECT msr.*, sp.career_path_id
         FROM mentor_student_requests msr
         LEFT JOIN student_profiles sp ON sp.user_id = msr.student_id
         WHERE msr.request_id = ? AND msr.mentor_id = ? AND msr.status = 'pending'",
        "ii",
        [$requestId, $mentorId]
    );

    if (!$request) {
        return false;
    }

    if ((int)($request['career_path_id'] ?? 0) > 0 && !mentorCanServeCareer($conn, $mentorId, (int)$request['career_path_id'])) {
        return false;
    }

    dbExecute($conn, "UPDATE mentor_student_requests SET status = ?, reviewed_at = NOW() WHERE request_id = ?", "si", [$status, $requestId]);
    dbExecute(
        $conn,
        "UPDATE mentor_enrollment_requests
         SET status = ?, reviewed_at = NOW()
         WHERE mentor_id = ? AND student_id = ? AND status = 'pending'",
        "sii",
        [$status === 'accepted' ? 'approved' : 'rejected', $mentorId, (int)$request['student_id']]
    );

    if ($status === 'accepted') {
        $subjects = getStudentSubjectRows($conn, (int)$request['student_id']);
        $current = getCurrentAcademicPosition($subjects);
        $subjectId = (int)$current['subject_id'];

        dbExecute(
            $conn,
            "INSERT INTO mentor_students (mentor_id, student_id, subject_id, status)
             VALUES (?, ?, ?, 'active')
             ON DUPLICATE KEY UPDATE status = 'active'",
            "iii",
            [$mentorId, (int)$request['student_id'], $subjectId]
        );
        dbExecute(
            $conn,
            "INSERT INTO mentor_assignments (student_id, mentor_id, status)
             VALUES (?, ?, 'active')
             ON DUPLICATE KEY UPDATE status = 'active'",
            "ii",
            [(int)$request['student_id'], $mentorId]
        );
    }

    return true;
}

function assignMentorCareers($conn, $mentorId, $careerPathIds, $adminId) {
    ensureMentorTables($conn);
    dbExecute($conn, "DELETE FROM mentor_career_assignments WHERE mentor_id = ?", "i", [$mentorId]);

    foreach ($careerPathIds as $pathId) {
        $pathId = (int)$pathId;
        if ($pathId > 0) {
            dbExecute(
                $conn,
                "INSERT INTO mentor_career_assignments (mentor_id, career_path_id, assigned_by_admin)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE assigned_by_admin = VALUES(assigned_by_admin)",
                "iii",
                [$mentorId, $pathId, $adminId]
            );
        }
    }

    return true;
}

function setApplicantStatus($conn, $userId, $status, $adminId, $careerPathIds = []) {
    ensureMentorTables($conn);

    if (!in_array($status, ['approved', 'rejected'], true)) {
        return false;
    }

    $user = dbFetchOne($conn, "SELECT user_id, role, email, full_name FROM users WHERE user_id = ? AND role IN ('mentor','employer')", "i", [$userId]);

    if (!$user) {
        return false;
    }

    if ($status === 'approved' && $user['role'] === 'mentor' && count($careerPathIds) === 0) {
        return false;
    }

    dbExecute($conn, "UPDATE users SET status = ? WHERE user_id = ?", "si", [$status, $userId]);
    dbExecute($conn, "UPDATE user_applications SET status = ? WHERE user_id = ?", "si", [$status, $userId]);

    if ($user['role'] === 'mentor') {
        dbExecute($conn, "UPDATE mentor_profiles SET verification_status = ? WHERE user_id = ?", "si", [$status, $userId]);
        if ($status === 'approved') {
            assignMentorCareers($conn, $userId, $careerPathIds, $adminId);
        }
    } elseif ($user['role'] === 'employer') {
        dbExecute($conn, "UPDATE employer_profiles SET verification_status = ? WHERE user_id = ?", "si", [$status, $userId]);
    }

    logEmail(
        $conn,
        $user['email'],
        "Map My Future application " . $status,
        "Hi {$user['full_name']}, your {$user['role']} application has been {$status}."
    );

    return true;
}

function getAvailableMentorTasksForStudent($conn, $studentId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT mt.*, u.full_name AS mentor_name, cs.subject_title, ml.title AS lesson_title,
                mts.submission_id, mts.status AS submission_status, mts.score, mts.comment
         FROM mentor_tasks mt
         JOIN mentor_students ms ON ms.mentor_id = mt.mentor_id AND ms.subject_id = mt.subject_id AND ms.student_id = ? AND ms.status = 'active'
         JOIN users u ON u.user_id = mt.mentor_id
         JOIN career_subjects cs ON cs.subject_id = mt.subject_id
         JOIN module_lessons ml ON ml.lesson_id = mt.lesson_id
         LEFT JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ?
         WHERE mt.assigned_student_id IS NULL OR mt.assigned_student_id = ?
         ORDER BY mt.created_at DESC",
        "iii",
        [$studentId, $studentId, $studentId]
    );
}

function getMentorRoomTasks($conn, $studentId, $mentorId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT mt.*, cs.subject_title, ml.title AS lesson_title,
                mts.submission_id, mts.submission_file, mts.submission_link,
                mts.status AS submission_status, mts.score, mts.comment, mts.submitted_at, mts.reviewed_at
         FROM mentor_tasks mt
         JOIN mentor_students ms ON ms.mentor_id = mt.mentor_id AND ms.subject_id = mt.subject_id AND ms.student_id = ? AND ms.status = 'active'
         JOIN career_subjects cs ON cs.subject_id = mt.subject_id
         JOIN module_lessons ml ON ml.lesson_id = mt.lesson_id
         LEFT JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ?
         WHERE mt.mentor_id = ? AND (mt.assigned_student_id IS NULL OR mt.assigned_student_id = ?)
         ORDER BY ml.title, mt.created_at DESC",
        "iiii",
        [$studentId, $studentId, $mentorId, $studentId]
    );
}

function getStudentMentorAssignment($conn, $studentId, $mentorId) {
    ensureMentorTables($conn);

    return dbFetchOne(
        $conn,
        "SELECT ma.*, u.full_name AS mentor_name, u.email AS mentor_email
         FROM mentor_assignments ma
         JOIN users u ON u.user_id = ma.mentor_id
         WHERE ma.student_id = ? AND ma.mentor_id = ? AND ma.status = 'active'",
        "ii",
        [$studentId, $mentorId]
    );
}

function getMentorConversation($conn, $studentId, $mentorId) {
    $assignment = getStudentMentorAssignment($conn, $studentId, $mentorId);

    if (!$assignment) {
        return [];
    }

    return dbFetchAll(
        $conn,
        "SELECT mm.*, u.full_name, u.role
         FROM mentor_messages mm
         JOIN users u ON u.user_id = mm.sender_id
         WHERE mm.assignment_id = ?
         ORDER BY mm.created_at ASC",
        "i",
        [(int)$assignment['assignment_id']]
    );
}

function sendStudentMentorQuestion($conn, $studentId, $mentorId, $message) {
    $assignment = getStudentMentorAssignment($conn, $studentId, $mentorId);
    $message = trim($message);

    if (!$assignment || $message === '') {
        return false;
    }

    return dbExecute(
        $conn,
        "INSERT INTO mentor_messages (assignment_id, sender_id, message) VALUES (?, ?, ?)",
        "iis",
        [(int)$assignment['assignment_id'], $studentId, $message]
    );
}

function getMentorStudentsOverview($conn, $mentorId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT ms.*, u.full_name, u.email, sp.career_path, sp.readiness_score,
                cs.subject_code, cs.subject_title, csem.semester_number, cy.year_number,
                COALESCE(ss.progress, 0) AS roadmap_progress,
                MAX(COALESCE(mts.submitted_at, mt.created_at, ms.created_at)) AS latest_activity,
                COUNT(DISTINCT mt.mentor_task_id) AS assigned_tasks,
                COUNT(DISTINCT CASE WHEN mts.status = 'submitted' THEN mts.submission_id END) AS pending_submissions
         FROM mentor_students ms
         JOIN users u ON u.user_id = ms.student_id
         LEFT JOIN student_profiles sp ON sp.user_id = ms.student_id
         LEFT JOIN career_subjects cs ON cs.subject_id = ms.subject_id
         LEFT JOIN career_semesters csem ON csem.semester_id = cs.semester_id
         LEFT JOIN career_years cy ON cy.year_id = csem.year_id
         LEFT JOIN student_subjects ss ON ss.user_id = ms.student_id AND ss.subject_id = ms.subject_id
         LEFT JOIN mentor_tasks mt ON mt.mentor_id = ms.mentor_id
            AND mt.subject_id = ms.subject_id
            AND (mt.assigned_student_id IS NULL OR mt.assigned_student_id = ms.student_id)
         LEFT JOIN mentor_task_submissions mts ON mts.mentor_task_id = mt.mentor_task_id AND mts.student_id = ms.student_id
         WHERE ms.mentor_id = ?
         GROUP BY ms.mentor_student_id, ms.mentor_id, ms.student_id, ms.subject_id, ms.status, ms.created_at,
                  u.full_name, u.email, sp.career_path, sp.readiness_score,
                  cs.subject_code, cs.subject_title, csem.semester_number, cy.year_number, ss.progress
         ORDER BY latest_activity DESC",
        "i",
        [$mentorId]
    );
}

function getMentorAssignableLessons($conn, $mentorId, $studentId = 0) {
    ensureMentorTables($conn);

    $params = [$mentorId];
    $types = "i";
    $studentFilter = "";

    if ($studentId > 0) {
        $studentFilter = " AND ms.student_id = ?";
        $types .= "i";
        $params[] = $studentId;
    }

    return dbFetchAll(
        $conn,
        "SELECT DISTINCT cp.path_id, cp.title AS career_title,
                cs.subject_id, cs.subject_code, cs.subject_title,
                ml.lesson_id, ml.title AS lesson_title
         FROM mentor_career_assignments mca
         JOIN career_paths cp ON cp.path_id = mca.career_path_id
         JOIN career_years cy ON cy.path_id = cp.path_id
         JOIN career_semesters csem ON csem.year_id = cy.year_id
         JOIN career_subjects cs ON cs.semester_id = csem.semester_id
         JOIN subject_modules sm ON sm.subject_id = cs.subject_id
         JOIN module_lessons ml ON ml.module_id = sm.module_id
         LEFT JOIN mentor_students ms ON ms.mentor_id = mca.mentor_id AND ms.subject_id = cs.subject_id AND ms.status = 'active'
         WHERE mca.mentor_id = ? {$studentFilter}
         ORDER BY cp.title, cs.subject_title, ml.title",
        $types,
        $params
    );
}

function createMentorTask($conn, $mentorId, $studentId, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline, $points) {
    ensureMentorTables($conn);

    $allowed = dbFetchOne(
        $conn,
        "SELECT cs.subject_id
         FROM mentor_career_assignments mca
         JOIN career_years cy ON cy.path_id = mca.career_path_id
         JOIN career_semesters csem ON csem.year_id = cy.year_id
         JOIN career_subjects cs ON cs.semester_id = csem.semester_id
         JOIN subject_modules sm ON sm.subject_id = cs.subject_id
         JOIN module_lessons ml ON ml.module_id = sm.module_id
         WHERE mca.mentor_id = ? AND mca.career_path_id = ? AND cs.subject_id = ? AND ml.lesson_id = ?",
        "iiii",
        [$mentorId, $pathId, $subjectId, $lessonId]
    );

    if (!$allowed) {
        return false;
    }

    if ($studentId > 0) {
        $active = dbFetchOne(
            $conn,
            "SELECT mentor_student_id FROM mentor_students WHERE mentor_id = ? AND student_id = ? AND subject_id = ? AND status = 'active'",
            "iii",
            [$mentorId, $studentId, $subjectId]
        );

        if (!$active) {
            return false;
        }
    }

    return dbExecute(
        $conn,
        "INSERT INTO mentor_tasks (mentor_id, assigned_student_id, path_id, subject_id, lesson_id, title, instructions, resources, deadline, points)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "iiiiissssi",
        [$mentorId, $studentId > 0 ? $studentId : null, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline ?: null, $points]
    );
}

function saveMentorTaskSubmission($conn, $studentId, $taskId, $filePath, $link, $notes) {
    ensureMentorTables($conn);

    $task = dbFetchOne(
        $conn,
        "SELECT mt.mentor_task_id
         FROM mentor_tasks mt
         JOIN mentor_students ms ON ms.mentor_id = mt.mentor_id AND ms.subject_id = mt.subject_id AND ms.student_id = ? AND ms.status = 'active'
         WHERE mt.mentor_task_id = ?",
        "ii",
        [$studentId, $taskId]
    );

    if (!$task) {
        return false;
    }

    return dbExecute(
        $conn,
        "INSERT INTO mentor_task_submissions (mentor_task_id, student_id, submission_file, submission_link, notes, status, submitted_at)
         VALUES (?, ?, ?, ?, ?, 'submitted', NOW())
         ON DUPLICATE KEY UPDATE
            submission_file = VALUES(submission_file),
            submission_link = VALUES(submission_link),
            notes = VALUES(notes),
            status = 'submitted',
            submitted_at = NOW(),
            reviewed_at = NULL",
        "iisss",
        [$taskId, $studentId, $filePath, $link, $notes]
    );
}

function getMentorPortfolioItems($conn, $mentorId) {
    ensureMentorTables($conn);
    $items = dbFetchAll(
        $conn,
        "SELECT * FROM mentor_portfolio_items WHERE mentor_id = ? ORDER BY item_type, sort_order, item_id",
        "i",
        [$mentorId]
    );
    $grouped = [
        'education' => [],
        'experience' => [],
        'skill' => [],
        'project' => []
    ];

    foreach ($items as $item) {
        $grouped[$item['item_type']][] = $item;
    }

    return $grouped;
}

function replaceMentorPortfolioItems($conn, $mentorId, $type, $items) {
    ensureMentorTables($conn);

    if (!in_array($type, ['education', 'experience', 'skill', 'project'], true)) {
        return false;
    }

    dbExecute($conn, "DELETE FROM mentor_portfolio_items WHERE mentor_id = ? AND item_type = ?", "is", [$mentorId, $type]);

    foreach ($items as $index => $item) {
        $title = sanitize($item['title'] ?? '');
        if ($title === '') {
            continue;
        }

        dbExecute(
            $conn,
            "INSERT INTO mentor_portfolio_items (mentor_id, item_type, title, description, link_url, file_path, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            "isssssi",
            [
                $mentorId,
                $type,
                $title,
                sanitize($item['description'] ?? ''),
                sanitize($item['link_url'] ?? ''),
                sanitize($item['file_path'] ?? ''),
                $index
            ]
        );
    }

    return true;
}

function getEmployerDashboardStats($conn, $employerId) {
    ensureMentorTables($conn);

    $stats = [
        'active_jobs' => 0,
        'applicants' => 0,
        'hires' => 0,
        'views' => 0,
        'weekly_applications' => [],
        'monthly_applications' => [],
        'conversion' => [
            'submitted' => 0,
            'shortlisted' => 0,
            'interview' => 0,
            'assessment' => 0,
            'hired' => 0
        ]
    ];

    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(*) AS total, COALESCE(SUM(views), 0) AS views
         FROM job_posts
         WHERE employer_id = ? AND status = 'active' AND posting_status = 'open'",
        "i",
        [$employerId]
    );
    $stats['active_jobs'] = (int)($row['total'] ?? 0);
    $stats['views'] = (int)($row['views'] ?? 0);

    $row = dbFetchOne(
        $conn,
        "SELECT COUNT(ja.application_id) AS applicants,
                COUNT(CASE WHEN ja.status = 'hired' THEN 1 END) AS hires
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         WHERE jp.employer_id = ?",
        "i",
        [$employerId]
    );
    $stats['applicants'] = (int)($row['applicants'] ?? 0);
    $stats['hires'] = (int)($row['hires'] ?? 0);

    $stats['weekly_applications'] = dbFetchAll(
        $conn,
        "SELECT DATE_FORMAT(ja.applied_at, '%b %d') AS label, COUNT(*) AS total
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         WHERE jp.employer_id = ? AND ja.applied_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(ja.applied_at), DATE_FORMAT(ja.applied_at, '%b %d')
         ORDER BY DATE(ja.applied_at)",
        "i",
        [$employerId]
    );

    $stats['monthly_applications'] = dbFetchAll(
        $conn,
        "SELECT DATE_FORMAT(ja.applied_at, '%Y-%m') AS label, COUNT(*) AS total
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         WHERE jp.employer_id = ? AND ja.applied_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY DATE_FORMAT(ja.applied_at, '%Y-%m')
         ORDER BY label",
        "i",
        [$employerId]
    );

    $conversionRows = dbFetchAll(
        $conn,
        "SELECT ja.status, COUNT(*) AS total
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         WHERE jp.employer_id = ?
         GROUP BY ja.status",
        "i",
        [$employerId]
    );
    foreach ($conversionRows as $conversionRow) {
        if (array_key_exists($conversionRow['status'], $stats['conversion'])) {
            $stats['conversion'][$conversionRow['status']] = (int)$conversionRow['total'];
        }
    }

    return $stats;
}

function getEmployerApplicants($conn, $employerId) {
    ensureMentorTables($conn);

    return dbFetchAll(
        $conn,
        "SELECT ja.application_id, ja.status, ja.applied_at,
                jp.title AS job_title,
                u.user_id, u.full_name, u.email,
                sp.career_path, sp.readiness_score,
                COUNT(DISTINCT pp.project_id) AS portfolio_projects,
                COUNT(DISTINCT mc.certification_id) AS certifications,
                ei.status AS invite_status
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id AND jp.employer_id = ?
         JOIN users u ON u.user_id = ja.user_id
         LEFT JOIN student_profiles sp ON sp.user_id = u.user_id
         LEFT JOIN portfolio_projects pp ON pp.user_id = u.user_id
         LEFT JOIN mentor_certifications mc ON mc.user_id = u.user_id
         LEFT JOIN employer_invites ei ON ei.employer_id = jp.employer_id AND ei.student_id = u.user_id
         GROUP BY ja.application_id, ja.status, ja.applied_at, jp.title, u.user_id, u.full_name, u.email,
                  sp.career_path, sp.readiness_score, ei.status
         ORDER BY ja.applied_at DESC",
        "i",
        [$employerId]
    );
}

function getEmployerApplicantDetails($conn, $employerId, $studentId) {
    ensureMentorTables($conn);

    $student = dbFetchOne(
        $conn,
        "SELECT DISTINCT u.user_id, u.full_name, u.email,
                sp.career_path, sp.readiness_score, sp.skills, sp.ai_summary
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id AND jp.employer_id = ?
         JOIN users u ON u.user_id = ja.user_id
         LEFT JOIN student_profiles sp ON sp.user_id = u.user_id
         WHERE u.user_id = ?",
        "ii",
        [$employerId, $studentId]
    );

    if (!$student) {
        return null;
    }

    $student['portfolio'] = getPortfolioProjects($conn, $studentId);
    $student['certifications'] = dbFetchAll($conn, "SELECT title, file_path FROM mentor_certifications WHERE user_id = ? ORDER BY uploaded_at DESC", "i", [$studentId]);
    return $student;
}

function inviteEmployerApplicant($conn, $employerId, $studentId, $message = '') {
    ensureMentorTables($conn);

    $exists = dbFetchOne(
        $conn,
        "SELECT ja.application_id
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id AND jp.employer_id = ?
         WHERE ja.user_id = ?",
        "ii",
        [$employerId, $studentId]
    );

    if (!$exists) {
        return false;
    }

    dbExecute(
        $conn,
        "UPDATE job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         SET ja.status = 'reviewing'
         WHERE jp.employer_id = ? AND ja.user_id = ?",
        "ii",
        [$employerId, $studentId]
    );

    return dbExecute(
        $conn,
        "INSERT INTO employer_invites (employer_id, student_id, message, status)
         VALUES (?, ?, ?, 'sent')
         ON DUPLICATE KEY UPDATE message = VALUES(message), status = 'sent', created_at = NOW()",
        "iis",
        [$employerId, $studentId, $message]
    );
}

function parseSkillTags($value) {
    $parts = preg_split('/[,;\n]+/', strtolower($value ?? ''));
    $parts = array_map('trim', $parts);
    return array_values(array_filter(array_unique($parts)));
}

function getStudentSkillSignals($conn, $studentId) {
    $profile = getStudentProfile($conn, $studentId) ?: [];
    $subjects = getStudentSubjectRows($conn, $studentId);
    $completedSubjects = array_filter($subjects, fn($subject) => ($subject['status'] ?? '') === 'completed');
    $completedQuizzes = getCompletedQuizAttempts($conn, $studentId, 100);
    $projects = getPortfolioProjects($conn, $studentId);

    $text = implode(' ', [
        $profile['skills'] ?? '',
        $profile['interests'] ?? '',
        $profile['career_path'] ?? '',
        implode(' ', array_map(fn($subject) => $subject['subject_title'] ?? '', $completedSubjects)),
        implode(' ', array_map(fn($project) => ($project['title'] ?? '') . ' ' . ($project['description'] ?? ''), $projects))
    ]);

    return [
        'profile' => $profile,
        'completed_subjects' => count($completedSubjects),
        'completed_quizzes' => count($completedQuizzes),
        'projects' => count($projects),
        'readiness' => (int)($profile['readiness_score'] ?? 0),
        'text' => strtolower($text)
    ];
}

function calculateJobCompatibility($conn, $studentId, $job) {
    $signals = getStudentSkillSignals($conn, $studentId);
    $requiredSkills = parseSkillTags($job['required_skills'] ?? '');
    $matched = 0;

    foreach ($requiredSkills as $skill) {
        if ($skill !== '' && str_contains($signals['text'], $skill)) {
            $matched++;
        }
    }

    $skillScore = count($requiredSkills) > 0 ? (int)round(($matched / count($requiredSkills)) * 45) : 25;
    $readinessScore = min(25, (int)round($signals['readiness'] * .25));
    $subjectScore = min(15, $signals['completed_subjects'] * 3);
    $quizScore = min(8, $signals['completed_quizzes'] * 2);
    $projectScore = min(7, $signals['projects'] * 3);

    return max(35, min(99, $skillScore + $readinessScore + $subjectScore + $quizScore + $projectScore));
}

function getRelevantJobsForStudent($conn, $studentId) {
    ensureMentorTables($conn);
    $pathId = getStudentCareerPathId($conn, $studentId);

    if ($pathId === 0) {
        return [];
    }

    $jobs = dbFetchAll(
        $conn,
        "SELECT jp.*, u.full_name AS employer_name, ep.company_name, ep.industry,
                ja.status AS application_status
         FROM job_posts jp
         JOIN users u ON u.user_id = jp.employer_id
         LEFT JOIN employer_profiles ep ON ep.user_id = jp.employer_id
         LEFT JOIN job_applications ja ON ja.job_id = jp.job_id AND ja.user_id = ?
         WHERE jp.status = 'active' AND jp.posting_status = 'open' AND (jp.path_id = ? OR jp.path_id IS NULL)
         ORDER BY jp.created_at DESC",
        "ii",
        [$studentId, $pathId]
    );

    foreach ($jobs as $index => $job) {
        $jobs[$index]['compatibility'] = calculateJobCompatibility($conn, $studentId, $job);
    }

    usort($jobs, fn($a, $b) => $b['compatibility'] <=> $a['compatibility']);
    return $jobs;
}

function getJobDetailsForStudent($conn, $studentId, $jobId) {
    ensureMentorTables($conn);
    $pathId = getStudentCareerPathId($conn, $studentId);

    $job = dbFetchOne(
        $conn,
        "SELECT jp.*, u.full_name AS employer_name, ep.company_name, ep.industry,
                ja.application_id, ja.status AS application_status
         FROM job_posts jp
         JOIN users u ON u.user_id = jp.employer_id
         LEFT JOIN employer_profiles ep ON ep.user_id = jp.employer_id
         LEFT JOIN job_applications ja ON ja.job_id = jp.job_id AND ja.user_id = ?
         WHERE jp.job_id = ? AND jp.status = 'active' AND jp.posting_status = 'open' AND (jp.path_id = ? OR jp.path_id IS NULL)",
        "iii",
        [$studentId, $jobId, $pathId]
    );

    if (!$job) {
        return null;
    }

    $job['compatibility'] = calculateJobCompatibility($conn, $studentId, $job);
    return $job;
}

function createJobApplication($conn, $studentId, $jobId, $resumePath, $coverLetterPath, $coverLetterText = '') {
    ensureMentorTables($conn);
    $job = getJobDetailsForStudent($conn, $studentId, $jobId);

    if (!$job) {
        return false;
    }

    return dbExecute(
        $conn,
        "INSERT INTO job_applications (job_id, user_id, status, resume_path, cover_letter_path, cover_letter, applied_at, updated_at)
         VALUES (?, ?, 'submitted', ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            resume_path = VALUES(resume_path),
            cover_letter_path = VALUES(cover_letter_path),
            cover_letter = VALUES(cover_letter),
            status = IF(status IN ('rejected'), 'submitted', status),
            updated_at = NOW()",
        "iisss",
        [$jobId, $studentId, $resumePath, $coverLetterPath, $coverLetterText]
    );
}

function createEmployerJob($conn, $employerId, $data) {
    ensureMentorTables($conn);

    $postingStatus = sanitize($data['posting_status'] ?? 'open');
    if (!in_array($postingStatus, ['draft', 'open', 'closed', 'archived'], true)) {
        $postingStatus = 'open';
    }

    $legacyStatus = in_array($postingStatus, ['closed', 'archived'], true) ? 'closed' : 'active';
    $deadline = sanitize($data['application_deadline'] ?? '');
    $deadline = $deadline !== '' ? $deadline : null;
    $maxApplicants = (int)($data['max_applicants'] ?? 0);
    $maxApplicants = $maxApplicants > 0 ? $maxApplicants : null;
    $jobId = (int)($data['job_id'] ?? 0);

    $params = [
        (int)($data['path_id'] ?? 0) ?: null,
        sanitize($data['title'] ?? ''),
        sanitize($data['department'] ?? ''),
        sanitize($data['work_setup'] ?? 'onsite'),
        sanitize($data['salary'] ?? ''),
        sanitize($data['location'] ?? ''),
        sanitize($data['employment_type'] ?? ''),
        sanitize($data['description'] ?? ''),
        sanitize($data['responsibilities'] ?? ''),
        sanitize($data['qualifications'] ?? ''),
        sanitize($data['required_experience'] ?? ''),
        sanitize($data['education'] ?? ''),
        sanitize($data['required_skills'] ?? ''),
        sanitize($data['preferred_skills'] ?? ''),
        sanitize($data['optional_skills'] ?? ''),
        $deadline,
        $maxApplicants,
        sanitize($data['hiring_process'] ?? ''),
        $legacyStatus,
        $postingStatus
    ];

    if ($jobId > 0) {
        return dbExecute(
            $conn,
            "UPDATE job_posts
             SET path_id = ?, title = ?, department = ?, work_setup = ?, salary = ?, location = ?, employment_type = ?,
                 description = ?, responsibilities = ?, qualifications = ?, required_experience = ?, education = ?,
                 required_skills = ?, preferred_skills = ?, optional_skills = ?, application_deadline = ?, max_applicants = ?,
                 hiring_process = ?, status = ?, posting_status = ?
             WHERE job_id = ? AND employer_id = ?",
            "isssssssssssssssisssii",
            array_merge($params, [$jobId, $employerId])
        );
    }

    return dbExecute(
        $conn,
        "INSERT INTO job_posts
         (employer_id, path_id, title, department, work_setup, salary, location, employment_type, description, responsibilities,
          qualifications, required_experience, education, required_skills, preferred_skills, optional_skills, application_deadline,
          max_applicants, hiring_process, status, posting_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "iisssssssssssssssisss",
        array_merge([$employerId], $params)
    );
}

function getEmployerJobs($conn, $employerId) {
    ensureMentorTables($conn);

    $jobs = dbFetchAll(
        $conn,
        "SELECT jp.*, cp.title AS career_title,
                COALESCE(app.applicant_count, 0) AS applicant_count,
                COALESCE(app.active_applicants, 0) AS active_applicants,
                COALESCE(app.submitted_count, 0) AS submitted_count,
                COALESCE(app.reviewing_count, 0) AS reviewing_count,
                COALESCE(app.shortlisted_count, 0) AS shortlisted_count,
                COALESCE(app.interview_count, 0) AS interview_count,
                COALESCE(app.assessment_count, 0) AS assessment_count,
                COALESCE(app.hired_count, 0) AS hired_count
         FROM job_posts jp
         LEFT JOIN career_paths cp ON cp.path_id = jp.path_id
         LEFT JOIN (
            SELECT job_id,
                   COUNT(application_id) AS applicant_count,
                   COUNT(CASE WHEN status NOT IN ('hired','rejected') THEN 1 END) AS active_applicants,
                   COUNT(CASE WHEN status = 'submitted' THEN 1 END) AS submitted_count,
                   COUNT(CASE WHEN status = 'reviewing' THEN 1 END) AS reviewing_count,
                   COUNT(CASE WHEN status = 'shortlisted' THEN 1 END) AS shortlisted_count,
                   COUNT(CASE WHEN status = 'interview' THEN 1 END) AS interview_count,
                   COUNT(CASE WHEN status = 'assessment' THEN 1 END) AS assessment_count,
                   COUNT(CASE WHEN status = 'hired' THEN 1 END) AS hired_count
            FROM job_applications
            GROUP BY job_id
         ) app ON app.job_id = jp.job_id
         WHERE jp.employer_id = ?
         ORDER BY jp.created_at DESC",
        "i",
        [$employerId]
    );

    foreach ($jobs as $index => $job) {
        $applicants = dbFetchAll($conn, "SELECT user_id FROM job_applications WHERE job_id = ?", "i", [(int)$job['job_id']]);
        $totalCompatibility = 0;
        foreach ($applicants as $applicant) {
            $totalCompatibility += calculateJobCompatibility($conn, (int)$applicant['user_id'], $job);
        }

        $jobs[$index]['compatibility_average'] = count($applicants) > 0 ? (int)round($totalCompatibility / count($applicants)) : 0;
        $stageCounts = [
            'Assessment' => (int)$job['assessment_count'],
            'Interview' => (int)$job['interview_count'],
            'Shortlisted' => (int)$job['shortlisted_count'],
            'Reviewing' => (int)$job['reviewing_count'],
            'New' => (int)$job['submitted_count'],
            'Hired' => (int)$job['hired_count']
        ];
        $jobs[$index]['hiring_stage'] = 'No applicants';
        foreach ($stageCounts as $label => $count) {
            if ($count > 0) {
                $jobs[$index]['hiring_stage'] = $label;
                break;
            }
        }
    }

    return $jobs;
}

function updateEmployerJobStatus($conn, $employerId, $jobId, $postingStatus) {
    ensureMentorTables($conn);

    if (!in_array($postingStatus, ['draft', 'open', 'closed', 'archived'], true)) {
        return false;
    }

    $legacyStatus = in_array($postingStatus, ['closed', 'archived'], true) ? 'closed' : 'active';
    return dbExecute(
        $conn,
        "UPDATE job_posts SET posting_status = ?, status = ? WHERE job_id = ? AND employer_id = ?",
        "ssii",
        [$postingStatus, $legacyStatus, $jobId, $employerId]
    );
}

function duplicateEmployerJob($conn, $employerId, $jobId) {
    ensureMentorTables($conn);

    $job = dbFetchOne($conn, "SELECT * FROM job_posts WHERE job_id = ? AND employer_id = ?", "ii", [$jobId, $employerId]);
    if (!$job) {
        return false;
    }

    $job['title'] = ($job['title'] ?? 'Job') . ' Copy';
    $job['posting_status'] = 'draft';
    unset($job['job_id']);

    return createEmployerJob($conn, $employerId, $job);
}

function getEmployerApplicantsByStage($conn, $employerId) {
    ensureMentorTables($conn);

    $rows = dbFetchAll(
        $conn,
        "SELECT ja.*, jp.title AS job_title, jp.employer_id, jp.required_skills, jp.preferred_skills, jp.optional_skills,
                u.full_name, u.email,
                sp.career_path, sp.readiness_score
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         JOIN users u ON u.user_id = ja.user_id
         LEFT JOIN student_profiles sp ON sp.user_id = u.user_id
         WHERE jp.employer_id = ?
         ORDER BY ja.updated_at DESC, ja.applied_at DESC",
        "i",
        [$employerId]
    );

    $stages = ['submitted' => [], 'reviewing' => [], 'shortlisted' => [], 'interview' => [], 'assessment' => [], 'hired' => [], 'rejected' => []];
    foreach ($rows as $row) {
        $row['compatibility'] = calculateJobCompatibility($conn, (int)$row['user_id'], $row);
        $stages[$row['status']][] = $row;
    }
    return $stages;
}

function updateJobApplicationStatus($conn, $employerId, $applicationId, $status) {
    ensureMentorTables($conn);

    if (!in_array($status, ['reviewing', 'shortlisted', 'interview', 'assessment', 'hired', 'rejected'], true)) {
        return false;
    }

    $application = dbFetchOne(
        $conn,
        "SELECT ja.*, jp.employer_id, jp.title
         FROM job_applications ja
         JOIN job_posts jp ON jp.job_id = ja.job_id
         WHERE ja.application_id = ? AND jp.employer_id = ?",
        "ii",
        [$applicationId, $employerId]
    );

    if (!$application) {
        return false;
    }

    dbExecute($conn, "UPDATE job_applications SET status = ?, updated_at = NOW() WHERE application_id = ?", "si", [$status, $applicationId]);

    if ($status === 'hired') {
        dbExecute(
            $conn,
            "INSERT INTO student_employment_history (student_id, employer_id, job_id, position, hire_date)
             VALUES (?, ?, ?, ?, CURDATE())
             ON DUPLICATE KEY UPDATE position = VALUES(position), hire_date = VALUES(hire_date)",
            "iiis",
            [(int)$application['user_id'], $employerId, (int)$application['job_id'], $application['title']]
        );
    }

    return true;
}

function getSalesReportData($conn) {
    ensureStudentSubscriptionsTable($conn);
    ensureMentorTables($conn);

    $totals = dbFetchOne(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN YEAR(started_at) = YEAR(CURDATE()) AND MONTH(started_at) = MONTH(CURDATE()) THEN amount ELSE 0 END), 0) AS monthly_revenue,
                COUNT(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) THEN 1 END) AS active_premium_users
         FROM student_subscriptions"
    );

    $mentorEnrollments = dbFetchOne($conn, "SELECT COUNT(*) AS total FROM mentor_student_requests WHERE status = 'accepted'");
    $monthly = dbFetchAll(
        $conn,
        "SELECT DATE_FORMAT(started_at, '%Y-%m') AS label, COALESCE(SUM(amount), 0) AS total
         FROM student_subscriptions
         WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY DATE_FORMAT(started_at, '%Y-%m')
         ORDER BY label"
    );
    $plans = dbFetchAll(
        $conn,
        "SELECT plan_type AS label, COUNT(*) AS total
         FROM student_subscriptions
         WHERE status = 'active'
         GROUP BY plan_type
         ORDER BY total DESC"
    );
    $mentorRevenue = dbFetchAll(
        $conn,
        "SELECT cp.title AS label, COALESCE(SUM(ss.amount), 0) AS total
         FROM mentor_student_requests msr
         JOIN student_profiles sp ON sp.user_id = msr.student_id
         JOIN career_paths cp ON cp.path_id = sp.career_path_id
         JOIN student_subscriptions ss ON ss.user_id = msr.student_id AND ss.status = 'active'
         WHERE msr.status = 'accepted'
         GROUP BY cp.path_id, cp.title
         ORDER BY total DESC
         LIMIT 8"
    );
    $jobPostings = dbFetchAll(
        $conn,
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, COUNT(*) AS total
         FROM job_posts
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
         ORDER BY label"
    );
    $hiredStudents = dbFetchAll(
        $conn,
        "SELECT DATE_FORMAT(hire_date, '%Y-%m') AS label, COUNT(*) AS total
         FROM student_employment_history
         WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY DATE_FORMAT(hire_date, '%Y-%m')
         ORDER BY label"
    );
    $hiringTotals = dbFetchOne(
        $conn,
        "SELECT
            (SELECT COUNT(*) FROM job_posts WHERE status = 'active' AND posting_status = 'open') AS active_jobs,
            (SELECT COUNT(*) FROM job_applications) AS applications,
            (SELECT COUNT(*) FROM student_employment_history) AS hired_students"
    );

    return [
        'totals' => [
            'total_revenue' => (float)($totals['total_revenue'] ?? 0),
            'monthly_revenue' => (float)($totals['monthly_revenue'] ?? 0),
            'active_premium_users' => (int)($totals['active_premium_users'] ?? 0),
            'mentor_enrollments' => (int)($mentorEnrollments['total'] ?? 0),
            'employer_subscriptions' => 0,
            'active_jobs' => (int)($hiringTotals['active_jobs'] ?? 0),
            'applications' => (int)($hiringTotals['applications'] ?? 0),
            'hired_students' => (int)($hiringTotals['hired_students'] ?? 0),
            'refunds' => 0
        ],
        'monthly_sales' => $monthly,
        'plan_distribution' => $plans,
        'mentor_revenue' => $mentorRevenue,
        'job_postings' => $jobPostings,
        'hired_students' => $hiredStudents
    ];
}

function getPortfolioProjects($conn, $userId) {
    return dbFetchAll(
        $conn,
        "SELECT * FROM portfolio_projects WHERE user_id = ? ORDER BY created_at DESC",
        "i",
        [$userId]
    );
}

function createPortfolioProject($conn, $userId, $title, $description, $githubLink, $liveDemoLink, $imagePath) {
    dbExecute(
        $conn,
        "INSERT INTO portfolio_projects
         (user_id, title, description, github_link, live_demo_link, image)
         VALUES (?, ?, ?, ?, ?, ?)",
        "isssss",
        [$userId, $title, $description, $githubLink, $liveDemoLink, $imagePath]
    );

    calculateReadiness($conn, $userId);
}

function getDynamicProcessingMessages($profile) {
    $subjects = decodeProfileList($profile['favorite_subjects'] ?? '[]');
    $activities = decodeProfileList($profile['activity_preferences'] ?? '[]');
    $subjectText = count($subjects) > 0 ? implode(' and ', array_slice($subjects, 0, 2)) : 'your learning preferences';
    $activityText = count($activities) > 0 ? $activities[0] : 'your strongest interests';
    $career = $profile['career_path'] ?? 'your career pathway';

    return [
        "Analyzing your personality and learning preferences, including strengths in {$subjectText}...",
        "Comparing your strengths with Philippine IT industry demand...",
        "Matching your profile with professional career pathways, including your interest in {$activityText}...",
        "Designing your 4-year academic roadmap for {$career}...",
        "Preparing your personalized future map..."
    ];
}

function getRoadmapTasks($conn, $userId) {
    $subjects = getStudentSubjectRows($conn, $userId);
    $rows = [];

    foreach ($subjects as $subject) {
        $rows[] = [
            'student_task_id' => (int)$subject['enrollment_id'],
            'status' => $subject['status'],
            'progress_percent' => (int)$subject['progress'],
            'mentor_feedback' => null,
            'completed_at' => $subject['completed_at'] ?? null,
            'task_id' => (int)$subject['subject_id'],
            'task_title' => $subject['subject_title'],
            'task_type' => 'lesson',
            'points' => 100,
            'estimated_hours' => 24,
            'path_id' => (int)$subject['subject_id'],
            'phase_title' => 'Year ' . $subject['year_number'] . ' Semester ' . $subject['semester_number'],
            'phase_order' => ((int)$subject['year_number'] * 10) + (int)$subject['semester_number'],
            'phase_description' => $subject['description'],
            'assessment_id' => null
        ];
    }

    return $rows;
}

function getUpcomingMilestones($conn, $userId, $limit = 4) {
    return array_slice(array_values(array_filter(getRoadmapTasks($conn, $userId), function ($task) {
        return in_array($task['status'], ['available', 'in_progress'], true);
    })), 0, $limit);
}

function groupTasksByPhase($tasks) {
    $grouped = [];

    foreach ($tasks as $task) {
        $key = (int)$task['phase_order'];

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'title' => $task['phase_title'],
                'description' => $task['phase_description'],
                'tasks' => []
            ];
        }

        $grouped[$key]['tasks'][] = $task;
    }

    return $grouped;
}

function taskIcon($type) {
    $icons = [
        'lesson' => 'fa-book-open',
        'project' => 'fa-layer-group',
        'quiz' => 'fa-brain',
        'assignment' => 'fa-file-lines',
        'mentor_review' => 'fa-users',
        'certification' => 'fa-certificate'
    ];

    return $icons[$type] ?? 'fa-route';
}

function statusClass($status) {
    $classes = [
        'locked' => 'text-slate-500 border-slate-700 bg-slate-900/70',
        'available' => 'text-blue-300 border-blue-500/30 bg-blue-500/10',
        'in_progress' => 'text-yellow-300 border-yellow-500/30 bg-yellow-500/10',
        'submitted' => 'text-purple-300 border-purple-500/30 bg-purple-500/10',
        'completed' => 'text-green-300 border-green-500/30 bg-green-500/10',
    ];

    return $classes[$status] ?? $classes['locked'];
}

function readableStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}

function getAssessments($conn, $userId) {
    $pathId = getStudentCareerPathId($conn, $userId);

    if ($pathId === 0) {
        return [];
    }

    return dbFetchAll(
        $conn,
        "SELECT a.*, rt.title AS task_title,
                COALESCE(st.status, 'available') AS task_status,
                aa.score AS latest_score,
                aa.passed AS latest_passed
         FROM assessments a
         JOIN roadmap_tasks rt ON rt.task_id = a.task_id
         LEFT JOIN student_tasks st ON st.task_id = rt.task_id AND st.user_id = ?
         LEFT JOIN assessment_attempts aa ON aa.assessment_id = a.assessment_id AND aa.user_id = ?
         WHERE rt.path_id = ?
         ORDER BY a.title",
        "iii",
        [$userId, $userId, $pathId]
    );
}

function getAssessmentWithQuestions($conn, $assessmentId, $userId) {
    $assessment = dbFetchOne(
        $conn,
        "SELECT a.*, COALESCE(st.status, 'available') AS task_status
         FROM assessments a
         JOIN roadmap_tasks rt ON rt.task_id = a.task_id
         LEFT JOIN student_tasks st ON st.task_id = rt.task_id AND st.user_id = ?
         WHERE a.assessment_id = ?",
        "ii",
        [$userId, $assessmentId]
    );

    if (!$assessment) {
        return null;
    }

    $questions = dbFetchAll(
        $conn,
        "SELECT q.* FROM assessment_questions q WHERE q.assessment_id = ? ORDER BY q.question_id",
        "i",
        [$assessmentId]
    );

    foreach ($questions as $index => $question) {
        $questions[$index]['choices'] = dbFetchAll(
            $conn,
            "SELECT * FROM assessment_choices WHERE question_id = ? ORDER BY choice_id",
            "i",
            [$question['question_id']]
        );
    }

    $assessment['questions'] = $questions;
    return $assessment;
}

function gradeAssessment($conn, $assessmentId, $answers) {
    $questionIds = array_keys($answers);
    $correct = 0;
    $total = 0;

    foreach ($questionIds as $questionId) {
        $selectedChoiceId = (int)$answers[$questionId];
        $choice = dbFetchOne(
            $conn,
            "SELECT is_correct FROM assessment_choices WHERE choice_id = ? AND question_id = ?",
            "ii",
            [$selectedChoiceId, (int)$questionId]
        );

        if ($choice && (int)$choice['is_correct'] === 1) {
            $correct++;
        }

        $total++;
    }

    return $total > 0 ? (int)round(($correct / $total) * 100) : 0;
}

function saveAssessmentAttempt($conn, $userId, $assessmentId, $score) {
    $assessment = dbFetchOne(
        $conn,
        "SELECT * FROM assessments WHERE assessment_id = ?",
        "i",
        [$assessmentId]
    );

    if (!$assessment) {
        return false;
    }

    $passed = $score >= (int)$assessment['passing_score'] ? 1 : 0;

    dbExecute(
        $conn,
        "INSERT INTO assessment_attempts (user_id, assessment_id, score, passed)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE score = VALUES(score), passed = VALUES(passed), attempted_at = CURRENT_TIMESTAMP",
        "iiii",
        [$userId, $assessmentId, $score, $passed]
    );

    if ($passed) {
        dbExecute(
            $conn,
            "UPDATE student_tasks st
             JOIN assessments a ON a.task_id = st.task_id
             SET st.status = 'completed', st.progress_percent = 100
             WHERE st.user_id = ? AND a.assessment_id = ?",
            "ii",
            [$userId, $assessmentId]
        );
    }

    return $passed === 1;
}

function getReadyQuizAttempts($conn, $userId) {
    ensureQuizAttemptsTable($conn);

    return dbFetchAll(
        $conn,
        "SELECT qa.*, ml.title AS lesson_title, cs.subject_title, cs.subject_code
         FROM quiz_attempts qa
         JOIN module_lessons ml ON ml.lesson_id = qa.lesson_id
         JOIN career_subjects cs ON cs.subject_id = qa.subject_id
         WHERE qa.user_id = ? AND qa.status = 'ready'
         ORDER BY qa.created_at DESC",
        "i",
        [$userId]
    );
}

function getCompletedQuizAttempts($conn, $userId, $limit = 12) {
    ensureQuizAttemptsTable($conn);

    return dbFetchAll(
        $conn,
        "SELECT qa.*, ml.title AS lesson_title, cs.subject_title, cs.subject_code
         FROM quiz_attempts qa
         JOIN module_lessons ml ON ml.lesson_id = qa.lesson_id
         JOIN career_subjects cs ON cs.subject_id = qa.subject_id
         WHERE qa.user_id = ? AND qa.status = 'completed'
         ORDER BY qa.completed_at DESC
         LIMIT ?",
        "ii",
        [$userId, $limit]
    );
}

function completeReadyQuizAttempt($conn, $userId, $attemptId) {
    ensureQuizAttemptsTable($conn);
    $attempt = dbFetchOne($conn, "SELECT * FROM quiz_attempts WHERE attempt_id = ? AND user_id = ? AND status = 'ready'", "ii", [$attemptId, $userId]);

    if (!$attempt) {
        return null;
    }

    $score = rand(70, 100);
    dbExecute(
        $conn,
        "UPDATE quiz_attempts
         SET score = ?, status = 'completed', completed_at = NOW()
         WHERE attempt_id = ? AND user_id = ?",
        "iii",
        [$score, $attemptId, $userId]
    );
    recalculateSubjectProgress($conn, $userId, (int)$attempt['subject_id']);

    return [
        'lesson_id' => (int)$attempt['lesson_id'],
        'subject_id' => (int)$attempt['subject_id'],
        'score' => $score
    ];
}

function hasCompletedLessonQuiz($conn, $userId, $lessonId) {
    ensureQuizAttemptsTable($conn);
    $attempt = dbFetchOne($conn, "SELECT status FROM quiz_attempts WHERE user_id = ? AND lesson_id = ?", "ii", [$userId, $lessonId]);
    return $attempt && $attempt['status'] === 'completed';
}

function getLessonQuizStatus($conn, $userId, $lessonId) {
    ensureQuizAttemptsTable($conn);
    $attempt = dbFetchOne($conn, "SELECT * FROM quiz_attempts WHERE user_id = ? AND lesson_id = ?", "ii", [$userId, $lessonId]);

    if (!$attempt) {
        return ['available' => false];
    }

    return [
        'available' => true,
        'completed' => $attempt['status'] === 'completed',
        'score' => $attempt['score'],
        'attempted_at' => $attempt['completed_at'] ?? $attempt['created_at']
    ];
}
?>

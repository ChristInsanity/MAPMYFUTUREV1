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
        'mentor_status' => getMentorStatus($conn, $userId),
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

    foreach ($modules as $moduleIndex => $module) {
        $modules[$moduleIndex]['lessons'] = dbFetchAll(
            $conn,
            "SELECT * FROM module_lessons WHERE module_id = ? ORDER BY lesson_order",
            "i",
            [$module['module_id']]
        );
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

function recalculateSubjectProgress($conn, $userId, $subjectId) {
    $stats = dbFetchOne(
        $conn,
        "SELECT COUNT(mt.task_id) AS total_tasks,
                SUM(CASE WHEN ts.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
         FROM subject_modules sm
         JOIN module_tasks mt ON mt.module_id = sm.module_id
         LEFT JOIN task_submissions ts ON ts.task_id = mt.task_id AND ts.user_id = ?
         WHERE sm.subject_id = ?",
        "ii",
        [$userId, $subjectId]
    );

    $total = (int)($stats['total_tasks'] ?? 0);
    $completed = (int)($stats['completed_tasks'] ?? 0);
    $progress = $total > 0 ? (int)round(($completed / $total) * 100) : 0;

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

function getFeaturedMentor($conn, $userId) {
    return dbFetchOne(
        $conn,
        "SELECT ma.*, u.full_name, u.email, u.profile_photo
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
    return [];
}

function getAssessmentWithQuestions($conn, $assessmentId, $userId) {
    return null;
}

function gradeAssessment($conn, $assessmentId, $answers) {
    return 0;
}

function saveAssessmentAttempt($conn, $userId, $assessmentId, $score) {
    return false;
}

?>

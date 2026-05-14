<?php

function ensureProfileEnhancementColumns($conn) {
    ensureColumn($conn, 'student_profiles', 'bio', "TEXT DEFAULT NULL");
    ensureColumn($conn, 'student_profiles', 'goals', "TEXT DEFAULT NULL");
}

function getUserAccount($conn, $userId, $role = null) {
    $sql = "SELECT user_id, full_name, email, role, status, profile_photo, profile_completed, career_path, created_at
            FROM users
            WHERE user_id = ?";
    $types = "i";
    $params = [$userId];

    if ($role !== null) {
        $sql .= " AND role = ?";
        $types = "is";
        $params[] = $role;
    }

    return dbFetchOne($conn, $sql, $types, $params);
}

function profileInitials($name) {
    $parts = preg_split('/\s+/', trim($name ?? 'User'));
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $second = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $first . $second;
}

function profilePhotoUrl($photoPath, $prefix = '../') {
    if (empty($photoPath)) {
        return '';
    }

    if (preg_match('/^(https?:)?\/\//', $photoPath) || str_starts_with($photoPath, '/') || str_starts_with($photoPath, '../')) {
        return $photoPath;
    }

    return $prefix . $photoPath;
}

function saveProfilePhotoUpload($conn, $userId, $field = 'profile_photo') {
    if (empty($_FILES[$field]['name'])) {
        return [true, null, null];
    }

    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return [false, null, 'Unable to read uploaded profile photo.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return [false, null, 'Profile photo must be JPG, PNG, or WebP.'];
    }

    $imageInfo = @getimagesize($_FILES[$field]['tmp_name']);
    if ($imageInfo === false) {
        return [false, null, 'Uploaded file is not a valid image.'];
    }

    $uploadDir = __DIR__ . '/../uploads/profile_photos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'profile-' . (int)$userId . '-' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return [false, null, 'Unable to save profile photo.'];
    }

    $relativePath = 'uploads/profile_photos/' . $filename;
    $ok = dbExecute($conn, "UPDATE users SET profile_photo = ? WHERE user_id = ?", "si", [$relativePath, $userId]);
    if ($ok) {
        $_SESSION['profile_photo'] = $relativePath;
    }

    return [$ok, $relativePath, $ok ? null : 'Unable to update profile photo.'];
}

function updateUserIdentity($conn, $userId, $fullName, $email) {
    $fullName = sanitize($fullName);
    $email = sanitize($email);

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Enter a valid full name and email.'];
    }

    $existing = dbFetchOne($conn, "SELECT user_id FROM users WHERE email = ? AND user_id <> ?", "si", [$email, $userId]);
    if ($existing) {
        return [false, 'That email is already used by another account.'];
    }

    $ok = dbExecute($conn, "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?", "ssi", [$fullName, $email, $userId]);
    if ($ok) {
        $_SESSION['name'] = $fullName;
        $_SESSION['full_name'] = $fullName;
    }

    return [$ok, $ok ? 'Profile updated.' : 'Unable to update account details.'];
}

function profileNoticeClass($success) {
    return $success
        ? 'border-green-500 bg-green-500/10 text-green-200'
        : 'border-red-500 bg-red-500/10 text-red-200';
}

?>

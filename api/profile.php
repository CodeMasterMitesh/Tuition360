<?php

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

$action = $_REQUEST['action'] ?? 'get';
$userId = intval($_SESSION['user']['id'] ?? 0);

if (!$userId) {
    send_json(false, 'Unauthorized');
    exit;
}

try {
    switch ($action) {
        case 'get':
            $stmt = mysqli_prepare($conn, "SELECT id, branch_id, role, name, email, mobile, address, date_of_birth, gender, photo, created_at FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            
            if ($user) {
                send_json(true, null, $user);
            } else {
                send_json(false, 'User not found');
            }
            break;

        case 'update':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;

            if (empty($name) || empty($email)) {
                send_json(false, 'Name and email are required');
                break;
            }

            // Check if email is already used by another user
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if (mysqli_fetch_assoc($res)) {
                send_json(false, 'Email already exists');
                break;
            }

            $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, mobile = ?, address = ?, date_of_birth = ?, gender = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $email, $mobile, $address, $date_of_birth, $gender, $userId);
            
            if (mysqli_stmt_execute($stmt)) {
                // Update session data
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                send_json(true, 'Profile updated successfully');
            } else {
                send_json(false, 'Failed to update profile');
            }
            break;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                send_json(false, 'All password fields are required');
                break;
            }

            if ($newPassword !== $confirmPassword) {
                send_json(false, 'New passwords do not match');
                break;
            }

            if (strlen($newPassword) < 6) {
                send_json(false, 'Password must be at least 6 characters');
                break;
            }

            // Verify current password
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                send_json(false, 'Current password is incorrect');
                break;
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $hashedPassword, $userId);

            if (mysqli_stmt_execute($stmt)) {
                send_json(true, 'Password changed successfully');
            } else {
                send_json(false, 'Failed to change password');
            }
            break;

        case 'upload_photo':
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                send_json(false, 'No photo uploaded or upload error');
                break;
            }

            $file = $_FILES['photo'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                send_json(false, 'Invalid file type. Only JPG, PNG, and GIF allowed');
                break;
            }

            if ($file['size'] > $maxSize) {
                send_json(false, 'File too large. Maximum size is 5MB');
                break;
            }

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Delete old photo if exists
                $stmt = mysqli_prepare($conn, "SELECT photo FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($res);
                
                if ($user && $user['photo']) {
                    $oldPhotoPath = __DIR__ . '/../public/uploads/profiles/' . basename($user['photo']);
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Update database
                $photoUrl = '/public/uploads/profiles/' . $filename;
                $stmt = mysqli_prepare($conn, "UPDATE users SET photo = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'si', $photoUrl, $userId);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['user']['photo'] = $photoUrl;
                    send_json(true, 'Photo uploaded successfully', ['photo' => $photoUrl]);
                } else {
                    send_json(false, 'Failed to update photo in database');
                }
            } else {
                send_json(false, 'Failed to upload photo');
            }
            break;

        default:
            send_json(false, 'Unknown action');
    }
} catch (Exception $e) {
    send_json(false, 'Server error', null, ['exception' => $e->getMessage()]);
}

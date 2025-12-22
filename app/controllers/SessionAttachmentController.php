<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class SessionAttachmentController {

    const UPLOAD_DIR = __DIR__ . '/../../public/uploads/sessions/';
    const MAX_FILE_SIZE = 104857600; // 100MB
    const ALLOWED_TYPES = [
        'application/pdf' => 'pdf',
        'video/mp4' => 'video',
        'video/mpeg' => 'video',
        'video/quicktime' => 'video',
        'video/webm' => 'video',
        'audio/mpeg' => 'audio',
        'audio/wav' => 'audio',
        'audio/webm' => 'audio',
        'application/msword' => 'document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
        'application/vnd.ms-excel' => 'document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'document',
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'image/webp' => 'image'
    ];

    /**
     * Get attachments for a session completion
     */
    public static function getByCompletion(int $completionId) {
        global $conn;
        $rows = [];
        $sql = "SELECT sa.*, u.name AS uploader_name FROM session_attachments sa
                LEFT JOIN users u ON u.id = sa.uploaded_by
                WHERE sa.completion_id = ?
                ORDER BY sa.created_at DESC";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $completionId);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $rows;
    }

    /**
     * Upload an attachment
     */
    public static function upload(int $completionId, array $file, string $description = '', int $uploadedBy = 0) {
        global $conn;

        // Validate file
        if (!isset($file['tmp_name']) || !isset($file['type']) || !isset($file['size'])) {
            return false;
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return false; // File too large
        }

        $mimeType = $file['type'];
        $fileType = self::ALLOWED_TYPES[$mimeType] ?? 'other';

        // Create upload directory if needed
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Generate unique filename
        $originalName = basename($file['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('session_') . '_' . time() . '.' . $ext;
        $uploadPath = self::UPLOAD_DIR . $uniqueName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return false;
        }

        // Save to database
        $sql = "INSERT INTO session_attachments (completion_id, file_name, file_path, file_type, mime_type, file_size, uploaded_by, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        $relativePath = 'public/uploads/sessions/' . $uniqueName;
        $fileSize = filesize($uploadPath);

        mysqli_stmt_bind_param($stmt, 'issssiis', 
            $completionId, $originalName, $relativePath, $fileType, $mimeType, $fileSize, $uploadedBy, $description
        );

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $id;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Delete an attachment
     */
    public static function delete(int $attachmentId) {
        global $conn;

        // Get file path
        $sql = "SELECT file_path FROM session_attachments WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $attachmentId);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                if ($row && file_exists($row['file_path'])) {
                    @unlink($row['file_path']);
                }
            }
            mysqli_stmt_close($stmt);
        }

        // Delete from database
        $sql = "DELETE FROM session_attachments WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $attachmentId);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        }
        return false;
    }

    /**
     * Get a single attachment
     */
    public static function get(int $id) {
        global $conn;
        $sql = "SELECT * FROM session_attachments WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                return $row ?: null;
            }
            mysqli_stmt_close($stmt);
        }
        return null;
    }
}
?>

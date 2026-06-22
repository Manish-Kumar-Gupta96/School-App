<?php

if (!function_exists('validate_uploaded_file')) {
    /**
     * Validate uploaded file by extension, MIME type, and size.
     * Returns true if valid, or an error message string if invalid.
     */
    function validate_uploaded_file($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'], $max_size = 2097152, $allowed_mimes = []) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Invalid file upload parameters.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return 'File size exceeds allowed limit.';
                case UPLOAD_ERR_PARTIAL:
                    return 'File was only partially uploaded.';
                case UPLOAD_ERR_NO_FILE:
                    return 'No file was uploaded.';
                default:
                    return 'Unknown upload error.';
            }
        }

        // Validate size
        if ($file['size'] > $max_size) {
            return 'File size exceeds maximum allowed limit of ' . ($max_size / 1024 / 1024) . 'MB.';
        }

        $filename = $file['name'];
        $tmp_path = $file['tmp_name'];

        // Validate Extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            return 'Invalid file extension: .' . $ext;
        }

        // Validate MIME type
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = @finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
            }
        }
        
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmp_path);
        }

        if ($mime) {
            $default_mimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];

            // Resolve allowed mimes if empty
            if (empty($allowed_mimes)) {
                foreach ($allowed_extensions as $ae) {
                    if (isset($default_mimes[$ae])) {
                        $allowed_mimes[] = $default_mimes[$ae];
                    }
                }
            }

            if (!in_array($mime, $allowed_mimes)) {
                return 'Invalid file content type (MIME mismatch): ' . $mime;
            }
        }

        return true;
    }
}

<?php
declare(strict_types=1);

function inspect_social_image(array $file): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'uploaded' => false, 'ext' => '', 'error' => ''];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'تعذر رفع صورة المشاركة. حاول مرة أخرى.'];
    }

    if (!isset($file['size']) || (int)$file['size'] <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'صورة المشاركة يجب ألا تتجاوز 5 ميجابايت.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'ملف صورة المشاركة غير صالح.'];
    }

    $info = @getimagesize($tmp);
    if ($info === false || empty($info['mime'])) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'الملف المرفوع ليس صورة صالحة.'];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = strtolower((string)$info['mime']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'نوع الصورة غير مدعوم. استخدم JPG أو PNG أو WebP.'];
    }

    $width = (int)($info[0] ?? 0);
    $height = (int)($info[1] ?? 0);

    if ($width < 1 || $height < 1 || $width > 10000 || $height > 10000 || ($width * $height) > 30000000) {
        return ['ok' => false, 'uploaded' => false, 'ext' => '', 'error' => 'أبعاد صورة المشاركة غير مناسبة.'];
    }

    return [
        'ok' => true,
        'uploaded' => true,
        'ext' => $allowed[$mime],
        'error' => '',
    ];
}

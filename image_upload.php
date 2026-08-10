<?php

function cotswold_store_logo_upload(array $upload, string $destinationDirectory): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The logo upload did not complete.');
    }

    $size = (int)($upload['size'] ?? 0);
    if ($size < 1 || $size > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Logo files must be no larger than 5 MB.');
    }

    $temporaryPath = (string)($upload['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new InvalidArgumentException('The uploaded logo could not be verified.');
    }

    $imageInfo = @getimagesize($temporaryPath);
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
    if (!$imageInfo || !in_array((int)$imageInfo[2], $allowedTypes, true)) {
        throw new InvalidArgumentException('Please upload a JPEG, PNG, GIF or WebP image.');
    }

    $width = (int)$imageInfo[0];
    $height = (int)$imageInfo[1];
    if ($width < 1 || $height < 1 || $width > 5000 || $height > 5000 || ($width * $height) > 20_000_000) {
        throw new InvalidArgumentException('The logo dimensions are too large.');
    }

    $raw = file_get_contents($temporaryPath);
    $image = $raw === false ? false : @imagecreatefromstring($raw);
    if ($image === false) {
        throw new InvalidArgumentException('The logo image is damaged or unsupported.');
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        imagedestroy($image);
        throw new RuntimeException('The logo storage directory is unavailable.');
    }

    $useWebp = function_exists('imagewebp');
    $filename = bin2hex(random_bytes(16)) . ($useWebp ? '.webp' : '.png');
    $destination = rtrim($destinationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    $stored = $useWebp ? imagewebp($image, $destination, 88) : imagepng($image, $destination, 6);
    imagedestroy($image);

    if (!$stored) {
        throw new RuntimeException('The logo could not be stored.');
    }
    @chmod($destination, 0644);
    return $filename;
}

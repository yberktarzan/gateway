<?php

namespace App\Services\Listing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageService
{
    /**
     * Maximum file size in kilobytes (5MB)
     */
    private const MAX_FILE_SIZE = 5120;

    /**
     * Allowed image MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Allowed image extensions
     */
    private const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

    /**
     * Upload directory path
     */
    private const UPLOAD_PATH = 'listings';

    /**
     * Upload a cover image for listing.
     *
     * @return string Filename
     *
     * @throws ValidationException
     */
    public function uploadCoverImage(UploadedFile $file): string
    {
        $this->validateImageFile($file);

        $filename = $this->generateImageFilename('cover', $file->getClientOriginalExtension());

        $destinationPath = public_path(self::UPLOAD_PATH);

        // Ensure directory exists
        if (! is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Move file to destination
        if (! $file->move($destinationPath, $filename)) {
            throw ValidationException::withMessages([
                'cover_image' => [__('response.listing.cover_image_upload_failed')],
            ]);
        }

        return $filename;
    }

    /**
     * Upload multiple gallery images for listing.
     *
     * @param  array<UploadedFile>  $files
     * @return array<string> Array of filenames
     *
     * @throws ValidationException
     */
    public function uploadGalleryImages(array $files): array
    {
        $filenames = [];

        if (count($files) > 10) {
            throw ValidationException::withMessages([
                'images' => [__('response.listing.too_many_images')],
            ]);
        }

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $this->validateImageFile($file);

                $filename = $this->generateImageFilename('gallery-'.($index + 1), $file->getClientOriginalExtension());

                $destinationPath = public_path(self::UPLOAD_PATH);

                // Ensure directory exists
                if (! is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Move file to destination
                if ($file->move($destinationPath, $filename)) {
                    $filenames[] = $filename;
                } else {
                    // Clean up already uploaded files on failure
                    $this->cleanupFiles($filenames);
                    throw ValidationException::withMessages([
                        'images.'.$index => [__('response.listing.gallery_image_upload_failed')],
                    ]);
                }

            } catch (ValidationException $e) {
                // Clean up already uploaded files on validation failure
                $this->cleanupFiles($filenames);
                throw $e;
            }
        }

        return $filenames;
    }

    /**
     * Delete a single image file.
     */
    public function deleteImage(string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $filePath = public_path(self::UPLOAD_PATH.'/'.$filename);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Delete multiple image files.
     *
     * @param  array<string>  $filenames
     */
    public function deleteImages(array $filenames): bool
    {
        $success = true;

        foreach ($filenames as $filename) {
            if (! $this->deleteImage($filename)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Generate full URL for an image.
     */
    public function getImageUrl(string $filename): string
    {
        return url(self::UPLOAD_PATH.'/'.$filename);
    }

    /**
     * Generate full URLs for multiple images.
     *
     * @param  array<string>  $filenames
     * @return array<string>
     */
    public function getImageUrls(array $filenames): array
    {
        return array_map(function ($filename) {
            return $this->getImageUrl($filename);
        }, $filenames);
    }

    /**
     * Validate an uploaded image file.
     *
     * @throws ValidationException
     */
    private function validateImageFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'image' => [__('response.listing.invalid_image_file')],
            ]);
        }

        // Check file size
        if ($file->getSize() > (self::MAX_FILE_SIZE * 1024)) {
            throw ValidationException::withMessages([
                'image' => [__('response.listing.image_too_large', ['max' => self::MAX_FILE_SIZE / 1024 .'MB'])],
            ]);
        }

        // Check MIME type
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                'image' => [__('response.listing.invalid_image_type')],
            ]);
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw ValidationException::withMessages([
                'image' => [__('response.listing.invalid_image_extension')],
            ]);
        }

        // Additional image validation
        try {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw ValidationException::withMessages([
                    'image' => [__('response.listing.invalid_image_content')],
                ]);
            }

            // Check minimum dimensions (optional)
            if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
                throw ValidationException::withMessages([
                    'image' => [__('response.listing.image_too_small')],
                ]);
            }

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'image' => [__('response.listing.image_validation_failed')],
            ]);
        }
    }

    /**
     * Generate unique filename for image.
     */
    private function generateImageFilename(string $prefix, string $extension): string
    {
        $timestamp = time();
        $random = Str::random(8);

        return "listing-{$prefix}-{$timestamp}-{$random}.".strtolower($extension);
    }

    /**
     * Clean up uploaded files (used when upload process fails).
     *
     * @param  array<string>  $filenames
     */
    private function cleanupFiles(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $this->deleteImage($filename);
        }
    }

    /**
     * Get allowed image extensions.
     *
     * @return array<string>
     */
    public function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get allowed MIME types.
     *
     * @return array<string>
     */
    public function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Get maximum file size in KB.
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Company;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class LogoService
 *
 * Handles logo upload and management for companies.
 */
class LogoService
{
    private string $logoPath;

    public function __construct()
    {
        $this->logoPath = public_path('logos');
        $this->ensureDirectoryExists();
    }

    /**
     * Upload and save company logo.
     *
     * @param  UploadedFile  $file  Uploaded logo file
     * @param  string|null  $oldLogo  Old logo filename to delete
     * @return string Filename of saved logo
     *
     * @throws \Exception
     */
    public function uploadLogo(UploadedFile $file, ?string $oldLogo = null): string
    {
        // Validate file
        $this->validateLogoFile($file);

        // Delete old logo if exists
        if ($oldLogo) {
            $this->deleteLogo($oldLogo);
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = time().'_'.Str::random(10).'.'.$extension;

        // Move file to public/logos directory
        $file->move($this->logoPath, $filename);

        return $filename;
    }

    /**
     * Delete logo file.
     *
     * @param  string  $filename  Logo filename
     * @return bool True if deleted or file doesn't exist
     */
    public function deleteLogo(string $filename): bool
    {
        // Don't delete external URLs
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            return true;
        }

        $filePath = $this->logoPath.'/'.$filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Get logo URL.
     *
     * @param  string|null  $filename  Logo filename
     * @return string|null Logo URL or null if no logo
     */
    public function getLogoUrl(?string $filename): ?string
    {
        if (! $filename) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            return $filename;
        }

        // Return public URL for local logo files
        return url('logos/'.$filename);
    }

    /**
     * Validate uploaded logo file.
     *
     * @param  UploadedFile  $file  File to validate
     *
     * @throws \Exception
     */
    private function validateLogoFile(UploadedFile $file): void
    {
        // Check file size (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception(__('response.validation.logo_size_exceeded'));
        }

        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception(__('response.validation.logo_invalid_type'));
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions)) {
            throw new \Exception(__('response.validation.logo_invalid_extension'));
        }
    }

    /**
     * Ensure logos directory exists.
     */
    private function ensureDirectoryExists(): void
    {
        if (! File::exists($this->logoPath)) {
            File::makeDirectory($this->logoPath, 0755, true);
        }
    }

    /**
     * Get supported file types for validation.
     *
     * @return array Supported MIME types
     */
    public function getSupportedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    /**
     * Get supported file extensions for validation.
     *
     * @return array Supported file extensions
     */
    public function getSupportedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }

    /**
     * Get maximum file size in bytes.
     *
     * @return int Maximum file size (2MB)
     */
    public function getMaxFileSize(): int
    {
        return 2 * 1024 * 1024; // 2MB
    }
}

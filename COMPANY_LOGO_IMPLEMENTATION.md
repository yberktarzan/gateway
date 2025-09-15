# Company Logo Upload Feature Implementation

## Overview
This document describes the complete logo upload functionality implemented for the Company management system.

## Features Implemented

### 1. Logo Upload Service (`app/Services/Company/LogoService.php`)
- **File Upload**: Handles file upload with validation
- **File Validation**: 
  - File types: JPEG, JPG, PNG, GIF, WEBP
  - Maximum size: 2MB
  - Image validation
- **Storage**: Files stored in `public/logos/` directory (not Laravel storage)
- **Filename Generation**: Unique filenames using timestamp and random string
- **File Cleanup**: Automatic deletion of old logo files

### 2. Company Model Updates (`app/Models/Company.php`)
- **Logo URL Accessor**: `getLogoUrlAttribute()` generates full URL for logo files
- **Logo Deletion Method**: `deleteLogo()` removes logo file from filesystem
- **Integration**: Seamless integration with existing model functionality

### 3. Company Service Updates (`app/Services/Company/CompanyService.php`)
- **Create Method**: Handles logo upload during company creation
- **Update Method**: 
  - Replaces existing logo with new upload
  - Handles logo removal (when explicitly set to null)
  - Preserves existing logo when not provided
- **Delete Method**: Cleans up logo files when company is deleted
- **Logo Service Integration**: Dependency injection of LogoService

### 4. Form Request Validation Updates
#### CreateCompanyRequest (`app/Http/Requests/CreateCompanyRequest.php`)
- Logo field validation: `'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'`
- Updated error messages for file validation

#### UpdateCompanyRequest (`app/Http/Requests/UpdateCompanyRequest.php`)  
- Same logo validation as create request
- Updated error messages for file validation

### 5. Language Support
#### English (`resources/lang/en/response.php`)
```php
'logo_file' => 'The logo field must be a file.',
'logo_image' => 'The logo field must be an image.',
'logo_mimes' => 'The logo field must be a file of type: jpeg, jpg, png, gif, webp.',
'logo_max' => 'The logo field may not be greater than 2MB.',
```

#### Turkish (`resources/lang/tr/response.php`)
```php
'logo_file' => 'Logo alanı bir dosya olmalıdır.',
'logo_image' => 'Logo alanı bir resim olmalıdır.',  
'logo_mimes' => 'Logo alanı şu formatlarda olmalıdır: jpeg, jpg, png, gif, webp.',
'logo_max' => 'Logo alanı en fazla 2MB olmalıdır.',
```

### 6. Directory Structure
```
public/
├── logos/                 # Logo upload directory
│   ├── .gitkeep          # Ensures directory exists in git
│   └── (uploaded files)  # User uploaded logos
```

### 7. Git Configuration
- Added `public/logos/*` to `.gitignore`
- Exception for `.gitkeep` file: `!/public/logos/.gitkeep`
- Ensures uploaded files are not committed to repository

## API Usage

### Upload Logo During Company Creation
```bash
POST /api/companies
Content-Type: multipart/form-data
Authorization: Bearer {token}

Form Data:
- name: "Company Name"
- country_code: "US"
- description[en]: "English description"  
- description[tr]: "Turkish description"
- logo: (file upload)
- website: "https://example.com"
```

### Update Company Logo
```bash
PATCH /api/companies/{id}
Content-Type: multipart/form-data
Authorization: Bearer {token}

Form Data:
- logo: (file upload) # Replaces existing logo
# OR
- logo: null # Removes existing logo
```

### Response Format
```json
{
  "success": true,
  "message": "Company created successfully",
  "data": {
    "id": 1,
    "name": "Company Name",
    "logo": "company-logo-1640995200-abc123.jpg",
    "logo_url": "http://localhost:8000/logos/company-logo-1640995200-abc123.jpg",
    // ... other fields
  }
}
```

## File Upload Process

1. **Validation**: File validated for type, size, and image format
2. **Filename Generation**: Unique filename: `company-logo-{timestamp}-{random}.{ext}`
3. **Storage**: File moved to `public/logos/` directory
4. **Database**: Only filename stored in database
5. **URL Generation**: Full URL generated via accessor method
6. **Cleanup**: Old logos automatically deleted on update/delete

## Error Handling

### Validation Errors
- Invalid file type: Multi-language error messages
- File too large: "Logo may not be greater than 2MB"
- Invalid image: "Logo must be an image"

### Upload Errors  
- File system errors handled gracefully
- Translated error messages in English and Turkish
- Rollback on failure (logo not saved if company creation fails)

## Security Features

1. **File Type Validation**: Only image files accepted
2. **Size Limits**: Maximum 2MB file size
3. **Unique Filenames**: Prevents file overwrites and conflicts
4. **Public Directory**: Files served directly by web server (efficient)
5. **Clean Filenames**: Generated names prevent path traversal attacks

## Performance Considerations

1. **Direct Serving**: Images served directly by web server (no PHP processing)
2. **Efficient URLs**: Direct URLs to image files
3. **Cleanup Process**: Automatic removal of orphaned files
4. **Optimized Storage**: Public directory for better performance

## Testing

A comprehensive test script (`test_logo_upload.php`) is included that:
1. Registers test user
2. Creates company with logo upload
3. Updates company with new logo
4. Verifies logo URLs and functionality
5. Cleans up test data and files

## Dependencies

- **PHP GD Extension**: Required for image validation
- **File System Permissions**: `public/logos/` directory must be writable
- **Web Server**: Must serve static files from `public/` directory

## Migration Notes

- No database migration required (logo field already exists)
- Directory creation automatic via code
- Backward compatible with existing companies (logos optional)

---

*Implementation completed: Company logo upload functionality with comprehensive validation, multi-language support, and automatic file management.*
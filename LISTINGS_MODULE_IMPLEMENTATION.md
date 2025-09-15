# Listings Module Implementation

## Overview
This document describes the complete listings module implementation for the gateway system. The listings module allows users to create, manage, and search for various types of listings including product sales, service offerings, and more.

## Features Implemented

### 1. Database Schema (`2025_09_15_190330_create_listings_table.php`)
- **Multi-language Support**: title, description, slug as JSON fields
- **Image Management**: cover_image (single) and images (gallery array)
- **Location & Pricing**: location text field and decimal price field
- **Categorization**: listing_type (enum) and category_id (for future use)
- **User Association**: user_id foreign key with cascade delete
- **Country Filter**: country_code for regional filtering
- **Status Management**: is_active boolean for listing visibility
- **Performance Indexes**: Multiple indexes for efficient filtering and searching

### 2. Listing Model (`app/Models/Listing.php`)
#### Key Features:
- **Translatable Attributes**: `title`, `description`, `slug` support multiple languages
- **Image URL Accessors**: Automatic URL generation for cover image and gallery
- **Price Formatting**: Currency-aware price formatting based on country
- **File Management**: Methods for deleting cover and gallery images
- **Query Scopes**: Active, inactive, by type, by country, search, price range filtering
- **Relationships**: Belongs to User (owner)

#### JSON Field Structure:
```php
'title' => ['en' => 'English Title', 'tr' => 'Turkish Title']
'description' => ['en' => 'English Description', 'tr' => 'Turkish Description']  
'slug' => ['en' => 'english-slug', 'tr' => 'turkish-slug']
'images' => ['image1.jpg', 'image2.png', 'image3.webp']
```

### 3. Repository Layer (`app/Repositories/ListingRepository.php`)
#### CRUD Operations:
- `create()` - Create new listing
- `findById()` - Find by primary key
- `findBySlug()` - Find by slug in any language
- `update()` - Update existing listing
- `delete()` - Delete listing

#### Advanced Queries:
- `getPaginated()` - Paginated listings with filters
- `getActive()` - Active listings only
- `getByType()` - Filter by ListingType enum
- `getByCountry()` - Filter by country code
- `getUserListings()` - User's own listings
- `search()` - Full-text search in translatable fields
- `getRecent()` - Recent listings (configurable days)
- `getStatistics()` - Comprehensive statistics
- `getPopular()` - Popular listings

### 4. Image Service (`app/Services/Listing/ImageService.php`)
#### Comprehensive Image Management:
- **File Validation**: Size (5MB max), type (JPEG/PNG/GIF/WebP), dimensions (100x100 min)
- **Cover Images**: Single main image upload and management
- **Gallery Images**: Multiple images upload (max 10)
- **Unique Filenames**: Timestamp and random string generation
- **Public Storage**: Files stored in `public/listings/` directory
- **Automatic Cleanup**: Failed upload rollback and orphaned file removal
- **URL Generation**: Full URL generation for frontend consumption

#### Security Features:
- MIME type validation
- File extension validation
- Image content validation using `getimagesize()`
- Size and dimension restrictions

### 5. Business Logic (`app/Services/Listing/ListingService.php`)
#### Core Operations:
- **Create Listing**: Image upload handling, slug auto-generation, validation
- **Update Listing**: Image replacement, selective updates, ownership validation
- **Delete Listing**: Image cleanup, ownership validation
- **Retrieve Operations**: By ID, by slug with locale support
- **Search & Filter**: Advanced filtering and pagination
- **Status Management**: Toggle active/inactive status

#### Business Rules:
- Users can only modify their own listings
- Automatic slug generation from titles if not provided
- Image cleanup on update/delete operations
- Multi-language slug support

### 6. Form Request Validation
#### CreateListingRequest (`app/Http/Requests/CreateListingRequest.php`)
```php
'title' => ['required', 'array'],
'title.en' => ['required', 'string', 'max:255'],
'description' => ['required', 'array'],
'description.en' => ['required', 'string', 'max:10000'],
'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
'images' => ['nullable', 'array', 'max:10'],
'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
'location' => ['required', 'string', 'max:255'],
'price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
'listing_type' => ['required', 'string', Rule::enum(ListingType::class)],
```

#### UpdateListingRequest (`app/Http/Requests/UpdateListingRequest.php`)
- Similar validation with 'sometimes' rules for optional updates
- Partial update support with required_with rules

### 7. RESTful API Controller (`app/Http/Controllers/ListingController.php`)
#### Public Endpoints (No Authentication):
- `GET /api/listings` - All listings with filtering
- `GET /api/listings/active` - Active listings only
- `GET /api/listings/type/{type}` - Listings by type
- `GET /api/listings/search?q={query}` - Search listings
- `GET /api/listings/statistics` - Statistics overview
- `GET /api/listings/{id}` - Single listing by ID
- `GET /api/listings/slug/{slug}` - Single listing by slug

#### Protected Endpoints (Require Authentication):
- `POST /api/listings` - Create new listing
- `PATCH /api/listings/{id}` - Update listing
- `DELETE /api/listings/{id}` - Delete listing
- `GET /api/listings/my` - User's own listings
- `PATCH /api/listings/{id}/toggle-status` - Toggle active status

### 8. Listing Types (ListingType Enum)
```php
enum ListingType: string {
    case PRODUCT_SELLER = 'product_seller';    // Selling products
    case PRODUCT_BUYER = 'product_buyer';      // Buying products  
    case SERVICE_GIVER = 'service_giver';      // Providing services
    case SERVICE_TAKER = 'service_taker';      // Seeking services
    case OTHER = 'other';                      // Other types
}
```

### 9. Multi-Language Support
#### Translation Files:
- **English**: `resources/lang/en/listing_types.php`
- **Turkish**: `resources/lang/tr/listing_types.php`
- **Validation Messages**: Comprehensive English and Turkish validation messages
- **Response Messages**: Success/error messages in both languages
- **Attributes**: Field name translations for error messages

#### Language Structure:
```php
// Listing Types
'product_seller' => 'Product Seller' / 'Ürün Satıcısı'
'service_giver' => 'Service Provider' / 'Hizmet Sağlayıcısı'

// Response Messages  
'created' => 'Listing created successfully' / 'İlan başarıyla oluşturuldu'
'updated' => 'Listing updated successfully' / 'İlan başarıyla güncellendi'
```

## API Usage Examples

### Create Listing with Images
```bash
POST /api/listings
Content-Type: multipart/form-data
Authorization: Bearer {token}

Form Data:
- title[en]: "Gaming Laptop for Sale"
- title[tr]: "Oyun Laptopı Satılık" 
- description[en]: "High-end gaming laptop in excellent condition"
- description[tr]: "Mükemmel durumda üst düzey oyun laptopı"
- cover_image: (file upload)
- images[]: (file upload)
- images[]: (file upload)
- location: "Istanbul, Turkey"
- price: 1500.00
- listing_type: "product_seller"
- country_code: "TR"
```

### Search Listings
```bash
GET /api/listings/search?q=laptop&listing_type=product_seller&country_code=TR&min_price=1000&max_price=2000&per_page=20
```

### Response Format
```json
{
  "success": true,
  "message": "Listing created successfully",
  "data": {
    "id": 1,
    "title": {
      "en": "Gaming Laptop for Sale",
      "tr": "Oyun Laptopı Satılık"
    },
    "description": {
      "en": "High-end gaming laptop in excellent condition",
      "tr": "Mükemmel durumda üst düzey oyun laptopı"
    },
    "cover_image": "listing-cover-1640995200-abc123.jpg",
    "cover_image_url": "http://localhost:8000/listings/listing-cover-1640995200-abc123.jpg",
    "images": [
      "listing-gallery-1-1640995201-def456.jpg",
      "listing-gallery-2-1640995202-ghi789.png"
    ],
    "image_urls": [
      "http://localhost:8000/listings/listing-gallery-1-1640995201-def456.jpg",
      "http://localhost:8000/listings/listing-gallery-2-1640995202-ghi789.png"
    ],
    "slug": {
      "en": "gaming-laptop-for-sale",
      "tr": "oyun-laptopu-satilik"
    },
    "location": "Istanbul, Turkey",
    "price": "1500.00",
    "formatted_price": "1,500.00 TL",
    "listing_type": "product_seller",
    "country_code": "TR",
    "is_active": true,
    "created_at": "2025-09-15T19:03:30.000000Z",
    "updated_at": "2025-09-15T19:03:30.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

## Directory Structure
```
app/
├── Models/
│   └── Listing.php                    # Listing model with relationships
├── Repositories/
│   └── ListingRepository.php          # Data access layer
├── Services/Listing/
│   ├── ListingService.php             # Business logic
│   └── ImageService.php               # Image management
├── Http/
│   ├── Controllers/
│   │   └── ListingController.php      # API endpoints
│   └── Requests/
│       ├── CreateListingRequest.php   # Create validation
│       └── UpdateListingRequest.php   # Update validation
└── Enums/
    └── ListingType.php                 # Listing type enumeration

database/migrations/
└── 2025_09_15_190330_create_listings_table.php

resources/lang/
├── en/
│   ├── listing_types.php              # English listing types
│   └── response.php                   # English messages
└── tr/
    ├── listing_types.php              # Turkish listing types
    └── response.php                   # Turkish messages

public/
└── listings/                          # Image storage directory
    └── .gitkeep                       # Ensure directory exists

routes/
└── api.php                            # API route definitions
```

## Database Schema
```sql
CREATE TABLE listings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title JSON NOT NULL,                -- {"en": "Title", "tr": "Başlık"}
    description JSON NOT NULL,          -- {"en": "Description", "tr": "Açıklama"}
    cover_image VARCHAR(255) NULL,      -- Single cover image filename
    images JSON NULL,                   -- ["image1.jpg", "image2.png"]
    slug JSON NOT NULL,                 -- {"en": "slug", "tr": "slug"}
    location VARCHAR(255) NOT NULL,     -- Physical location
    price DECIMAL(12,2) NULL,           -- Listing price
    listing_type VARCHAR(255) NOT NULL, -- ListingType enum value
    user_id BIGINT UNSIGNED NOT NULL,   -- Owner (foreign key)
    country_code CHAR(2) NOT NULL,      -- ISO country code
    category_id BIGINT UNSIGNED NULL,   -- Future category system
    is_active BOOLEAN DEFAULT 1,        -- Active status
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type_active (listing_type, is_active),
    INDEX idx_country_active (country_code, is_active),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_created (created_at)
);
```

## Performance Features

1. **Database Indexing**: Strategic indexes on commonly filtered fields
2. **Pagination**: Built-in pagination for large result sets
3. **Eager Loading**: User relationship pre-loaded to avoid N+1 queries
4. **Direct Image Serving**: Public directory serving for optimal image delivery
5. **Query Optimization**: Scoped queries and efficient filtering

## Security Features

1. **Image Validation**: Comprehensive file type, size, and content validation
2. **Ownership Authorization**: Users can only modify their own listings
3. **Input Sanitization**: Form request validation for all inputs
4. **File Security**: Unique filename generation prevents conflicts
5. **Path Security**: Public directory prevents path traversal attacks

## Testing & Integration

- Ready for automated testing with repositories and services
- Comprehensive error handling with translated messages
- API documentation compatible with tools like Postman/Scribe
- Frontend-ready with direct image URLs and structured responses

---

*Implementation completed: Full-featured listings module with multi-language support, comprehensive image management, advanced filtering, and RESTful API endpoints.*
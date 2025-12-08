# Requirements Document

## Introduction

Dokumen ini mendefinisikan requirements untuk meningkatkan kualitas dokumentasi API yang dihasilkan oleh Scramble. Berdasarkan audit implementasi saat ini, ditemukan beberapa area yang perlu ditingkatkan: response schema documentation, PHPDoc annotations yang konsisten, tag grouping untuk navigasi, dan contoh response yang actual. Tujuannya adalah menghasilkan dokumentasi API yang lengkap, akurat, dan mudah digunakan oleh API consumer.

## Glossary

- **Scramble**: Library Laravel untuk auto-generate OpenAPI specification dari kode PHP
- **OpenAPI**: Standar spesifikasi untuk mendeskripsikan RESTful APIs
- **Scalar**: UI modern untuk menampilkan dokumentasi OpenAPI
- **API_Documentation_System**: Sistem yang menghasilkan dan menampilkan dokumentasi API secara otomatis
- **API Resource**: Laravel class yang transform model ke JSON response dengan schema yang terdefinisi
- **PHPDoc**: Dokumentasi dalam kode PHP menggunakan format docblock
- **Tag Group**: Pengelompokan tags untuk navigasi yang lebih baik di dokumentasi
- **Response Schema**: Struktur data yang dikembalikan oleh endpoint dalam format JSON Schema

## Requirements

### Requirement 1: Response Schema Documentation

**User Story:** As an API consumer, I want to see clear response schemas for each endpoint, so that I can understand the exact structure of data returned by the API.

#### Acceptance Criteria

1. WHEN an endpoint returns a model or collection THEN the API_Documentation_System SHALL document the response schema with all properties and their types
2. WHEN an endpoint returns paginated data THEN the API_Documentation_System SHALL document the pagination wrapper structure including meta and links
3. WHEN an endpoint returns nested relationships THEN the API_Documentation_System SHALL document the nested object structures
4. WHEN an endpoint has multiple response codes THEN the API_Documentation_System SHALL document each response code with its corresponding schema

### Requirement 2: PHPDoc Annotations Standardization

**User Story:** As a developer, I want consistent PHPDoc annotations across all controllers, so that Scramble can generate accurate and complete documentation.

#### Acceptance Criteria

1. WHEN a controller method handles an API endpoint THEN the method SHALL have @summary annotation describing the endpoint purpose in Bahasa Indonesia
2. WHEN a controller method has complex logic or multiple scenarios THEN the method SHALL have @description annotation with detailed explanation
3. WHEN a controller method accepts query parameters not from Form Request THEN the method SHALL document parameters using appropriate PHPDoc tags
4. WHEN a controller method can return error responses THEN the method SHALL document error scenarios using @response annotations with status codes 400, 401, 403, 404, 422, 500

### Requirement 3: Tag Groups Configuration

**User Story:** As an API consumer, I want endpoints grouped logically in the documentation sidebar, so that I can navigate and find related endpoints easily.

#### Acceptance Criteria

1. WHEN viewing the API documentation THEN the API_Documentation_System SHALL display tags grouped by functional domain (Authentication, User Management, Course Management, Learning, etc.)
2. WHEN a tag group contains multiple tags THEN the API_Documentation_System SHALL display them in a logical order within the group
3. WHEN configuring tag groups THEN the configuration SHALL be maintainable in a single location (AppServiceProvider or config file)

### Requirement 4: Enum Documentation Enhancement

**User Story:** As an API consumer, I want to see all possible enum values with their descriptions, so that I can understand valid values for enum fields.

#### Acceptance Criteria

1. WHEN an endpoint accepts or returns an enum field THEN the API_Documentation_System SHALL list all possible enum values
2. WHEN an enum case has a description or label THEN the API_Documentation_System SHALL display the description alongside the value
3. WHEN documenting enum fields THEN the API_Documentation_System SHALL use consistent formatting across all endpoints

### Requirement 5: Authentication Documentation

**User Story:** As an API consumer, I want to clearly see which endpoints require authentication and which are public, so that I can implement proper authentication flow.

#### Acceptance Criteria

1. WHEN an endpoint does not require authentication THEN the endpoint documentation SHALL be marked with @unauthenticated annotation
2. WHEN an endpoint requires specific roles THEN the endpoint documentation SHALL indicate the required roles in the description
3. WHEN an endpoint uses JWT Bearer authentication THEN the API_Documentation_System SHALL show the security requirement in the endpoint details

### Requirement 6: Example Responses

**User Story:** As an API consumer, I want to see realistic example responses for each endpoint, so that I can understand the actual data format returned.

#### Acceptance Criteria

1. WHEN documenting a success response THEN the documentation SHALL include a realistic example with actual field values (not generic placeholders)
2. WHEN documenting an error response THEN the documentation SHALL include the standard error response format with appropriate error messages
3. WHEN an endpoint returns different data based on user role THEN the documentation SHALL provide examples for each scenario where applicable

### Requirement 7: Server Configuration

**User Story:** As an API consumer, I want to see different server environments in the documentation, so that I can test against the appropriate environment.

#### Acceptance Criteria

1. WHEN viewing the API documentation THEN the API_Documentation_System SHALL display at least two server options (Local Development, Production)
2. WHEN selecting a server THEN the Try It feature SHALL use the selected server URL for requests
3. WHEN configuring servers THEN the configuration SHALL use environment variables for flexibility

### Requirement 8: Query Builder Documentation (Filters, Sorts, Pagination, Search, Include)

**User Story:** As an API consumer, I want to see all available query parameters for filtering, sorting, pagination, search, and relationship includes, so that I can build efficient API queries.

#### Acceptance Criteria

1. WHEN an endpoint supports filtering THEN the documentation SHALL list all allowed filter fields using @allowedFilters annotation
2. WHEN an endpoint supports sorting THEN the documentation SHALL list all allowed sort fields using @allowedSorts annotation
3. WHEN an endpoint supports pagination THEN the documentation SHALL document page, per_page query parameters with default values
4. WHEN an endpoint supports search THEN the documentation SHALL document the search parameter and searchable fields
5. WHEN an endpoint supports relationship includes THEN the documentation SHALL list all allowed includes using @allowedIncludes annotation
6. WHEN a filter field accepts enum values THEN the documentation SHALL list valid values using @filterEnum annotation


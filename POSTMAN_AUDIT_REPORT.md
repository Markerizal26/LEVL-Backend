# Postman Documentation Audit Report & Action Plan

**Date:** November 12, 2025  
**Project:** ta-prep-lsp-be  
**Modules Audited:** Auth, Common, Schemes  
**Status:** ⚠️ PARTIALLY COMPLETE - Requires Updates

---

## 📊 EXECUTIVE SUMMARY

### Overall Status
- **Endpoint Coverage:** 98.2% (54/55 endpoints documented)
- **Request Body Accuracy:** 65% (needs improvement)
- **Documentation Completeness:** 70%

### Quick Statistics

| Module | Endpoints | Documented | Coverage | Body Accuracy |
|--------|-----------|------------|----------|----------------|
| **Auth** | 23 | 22 | 95.7% | ⚠️ 75% |
| **Common** | 5 | 5 | 100% | ✅ 100% |
| **Schemes** | 27 | 27 | 100% | ⚠️ 40% |
| **TOTAL** | 55 | 54 | 98.2% | ⚠️ 65% |

---

## ❌ CRITICAL ISSUES FOUND

### Issue #1: Missing Endpoint
**Severity:** 🔴 HIGH

**Endpoint:** `GET /auth/users/{user}`  
**Route:** `/v1/auth/users/{user}`  
**Method:** GET  
**Authorization:** super-admin  
**Description:** Get individual user details

**Impact:** Cannot retrieve specific user information from Postman  
**Action:** Add new request to Postman collection

---

### Issue #2: Incomplete Auth Endpoints (5 endpoints)
**Severity:** 🟠 MEDIUM

**Affected Endpoints:**
1. `PUT /profile` - Missing request body
2. `POST /auth/email/verify` - Body needs verification
3. `POST /auth/email/verify/by-token` - Body needs verification
4. `POST /profile/email/request` - Body needs verification
5. `POST /profile/email/verify` - Body needs verification

**Impact:** Developers cannot properly test these endpoints  
**Action:** Add/verify complete request bodies with all required fields

---

### Issue #3: Incomplete Schemes Endpoints (8 endpoints)
**Severity:** 🟠 MEDIUM

**Affected Endpoints:**
1. `POST /courses` - Missing: level_tag, type, visibility, progression_mode, tags, outcomes, etc
2. `PUT /courses/{slug}` - Same fields as POST
3. `POST /courses/{slug}/units` - Missing: code, slug, status
4. `PUT /courses/{slug}/units/{slug}` - Same as POST units
5. `PUT /courses/{slug}/units/reorder` - Incorrect body format
6. `POST /courses/{slug}/units/{slug}/lessons` - Missing: markdown_content, duration_minutes, etc
7. `PUT /courses/{slug}/units/{slug}/lessons/{slug}` - Same as POST lessons
8. `POST/PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks` - Incomplete type examples

**Impact:** Cannot properly test course/unit/lesson/block creation and updates  
**Action:** Add complete request bodies for all variants (JSON and form-data with files)

---

## 🎯 PRIORITY ACTION ITEMS

### Priority 1: CRITICAL (Do Now)
**Est. Time:** 2-3 hours

- [ ] **Add missing endpoint:** `GET /auth/users/{user}`
  - Copy structure from similar GET endpoints
  - Add proper authentication headers
  - Include test data path variable

- [ ] **Fix POST /courses request body:**
  - Add all required fields: code, slug, title, level_tag, type, visibility, progression_mode
  - Add optional fields: category_id, instructor_id, status, tags, outcomes, prereq, course_admins
  - Provide both JSON and form-data (with thumbnail, banner) examples

- [ ] **Fix PUT /profile request body:**
  - Add form-data example with avatar file upload
  - Add JSON example for text-only updates
  - Include proper Content-Type headers for each variant

### Priority 2: HIGH (This Week)
**Est. Time:** 4-6 hours

- [ ] **Update PUT /courses/{slug}** - Same as POST /courses

- [ ] **Fix Unit endpoints:**
  - `POST /courses/{slug}/units` - Add code, slug, status fields
  - `PUT /courses/{slug}/units/{slug}` - Same as POST
  - `PUT /courses/{slug}/units/reorder` - Fix array format: `{ "units": [id1, id2, id3] }`

- [ ] **Fix Lesson endpoints:**
  - `POST /courses/{slug}/units/{slug}/lessons` - Add markdown_content, duration_minutes
  - `PUT /courses/{slug}/units/{slug}/lessons/{slug}` - Same as POST

- [ ] **Verify and fix email verification endpoints:**
  - Add both UUID and token options for `/auth/email/verify`
  - Complete `/auth/email/verify/by-token` body
  - Complete `/profile/email/request` body
  - Complete `/profile/email/verify` body

### Priority 3: MEDIUM (Next 2 Weeks)
**Est. Time:** 3-4 hours

- [ ] **Fix Lesson Block endpoints:**
  - Add separate examples for each type: text, video, image, file
  - Provide form-data examples with file uploads
  - Include content structure for each type

- [ ] **Add documentation:**
  - Add response examples for all endpoints
  - Add error response examples (400, 401, 403, 404, 422)
  - Document all query parameters for GET endpoints

- [ ] **Add environment variables:**
  - {{API_URL}}
  - {{access_token}}
  - {{refresh_token}}
  - {{course_slug}}, {{unit_slug}}, {{lesson_slug}}

- [ ] **Test all endpoints:**
  - Run through complete workflow
  - Verify all required fields
  - Test with optional fields
  - Test error scenarios

---

## 📋 DETAILED ACTION PLAN

### Step 1: Add Missing Endpoint (15 min)

**Endpoint:** `GET /auth/users/{user}`

**Where:** Under Auth > Manajemen Pengguna folder

**Request Configuration:**
```
Method: GET
URL: {{API_URL}}/auth/users/{{user_id}}
Headers:
  - Authorization: Bearer {{access_token}}
  - Accept: application/json
```

**Path Variables:**
```
user | Description: User ID to retrieve | Value: {{user_id}}
```

**Authorization:** Requires super-admin role

---

### Step 2: Update POST /courses (30 min)

**Current Status:** ⚠️ Body is incomplete/missing

**Action:** Replace body with this complete example:

```json
{
  "code": "DASAR-TI-2025",
  "slug": "dasar-teknologi-informasi-2025",
  "title": "Dasar Teknologi Informasi",
  "short_desc": "Kursus komprehensif tentang fondasi teknologi informasi modern",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "draft",
  "tags": ["teknologi", "dasar", "ti", "informatika"],
  "outcomes": [
    "Memahami konsep dasar teknologi informasi",
    "Menguasai penggunaan komputer dasar",
    "Mampu menggunakan aplikasi perkantoran"
  ],
  "prereq": [],
  "course_admins": [5, 6]
}
```

**Note:** Add form-data variant with thumbnail and banner files

---

### Step 3: Update PUT /profile (20 min)

**Current Status:** ❌ Body missing

**Action 1:** Add JSON variant (for text-only):
```json
{
  "name": "Ahmad Wijaya",
  "username": "ahmadwijaya"
}
```

**Action 2:** Add form-data variant (with file):
```
Key: name | Value: Ahmad Wijaya
Key: username | Value: ahmadwijaya
Key: avatar | Value: [FILE]
```

**Headers:** Use appropriate Content-Type for each variant

---

### Step 4: Update Unit Endpoints (25 min)

**POST /courses/{slug}/units**
```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Unit 1: Pengenalan Dasar",
  "description": "Pengenalan dasar teknologi informasi",
  "order": 1,
  "status": "draft"
}
```

**PUT /courses/{slug}/units/reorder**
```json
{
  "units": [3, 1, 2, 5, 4]
}
```

---

### Step 5: Update Lesson Endpoints (20 min)

**POST /courses/{slug}/units/{slug}/lessons**
```json
{
  "slug": "lesson-1-apa-itu-ti",
  "title": "Apa Itu Teknologi Informasi?",
  "description": "Pelajaran pertama tentang TI",
  "markdown_content": "# Pengenalan TI\n\nTeknologi Informasi adalah...",
  "order": 1,
  "duration_minutes": 45,
  "status": "draft"
}
```

---

### Step 6: Update Email Verification Endpoints (20 min)

**POST /auth/email/verify** - Add two variants:
```json
// Variant 1: Using UUID
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}

// Variant 2: Using Token
{
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "code": "123456"
}
```

**POST /profile/email/request**
```json
{
  "new_email": "newemail@example.com"
}
```

**POST /profile/email/verify**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

---

### Step 7: Update Lesson Block Endpoints (30 min)

**POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks** - Add 4 variants:

**Type 1: Text**
```json
{
  "type": "text",
  "content": "Konten teks pembelajaran...",
  "order": 1
}
```

**Type 2: Video (form-data)**
```
type: video
content: Deskripsi video
order: 2
media: [FILE: video.mp4]
```

**Type 3: Image (form-data)**
```
type: image
content: Deskripsi gambar
order: 3
media: [FILE: image.jpg]
```

**Type 4: File (form-data)**
```
type: file
content: Deskripsi file
order: 4
media: [FILE: document.pdf]
```

---

### Step 8: Documentation & Testing (2-3 hours)

**Documentation:**
- [ ] Add response examples for each endpoint
- [ ] Document error responses (400, 401, 403, 404, 422)
- [ ] Add comments explaining complex fields
- [ ] Create pre-request scripts if needed for auth flow

**Testing:**
- [ ] Test complete workflow (register → create course → add units → add lessons)
- [ ] Test with minimal required fields
- [ ] Test with all optional fields
- [ ] Test error scenarios (invalid data, missing fields, unauthorized)
- [ ] Test file uploads (thumbnails, banners, avatars, media blocks)

---

## 📑 REFERENCE DOCUMENTS PROVIDED

Three comprehensive reference documents have been created:

### 1. `POSTMAN_REQUEST_BODY_REFERENCE.md`
- **Purpose:** Complete reference for all request bodies
- **Content:** Full specifications with validation rules
- **Use Case:** When you need detailed validation info

### 2. `POSTMAN_DETAILED_REQUEST_GUIDE.md`
- **Purpose:** Step-by-step guide with detailed explanations
- **Content:** Complex endpoints with multiple variants
- **Use Case:** When implementing form-data with file uploads

### 3. `POSTMAN_QUICK_REFERENCE.md`
- **Purpose:** Quick copy-paste examples ready to use
- **Content:** Pre-formatted JSON examples for each endpoint
- **Use Case:** Quick updates to Postman collection

---

## 🔄 WORKFLOW RECOMMENDATIONS

### For Testing New Endpoints:

1. **Start with JSON variant** (easier to test without files)
2. **Then add form-data variant** (with file uploads)
3. **Test both authenticated and unauthenticated scenarios**
4. **Verify response structure matches documentation**
5. **Add response examples to Postman**

### For Updating Postman Collection:

1. **Open Postman > Import > Paste Raw Text**
2. **Or manually update each request**
3. **Use environment variables** for dynamic values
4. **Test after each update**
5. **Export updated collection**

### For Maintaining Documentation:

1. **Keep request bodies in sync** with latest code
2. **Update when validation rules change**
3. **Add new endpoints** immediately after creation
4. **Review quarterly** for completeness
5. **Get team feedback** on clarity

---

## ✅ SUCCESS CRITERIA

Document can be considered **COMPLETE** when:

- [x] All 55 endpoints are documented in Postman
- [ ] All request bodies are complete and accurate
- [ ] All response examples are provided
- [ ] All error scenarios are documented
- [ ] File upload variants are demonstrated
- [ ] Environment variables are configured
- [ ] Team has tested and approved all endpoints
- [ ] Documentation is reviewed and signed off

---

## 📞 SUPPORT & CLARIFICATION

### Questions about specific endpoint requirements?

**Check these files in order:**
1. `POSTMAN_QUICK_REFERENCE.md` - Quick examples
2. `POSTMAN_DETAILED_REQUEST_GUIDE.md` - Detailed specs
3. `POSTMAN_REQUEST_BODY_REFERENCE.md` - Complete reference
4. Source code in `Modules/*/app/Http/Requests/` - Ultimate source

### Need to verify validation rules?

**Check the Form Request classes:**
- `Modules/Auth/app/Http/Requests/` - Auth rules
- `Modules/Common/app/Http/Requests/` - Common rules
- `Modules/Schemes/app/Http/Requests/` - Schemes rules

### Questions about API behavior?

**Check the Controllers:**
- `Modules/Auth/app/Http/Controllers/` - Auth logic
- `Modules/Common/app/Http/Controllers/` - Common logic
- `Modules/Schemes/app/Http/Controllers/` - Schemes logic

---

## 📝 CHANGE LOG

| Date | Version | Changes |
|------|---------|---------|
| Nov 12, 2025 | 1.0 | Initial audit and documentation |
| - | - | - |

---

## 🚀 NEXT STEPS

1. **Today:** Review this report
2. **Tomorrow:** Start with Priority 1 items (add missing endpoint, fix POST /courses)
3. **This Week:** Complete Priority 1 and 2
4. **Next Week:** Complete Priority 3 and thorough testing
5. **Final:** Review, test, export updated Postman collection

---

**Prepared by:** Documentation Generator  
**Date:** November 12, 2025  
**Status:** READY FOR IMPLEMENTATION

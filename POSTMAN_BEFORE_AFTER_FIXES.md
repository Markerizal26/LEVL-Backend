# Postman Issues & Solutions - Side-by-Side Comparison

**Purpose:** Show what's wrong and how to fix it  
**Format:** Before/After with exact changes needed

---

## 1️⃣ CRITICAL ISSUE: Missing Endpoint

### ❌ BEFORE (Current State in Postman)
```
Missing: GET /auth/users/{user}
Not available in Postman collection
```

### ✅ AFTER (What You Need to Add)
```
Endpoint: GET /auth/users/{user}
Method: GET
URL: {{API_URL}}/auth/users/{{user_id}}

Headers:
  Authorization: Bearer {{access_token}}
  Accept: application/json

Path Variables:
  user | User ID to retrieve | Value: {{user_id}}

Authorization:
  Requires super-admin role

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Ahmad Wijaya",
    "username": "ahmadwijaya",
    "email": "ahmad@example.com",
    "status": "active",
    ...
  },
  "message": "User berhasil diambil."
}
```

### 🔧 HOW TO FIX
1. Open Postman collection
2. Navigate to: Auth > Manajemen Pengguna
3. Create new GET request
4. Name it: "GET User Detail"
5. Add URL: {{API_URL}}/auth/users/{{user_id}}
6. Add Headers & Auth
7. Save

---

## 2️⃣ INCOMPLETE: PUT /profile (Auth Module)

### ❌ BEFORE (Current State in Postman)
```
Body: Empty or missing
No examples provided
Cannot update profile with avatar
```

### ✅ AFTER (What You Need to Add)

#### Option A: JSON Only (Text Fields)
```json
{
  "name": "Ahmad Wijaya",
  "username": "ahmadwijaya"
}
```

#### Option B: Form-Data (With Avatar File)
```
Form-Data:
  name: Ahmad Wijaya (text)
  username: ahmadwijaya (text)
  avatar: [SELECT FILE] (file - jpg/jpeg/png/webp, max 2MB)
```

### Headers
```
Authorization: Bearer {{access_token}}
Content-Type: application/json (for JSON)
             or multipart/form-data (for form-data)
```

### 🔧 HOW TO FIX
1. Open PUT /profile request in Postman
2. **For text-only variant:**
   - Set Body type: raw (JSON)
   - Add the JSON example above
3. **For file upload variant:**
   - Set Body type: form-data
   - Add name, username as text fields
   - Add avatar as file field
4. Save both variants

---

## 3️⃣ INCOMPLETE: POST /courses (Schemes Module)

### ❌ BEFORE (Current State in Postman)
```json
{
  "code": "DASAR-TI-001",
  "title": "Dasar Teknologi Informasi"
  // MISSING: level_tag, type, visibility, progression_mode,
  //          tags, outcomes, course_admins, etc.
}
```

### ✅ AFTER (Complete Body)
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

### 🔧 HOW TO FIX
1. Open POST /courses request
2. Copy the complete JSON body above
3. Paste into Body section (raw, JSON)
4. Save request
5. **Also add form-data variant** with thumbnail and banner files

---

## 4️⃣ INCOMPLETE: POST /courses/{slug}/units

### ❌ BEFORE (Current State)
```json
{
  "title": "Unit 1: Pengenalan Dasar"
  // MISSING: code, slug, status, description, order
}
```

### ✅ AFTER (Complete Body)
```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Unit 1: Pengenalan Dasar Teknologi Informasi",
  "description": "Unit ini memperkenalkan konsep fundamental dan sejarah teknologi informasi",
  "order": 1,
  "status": "draft"
}
```

**Required Fields:**
- `code`: string, max 50, unique
- `title`: string, max 255
- `slug`: string, max 100 (optional, auto-generated if not provided)
- `status`: draft | published (optional)

### 🔧 HOW TO FIX
1. Open POST /courses/{slug}/units request
2. Replace body with complete example above
3. Update field labels to match actual requirements
4. Save

---

## 5️⃣ INCOMPLETE: PUT /courses/{slug}/units/reorder

### ❌ BEFORE (Current State)
```
Wrong format or missing example
```

### ✅ AFTER (Correct Format)
```json
{
  "units": [3, 1, 2, 5, 4]
}
```

**Explanation:** Array of unit IDs in desired order (position 1 to N)

**Example with actual IDs:**
```json
{
  "units": [10, 8, 9, 7]
}
```

### Validation Rules
- `units`: required, array
- `units.*`: required, integer, must exist in units table
- All unit IDs must belong to same course

### 🔧 HOW TO FIX
1. Open PUT /courses/{slug}/units/reorder request
2. Set Body type: raw (JSON)
3. Add the array example above
4. Save

---

## 6️⃣ INCOMPLETE: POST /courses/{slug}/units/{slug}/lessons

### ❌ BEFORE (Current State)
```json
{
  "title": "Lesson 1"
  // MISSING: markdown_content, duration_minutes, slug, status, order, description
}
```

### ✅ AFTER (Complete Body)
```json
{
  "slug": "lesson-1-apa-itu-ti",
  "title": "Apa Itu Teknologi Informasi?",
  "description": "Pelajaran pertama tentang definisi dan ruang lingkup TI",
  "markdown_content": "# Apa Itu Teknologi Informasi?\n\nTeknologi Informasi adalah penggunaan komputer untuk mengelola informasi...\n\n## Komponen Utama\n1. Hardware\n2. Software\n3. Network\n4. Data",
  "order": 1,
  "duration_minutes": 45,
  "status": "draft"
}
```

### 🔧 HOW TO FIX
1. Open POST /courses/{slug}/units/{slug}/lessons request
2. Replace with complete example above
3. Update Content Type to: application/json
4. Save

---

## 7️⃣ INCOMPLETE: POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks

### ❌ BEFORE (Current State)
```
Missing examples for different block types
No form-data examples for file uploads
```

### ✅ AFTER (All 4 Types Documented)

#### TYPE 1: Text Block (JSON)
```json
{
  "type": "text",
  "content": "Ini adalah konten teks pembelajaran yang dapat berisi penjelasan detail tentang topik.",
  "order": 1
}
```

#### TYPE 2: Video Block (Form-Data with File)
```
name: type | value: video
name: content | value: Deskripsi video pembelajaran
name: order | value: 2
name: media | value: [FILE: video.mp4]
```

#### TYPE 3: Image Block (Form-Data with File)
```
name: type | value: image
name: content | value: Deskripsi diagram atau gambar
name: order | value: 3
name: media | value: [FILE: image.jpg]
```

#### TYPE 4: File Block (Form-Data with File)
```
name: type | value: file
name: content | value: Deskripsi file yang dapat diunduh
name: order | value: 4
name: media | value: [FILE: document.pdf]
```

### 🔧 HOW TO FIX
1. Open POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks request
2. Create 4 separate request variants in Postman
3. For each type: Update Body with correct type and example
4. For file types: Change Body to form-data
5. Save all variants

---

## 8️⃣ INCOMPLETE: Email Verification Endpoints (5 endpoints)

### Issue 1: POST /auth/email/verify

#### ❌ BEFORE
```
Body unclear - what are the required fields?
```

#### ✅ AFTER (Add Both Variants)

**Variant 1: Using UUID**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

**Variant 2: Using Token**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "code": "123456"
}
```

### Issue 2: POST /profile/email/request

#### ❌ BEFORE
```
Missing body specification
```

#### ✅ AFTER
```json
{
  "new_email": "newemail@example.com"
}
```

### Issue 3: POST /profile/email/verify

#### ❌ BEFORE
```
Unclear what fields are needed
```

#### ✅ AFTER
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

### 🔧 HOW TO FIX
1. Update each email endpoint with correct body examples
2. Provide clear field descriptions
3. Explain where UUID and code come from
4. Save

---

## 📊 SUMMARY TABLE: What's Wrong vs What's Right

| Endpoint | Issue | Missing/Wrong | Solution |
|----------|-------|---------------|----------|
| GET /auth/users/{user} | Missing | Entire endpoint | Add new request |
| PUT /profile | Incomplete | Request body | Add JSON + form-data examples |
| POST /auth/email/verify | Unclear | Field options | Add both UUID and token variants |
| POST /auth/email/verify/by-token | Incomplete | Full body spec | Add token and email fields |
| POST /profile/email/request | Incomplete | new_email field | Add email field example |
| POST /profile/email/verify | Incomplete | uuid and code | Add both fields |
| POST /courses | Incomplete | 8+ fields | Add complete JSON with all fields |
| PUT /courses/{slug} | Incomplete | Same as POST | Add complete JSON |
| POST /units | Incomplete | 4 fields | Add code, slug, status, order |
| PUT /units/{slug} | Incomplete | Same as POST units | Add complete JSON |
| PUT /units/reorder | Wrong format | Array structure | Fix to: {"units": [id1, id2]} |
| POST /lessons | Incomplete | 5 fields | Add markdown_content, duration, etc |
| PUT /lessons/{slug} | Incomplete | Same as POST | Add complete JSON |
| POST /blocks | Incomplete | Type variants | Add 4 type examples (text/video/image/file) |
| PUT /blocks/{slug} | Incomplete | Type variants | Add all type examples |

---

## ✅ IMPLEMENTATION PRIORITY

### 🔴 DO FIRST (Today)
1. Add missing `GET /auth/users/{user}` endpoint
2. Fix `POST /courses` with all required fields
3. Fix `PUT /profile` with both JSON and form-data

### 🟠 DO NEXT (This Week)
1. Fix `PUT /courses/{slug}` with all fields
2. Fix Unit endpoints (POST, PUT, reorder)
3. Fix Lesson endpoints (POST, PUT)
4. Complete email verification endpoints

### 🟡 DO LATER (Next Week)
1. Fix Lesson Block endpoints (all 4 types)
2. Add response examples
3. Add error documentation
4. Complete testing

---

## 🎯 QUICK CHECKLIST

### Before Implementation
- [ ] Understand what's wrong (read this file)
- [ ] Know what the fix is (see ✅ AFTER examples)
- [ ] Have Postman open
- [ ] Have correct endpoint definitions available

### During Implementation
- [ ] Add/replace request bodies
- [ ] Set correct HTTP methods
- [ ] Add proper headers
- [ ] Set correct Content-Type
- [ ] Test after each change

### After Implementation
- [ ] Verify request works in Postman
- [ ] Check response format
- [ ] Test with actual data
- [ ] Export updated collection
- [ ] Share with team

---

## 💡 TIPS

### Tip 1: Copy-Paste Carefully
- Copy exact JSON format
- Preserve special characters
- Check for trailing commas

### Tip 2: Test as You Go
- Don't fix everything then test
- Fix one endpoint, test it
- Then move to next
- This catches errors early

### Tip 3: Use Variables
- Replace hardcoded IDs with {{variable}}
- Makes examples reusable
- Easier for team to use

### Tip 4: Keep Variants Separate
- Create subfolder for variants
- Example: `/blocks - text`, `/blocks - video`
- Helps team understand options

### Tip 5: Document Why
- Add comments in Postman
- Explain complex fields
- Help future developers

---

**Last Updated:** November 12, 2025  
**Ready to Use:** YES ✓

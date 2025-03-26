# Database Modification: Company ID Hierarchy Changes

## ❗️ Critical Issues Requiring Immediate Attention

<span style="color: red; font-weight: bold">The following issues will cause the application to BREAK with the new database structure:</span>

1. <span style="color: red">**INSERT Statements in admin/add.php:** The current INSERT does not include company_id and uses a column order that will no longer match the database structure. This will cause immediate SQL errors when adding new staff members.</span>

2. <span style="color: red">**staff_member_by_id Query:** The function doesn't return company_id, which will break any code that tries to update a staff member with the existing data structure.</span>

3. <span style="color: red">**Default company_id Value:** Any staff members created without specifying company_id will fail foreign key constraints.</span>

## Overview

This document outlines the necessary changes to the application after modifying the database structure to switch the order of `company_id` and `department_id` in the `staff_members` table, reflecting a more logical hierarchy with companies appearing before departments.

## Database Changes Already Implemented

1. Changed order in `staff_members` table to place `company_id` before `department_id`
2. Updated all SQL INSERT statements to match the new column order
3. Updated foreign key constraints

## Required PHP Code Changes

### 1. Database Query Functions

#### `functions.php`: `get_all_staff_members()`

- **Current**: Only joins the departments table and returns department information
- **Required Changes**:
  - Add JOIN with companies table
  - Include company name and logo in the SELECT statement
  - Update SQL query to reflect new table structure
  - Add company filtering option similar to department filtering
  - Update parameter list to include company filter
  - Update sorting options to include company

#### `functions.php`: `get_staff_member_by_id()`

- **Current**: Only joins the departments table
- **Required Changes**:
  - Add JOIN with companies table
  - Include company name and ID in the SELECT statement
  - Update return data structure to include company information

#### New Function: `get_all_companies()`

- Create a new function to fetch all companies, similar to `get_all_departments()`
- Should return company ID, name, description, and logo

#### New Function: `get_all_company_names()`

- Create a new function to fetch all company names, similar to `get_all_department_names()`
- Used for filter dropdowns in the UI

#### New Function: `get_company_by_id()`

- Create a new function to get company details by ID
- Similar to `get_department_by_id()`

### 2. Admin Forms

#### `admin/add.php`

- **Current**: Only collects department information when adding staff
- <span style="color: red">**CRITICAL REQUIRED CHANGES**:</span>
  - <span style="color: red">Add company dropdown before department dropdown</span>
  - <span style="color: red">Update SQL INSERT query to include company_id (currently will fail with an SQL error)</span>
  - <span style="color: red">Update form validation to require company_id</span>
  - <span style="color: red">Update bind_param() to include company_id parameter ('ssisss' needs to change to 'sississ')</span>

#### `admin/edit.php`

- **Current**: Only allows editing department information
- **Required Changes**:
  - Add company dropdown before department dropdown
  - Update SQL UPDATE query to include company_id
  - Update form validation to require company_id
  - Update bind_param() to include company_id parameter

### 3. Frontend Display

#### `index.php`

- **Current**: Shows staff with department information only
- **Required Changes**:
  - Update staff cards to display company information
  - Add company filter dropdown similar to department filter
  - Update sorting functionality to allow sorting by company
  - Update search functionality to search across company names as well

### 4. JavaScript Changes

- Update any JavaScript code that handles staff data to accommodate company information
- Update form validation to include company selection
- Add dynamic behavior for company and department relationship if applicable (e.g., filtering departments based on selected company)

## UI/UX Considerations

1. **Staff Cards**: Update the design to display both company and department information
2. **Filters**: Add company filters alongside department filters
3. **Admin Forms**: Ensure logical arrangement with company selection appearing before department selection

## Testing Plan

1. **Database Operations**:
   - Verify staff creation with company and department
   - Verify staff updates with company and department changes
   - Test sorting and filtering by company

2. **UI Validation**:
   - Ensure company information appears correctly on all staff cards
   - Verify company filters work as expected
   - Test all form submissions with company data

## Implementation Priority

<span style="color: red; font-weight: bold">⚠️ URGENT: Fix the following first to prevent application breakage:</span>

<span style="color: red">1. Fix INSERT statement in admin/add.php to include company_id (add company dropdown)</span>
<span style="color: red">2. Update get_staff_member_by_id() to include company information</span>
<span style="color: red">3. Add get_all_companies() function to support company dropdown</span>

Then proceed with:

4. Remaining database query functions (core functionality)
5. Update remaining admin forms (for data management)
6. Update frontend display (for end users)
7. Testing and validation

## Additional Considerations

- Review any import/export functionality to ensure company data is properly handled
- Update any API endpoints that return staff data to include company information
- Consider adding company management pages if they don't already exist

## Temporary Fix (If Needed)

<span style="color: red">If immediate full implementation is not possible, consider implementing a temporary fix:</span>

```php
// Temporary fix for admin/add.php - add this before the INSERT statement
$company_id = 1; // Default to first company

// Update the SQL to include company_id
$sql = "INSERT INTO staff_members (first_name, last_name, company_id, department_id, job_title, email, profile_picture)
        VALUES (?, ?, ?, ?, ?, ?, ?)"; 

// Update bind_param to include company_id
$stmt->bind_param("sississ", $first_name, $last_name, $company_id, $department_id, $job_title, $email, $profile_picture);
```

This temporary solution would allow staff addition to continue working while the proper company selection UI is developed.

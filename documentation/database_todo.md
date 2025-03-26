# TODO: Database Structure Update Implementation

This document outlines the specific tasks needed to update the application to match the new database structure where `company_id` comes before `department_id` in the `staff_members` table. Tasks are organized by priority with the most critical tasks first.

## Critical Tasks (Fix Breaks in Functionality)

- [X] **Fix INSERT statement in admin/add.php**
  - Add `company_id` to INSERT SQL query
  - Update bind_param() from 'ssisss' to 'sississ' (string, int, string, int, string, string, string)
  - Add temporary default value of 1 for company_id if UI changes can't be done immediately

- [X] **Update get_staff_member_by_id() function**
  - Add company_id to SELECT query
  - Add company_name to SELECT query with JOIN to companies table
  - Update return data structure

- [X] **Create get_all_companies() function**
  - Similar structure to get_all_departments()
  - Return id, name, description, and logo for all companies
  - Add to functions.php

- [X] **Update get_all_staff_members() function**
  - Add JOIN with companies table
  - Include company name and information in results
  - Update function to support company filtering

## High Priority (UI Requirements)

- [X] **Update admin/add.php UI**
  - Add company dropdown before department dropdown
  - Add company selection validation
  - Update JavaScript to handle company selection

- [X] **Update admin/edit.php**
  - Add company_id to UPDATE SQL query
  - Add company dropdown in the form
  - Update bind_param() to include company_id
  - Pre-populate selected company

- [X] **Create get_all_company_names() function**
  - For use in filter dropdowns
  - Similar to get_all_department_names()

## Medium Priority (Enhanced Functionality)

- [X] **Update index.php frontend display**
  - Add company information to staff cards
  - Update card by adding company logo and company name in a div over h3.staff-name
  - Add company filter option alongside department filter

- [X] **Update sorting functionality**
  - Add sorting by company name
  - Update SQL queries to support company sorting

- [X] **Add relationships between filters**
  - Filter departments based on selected company (if applicable)
  - Update JavaScript to handle cascading filters

## Lower Priority (Nice to Have)

- [ ] **Add company management pages**
  - Create CRUD interface for companies
  - Add company logo upload functionality
  - Add company description editor

- [ ] **Add company statistics**
  - Show staff count per company
  - Add company metrics to admin dashboard

- [ ] **Document API changes**
  - Update API documentation to reflect new data structure
  - Ensure all endpoints return consistent company data

## Testing Checklist

- [ ] Test adding new staff members
- [ ] Test editing existing staff members
- [ ] Test filtering by company
- [ ] Test sorting by company
- [ ] Test that all form submissions include company_id
- [ ] Verify company information displays correctly on all pages

'use strict'

/**
 * Admin Staff Directory Filtering
 * Provides filtering functionality for the admin interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements - Admin table filters
    const searchInput = document.getElementById('admin-search')
    const departmentFilter = document.getElementById('admin-department-filter')
    const companyFilter = document.getElementById('admin-company-filter')

    // State tracking to prevent circular updates
    let isUpdatingDepartments = false
    let isUpdatingCompanies = false

    // Event Listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterStaffTable)
    }

    if (departmentFilter) {
        departmentFilter.addEventListener('change', filterStaffTable)
    }

    if (companyFilter) {
        companyFilter.addEventListener('change', filterStaffTable)
    }

    // Setup cascading filters if both company and department filters exist
    if (companyFilter && departmentFilter) {
        // Store original department options for reset purposes
        const originalDepartmentOptions = Array.from(departmentFilter.options).map(option => {
            return {
                value: option.value,
                text: option.text,
                selected: option.selected
            }
        })

        // Store original company options for reset purposes
        const originalCompanyOptions = Array.from(companyFilter.options).map(option => {
            return {
                value: option.value,
                text: option.text,
                selected: option.selected
            }
        })

        // Add change event to company filter to update departments
        companyFilter.addEventListener('change', function(event) {
            // Skip if this is a programmatic update (not user-initiated)
            if (isUpdatingCompanies) {
                return
            }

            // Remember current department selection when updating departments
            const currentDepartmentSelection = departmentFilter.value

            // Set flag to indicate we're updating departments programmatically
            isUpdatingDepartments = true

            // Use the shared FilterCore module with admin-specific path ('../../')
            FilterCore.updateDepartmentOptions(this.value, departmentFilter, originalDepartmentOptions, currentDepartmentSelection, '../../')
                .then(() => {
                    // After departments are updated, apply filters
                    filterStaffTable()
                    // Reset flag
                    isUpdatingDepartments = false
                })
                .catch(error => {
                    console.error('Error updating departments:', error)
                    isUpdatingDepartments = false
                })
        })

        // Add change event to department filter to update companies
        departmentFilter.addEventListener('change', function(event) {
            // Skip if this is a programmatic update (not user-initiated)
            if (isUpdatingDepartments) {
                return
            }

            // Only update companies if a specific department is selected (not 'All Departments')
            if (this.value) {
                // Remember current company selection
                const currentCompanySelection = companyFilter.value

                // Set flag to indicate we're updating companies programmatically
                isUpdatingCompanies = true

                // Use the shared FilterCore module with admin-specific path ('../../')
                FilterCore.updateCompanyOptions(this.value, companyFilter, originalCompanyOptions, currentCompanySelection, '../../')
                    .then(() => {
                        // After companies are updated, apply filters
                        filterStaffTable()
                        // Reset flag
                        isUpdatingCompanies = false
                    })
                    .catch(error => {
                        console.error('Error updating companies:', error)
                        isUpdatingCompanies = false
                    })
            } else {
                // If 'All Departments' is selected, restore original company options
                // but maintain the current selection if possible
                isUpdatingCompanies = true
                FilterCore.resetCompanyOptions(companyFilter, originalCompanyOptions, companyFilter.value)
                filterStaffTable()
                isUpdatingCompanies = false
            }
        })
    }

    /**
     * Filter the staff table based on search input and select filters
     */
    function filterStaffTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : ''
        const selectedDepartment = departmentFilter ? departmentFilter.value.toLowerCase() : ''
        const selectedCompany = companyFilter ? companyFilter.value.toLowerCase() : ''

        // Get all staff table rows using the new ID
        const staffRows = document.querySelectorAll('#admin-staff-table-body tr')

        staffRows.forEach(row => {
            // Adjust selectors to match the table structure - Name is in 2nd cell (index 1)
            const name = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || ''
            // Job title is in 5th cell (index 4)
            const job = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || ''
            // Department is in 4th cell (index 3)
            const department = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || ''
            // Company is in 3rd cell (index 2)
            const company = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || ''

            // Check if row matches all filters
            const matchesSearch = name.includes(searchTerm) || job.includes(searchTerm)
            const matchesDepartment = selectedDepartment === '' || department.includes(selectedDepartment)
            const matchesCompany = selectedCompany === '' || company.includes(selectedCompany)

            // Show or hide the row
            if (matchesSearch && matchesDepartment && matchesCompany) {
                row.style.display = ''
            } else {
                row.style.display = 'none'
            }
        })
    }
})
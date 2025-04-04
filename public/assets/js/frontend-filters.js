'use strict'

// Staff Directory Frontend JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const searchInput = document.getElementById('search')
    const departmentFilter = document.getElementById('department-filter')
    const companyFilter = document.getElementById('company-filter')
    const sortSelect = document.getElementById('sort')
    const staffGrid = document.getElementById('staff-grid')

    // State tracking to prevent circular updates
    let isUpdatingDepartments = false // Flag to track if we're updating departments programmatically
    let isUpdatingCompanies = false // Flag to track if we're updating companies programmatically

    // Event Listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterStaff)
    }

    if (departmentFilter) {
        departmentFilter.addEventListener('change', filterStaff)
    }

    if (companyFilter) {
        companyFilter.addEventListener('change', filterStaff)
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

            // Use the shared FilterCore module
            FilterCore.updateDepartmentOptions(this.value, departmentFilter, originalDepartmentOptions, currentDepartmentSelection)
                .then(() => {
                    // After departments are updated, apply filters
                    filterStaff()
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

                // Use the shared FilterCore module
                FilterCore.updateCompanyOptions(this.value, companyFilter, originalCompanyOptions, currentCompanySelection)
                    .then(() => {
                        // After companies are updated, apply filters
                        filterStaff()
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
                filterStaff()
                isUpdatingCompanies = false
            }
        })
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', sortStaff)
    }

    // Functions
    function filterStaff() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : ''
        const selectedDepartment = departmentFilter ? departmentFilter.value : ''
        const selectedCompany = companyFilter ? companyFilter.value : ''

        // Get all staff cards
        const staffCards = document.querySelectorAll('.staff-card')

        staffCards.forEach(card => {
            const name = card.querySelector('.staff-name').textContent.toLowerCase()
            const job = card.querySelector('.staff-job').textContent.toLowerCase()
            const department = card.querySelector('.staff-department').textContent.toLowerCase()
            const company = card.querySelector('.company-name')?.textContent.toLowerCase() || ''

            // Check if card matches all filters
            const matchesSearch = name.includes(searchTerm) || job.includes(searchTerm)
            const matchesDepartment = selectedDepartment === '' || department.includes(selectedDepartment.toLowerCase())
            const matchesCompany = selectedCompany === '' || company.includes(selectedCompany.toLowerCase())

            // Show or hide the card
            if (matchesSearch && matchesDepartment && matchesCompany) {
                card.style.display = 'block'
            } else {
                card.style.display = 'none'
            }
        })
    }

    function sortStaff() {
        const sortBy = sortSelect.value
        const staffCards = Array.from(document.querySelectorAll('.staff-card'))

        staffCards.sort((a, b) => {
            let valueA, valueB

            if (sortBy === 'name-asc' || sortBy === 'name-desc') {
                // Extract the full name
                const fullNameA = a.querySelector('.staff-name').textContent.trim()
                const fullNameB = b.querySelector('.staff-name').textContent.trim()

                // Extract last name (assuming the name format is "First Last")
                const lastNameA = fullNameA.split(' ').pop().toLowerCase()
                const lastNameB = fullNameB.split(' ').pop().toLowerCase()

                // If last names are equal, sort by first name
                if (lastNameA === lastNameB) {
                    // Get the first name
                    const firstNameA = fullNameA.split(' ').slice(0, -1).join(' ').toLowerCase()
                    const firstNameB = fullNameB.split(' ').slice(0, -1).join(' ').toLowerCase()
                    valueA = firstNameA
                    valueB = firstNameB
                } else {
                    valueA = lastNameA
                    valueB = lastNameB
                }
            } else if (sortBy === 'department-asc' || sortBy === 'department-desc') {
                valueA = a.querySelector('.staff-department').textContent.toLowerCase()
                valueB = b.querySelector('.staff-department').textContent.toLowerCase()
            } else if (sortBy === 'company-asc' || sortBy === 'company-desc') {
                valueA = (a.querySelector('.company-name')?.textContent || '').toLowerCase()
                valueB = (b.querySelector('.company-name')?.textContent || '').toLowerCase()
            }

            // Determine sort direction
            const sortDir = sortBy.endsWith('-asc') ? 1 : -1

            // Use localeCompare for better string comparison
            return valueA.localeCompare(valueB) * sortDir
        })

        // Reappend the sorted cards to the grid
        staffCards.forEach(card => {
            staffGrid.appendChild(card)
        })
    }

    // Delete confirmation for admin
    const deleteButtons = document.querySelectorAll('.outline-danger')
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this staff member?')) {
                    e.preventDefault()
                }
            })
        })
    }
})

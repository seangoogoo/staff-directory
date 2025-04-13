'use strict'

/**
 * Updates department filter options based on selected company
 * Returns a Promise that resolves when the update is complete
 *
 * @param {string} companyName - The selected company name
 * @param {HTMLSelectElement} departmentSelect - The department select dropdown element
 * @param {Array} originalOptions - Original department options to restore when no company is selected
 * @param {string} maintainSelection - Current department selection to maintain if possible
 * @param {string} basePath - Base path for AJAX handler ('../' or '../../')
 * @returns {Promise} Promise that resolves when the update is complete
 */
function updateDepartmentOptions(companyName, departmentSelect, originalOptions, maintainSelection = '') {
    // If no company is selected, restore all original options but maintain current selection if specified
    if (!companyName) {
        resetDepartmentOptions(departmentSelect, originalOptions, maintainSelection)
        return Promise.resolve()
    }

    // Store current selection (we'll try to maintain it if possible)
    const currentSelection = maintainSelection || departmentSelect.value

    // Return a promise that resolves when the department options are updated
    return new Promise((resolve, reject) => {
        // Use APP_BASE_URI prefix since we're in a subdirectory
        // Fetch departments for this company via AJAX
        const baseUri = window.APP_BASE_URI || '';
        fetch(`${baseUri}/includes/ajax_handlers.php?action=get_departments_by_company&company=${encodeURIComponent(companyName)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`)
                }
                return response.json()
            })
            .then(result => {
                // Check for success
                if (!result.success) {
                    throw new Error(result.error || 'Unknown error')
                }

                // Extract departments from the response
                const departments = result.departments || []

                // Clear current options
                departmentSelect.innerHTML = ''

                // Add "All Departments" option
                const allOption = document.createElement('option')
                allOption.value = ''
                allOption.text = 'All Departments'
                departmentSelect.appendChild(allOption)

                // Add departments from the response
                departments.forEach(dept => {
                    const option = document.createElement('option')
                    option.value = dept
                    option.text = dept
                    // Restore previous selection if it exists in new options
                    if (dept === currentSelection) {
                        option.selected = true
                    }
                    departmentSelect.appendChild(option)
                })

                // If previous selection is no longer available, select "All Departments"
                if (departments.indexOf(currentSelection) === -1) {
                    departmentSelect.value = ''
                }

                // Resolve the promise with the updated departments
                resolve(departments)
            })
            .catch(error => {
                console.error('Error fetching departments:', error)

                // Restore original options on error
                resetDepartmentOptions(departmentSelect, originalOptions)

                // Reject the promise with the error
                reject(error)
            })
    })
}

/**
 * Helper function to reset department options
 *
 * @param {HTMLSelectElement} departmentSelect - The department select dropdown element
 * @param {Array} originalOptions - Original department options
 * @param {string} maintainSelection - Current selection to maintain if possible
 */
function resetDepartmentOptions(departmentSelect, originalOptions, maintainSelection = '') {
    // Store the current selection we want to maintain
    const selectionToMaintain = maintainSelection || departmentSelect.value

    // Clear current options
    departmentSelect.innerHTML = ''

    // Restore original options
    originalOptions.forEach(option => {
        const optionEl = document.createElement('option')
        optionEl.value = option.value
        optionEl.text = option.text
        // If this option matches the selection to maintain, mark it as selected
        if (selectionToMaintain && option.value === selectionToMaintain) {
            optionEl.selected = true
        } else {
            optionEl.selected = option.selected
        }
        departmentSelect.appendChild(optionEl)
    })

    // Explicitly set the value to maintain the selection
    if (selectionToMaintain) {
        departmentSelect.value = selectionToMaintain
    }
}

/**
 * Updates company filter options based on selected department
 * Returns a Promise that resolves when the update is complete
 *
 * @param {string} departmentName - The selected department name
 * @param {HTMLSelectElement} companySelect - The company select dropdown element
 * @param {Array} originalOptions - Original company options to restore when no department is selected
 * @param {string} maintainSelection - Current company selection to maintain if possible
 * @param {string} basePath - Base path for AJAX handler ('../' or '../../')
 * @returns {Promise} Promise that resolves when the update is complete
 */
function updateCompanyOptions(departmentName, companySelect, originalOptions, maintainSelection = '') {
    // If no department is selected, use the reset helper function
    if (!departmentName) {
        resetCompanyOptions(companySelect, originalOptions, maintainSelection)
        return Promise.resolve()
    }

    // Store current selection (we'll try to maintain it if possible)
    const currentSelection = maintainSelection || companySelect.value

    // Return a promise that resolves when the company options are updated
    return new Promise((resolve, reject) => {
        // Use APP_BASE_URI prefix since we're in a subdirectory
        // Fetch companies for this department via AJAX
        const baseUri = window.APP_BASE_URI || '';
        fetch(`${baseUri}/includes/ajax_handlers.php?action=get_companies_by_department&department=${encodeURIComponent(departmentName)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`)
                }
                return response.json()
            })
            .then(result => {
                // Check for success
                if (!result.success) {
                    throw new Error(result.error || 'Unknown error')
                }

                // Extract companies from the response
                const companies = result.companies || []

                // Clear current options
                companySelect.innerHTML = ''

                // Add "All Companies" option
                const allOption = document.createElement('option')
                allOption.value = ''
                allOption.text = 'All Companies'
                companySelect.appendChild(allOption)

                // Add companies from the response
                companies.forEach(company => {
                    const option = document.createElement('option')
                    option.value = company
                    option.text = company
                    // Restore previous selection if it exists in new options
                    if (company === currentSelection) {
                        option.selected = true
                    }
                    companySelect.appendChild(option)
                })

                // If previous selection is no longer available, select "All Companies"
                if (companies.indexOf(currentSelection) === -1) {
                    companySelect.value = ''
                }

                // Resolve the promise with the updated companies
                resolve(companies)
            })
            .catch(error => {
                console.error('Error fetching companies:', error)

                // Restore original options on error
                resetCompanyOptions(companySelect, originalOptions)

                // Reject the promise with the error
                reject(error)
            })
    })
}

/**
 * Helper function to reset company options
 *
 * @param {HTMLSelectElement} companySelect - The company select dropdown
 * @param {Array} originalOptions - Original company options
 * @param {string} maintainSelection - Current selection to maintain if possible
 */
function resetCompanyOptions(companySelect, originalOptions, maintainSelection = '') {
    // Store the current selection we want to maintain
    const selectionToMaintain = maintainSelection || companySelect.value

    // Clear current options
    companySelect.innerHTML = ''

    // Restore original options
    originalOptions.forEach(option => {
        const optionEl = document.createElement('option')
        optionEl.value = option.value
        optionEl.text = option.text
        // If this option matches the selection to maintain, mark it as selected
        if (selectionToMaintain && option.value === selectionToMaintain) {
            optionEl.selected = true
        } else {
            optionEl.selected = option.selected
        }
        companySelect.appendChild(optionEl)
    })

    // Explicitly set the value to maintain the selection
    if (selectionToMaintain) {
        companySelect.value = selectionToMaintain
    }
}

// Export all functions as a module
window.FilterCore = {
    updateDepartmentOptions,
    resetDepartmentOptions,
    updateCompanyOptions,
    resetCompanyOptions
}
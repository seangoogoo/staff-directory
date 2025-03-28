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
            
            // Update department options based on selected company
            updateDepartmentOptions(this.value, departmentFilter, originalDepartmentOptions, currentDepartmentSelection)
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
                
                // Update company options based on selected department
                updateCompanyOptions(this.value, companyFilter, originalCompanyOptions, currentCompanySelection)
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
                resetCompanyOptions(companyFilter, originalCompanyOptions, companyFilter.value)
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
                valueA = a.querySelector('.staff-name').textContent
                valueB = b.querySelector('.staff-name').textContent
            } else if (sortBy === 'department-asc' || sortBy === 'department-desc') {
                valueA = a.querySelector('.staff-department').textContent
                valueB = b.querySelector('.staff-department').textContent
            } else if (sortBy === 'company-asc' || sortBy === 'company-desc') {
                valueA = a.querySelector('.company-name')?.textContent || ''
                valueB = b.querySelector('.company-name')?.textContent || ''
            }

            // Determine sort direction
            const sortDir = sortBy.endsWith('-asc') ? 1 : -1

            // Compare the values
            if (valueA < valueB) return -1 * sortDir
            if (valueA > valueB) return 1 * sortDir
            return 0
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

// Dropzone functionality for image uploads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropzone and remove button
    initializeDropzoneAndRemoveButton()

    // Store the original image source for edit.php page
    if (window.location.pathname.includes('edit.php')) {
        const imagePreview = document.getElementById('image-preview')
        if (imagePreview) {
            // Store the original src as a data attribute
            imagePreview.dataset.originalSrc = imagePreview.src
        }
    }
})

/**
 * Initialize dropzone and remove button functionality
 * Sets up all event handlers for image uploads and removals
 */
/**
 * Updates company filter options based on selected department while preserving selection when possible
 * Returns a Promise that resolves when the update is complete
 *
 * @param {string} departmentName - The selected department name
 * @param {HTMLSelectElement} companySelect - The company select dropdown element
 * @param {Array} originalOptions - Original company options to restore when no department is selected
 * @param {string} maintainSelection - Current company selection to maintain if possible
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

    // Build correct path to AJAX handler relative to the current page
    const currentPath = window.location.pathname
    const basePath = currentPath.includes('/admin/') ? '../../' : '../'

    // Return a promise that resolves when the company options are updated
    return new Promise((resolve, reject) => {
        // Fetch companies for this department via AJAX
        fetch(`${basePath}includes/ajax_handlers.php?action=get_companies_by_department&department=${encodeURIComponent(departmentName)}`)
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

function initializeDropzoneAndRemoveButton() {
    // Set up dropzone if it exists on the page
    const dropzone = document.querySelector('.dropzone')
    const fileInput = document.querySelector('.dropzone-input')
    const imagePreview = document.getElementById('image-preview')
    const removeButton = document.getElementById('remove-image')
    const defaultImage = imagePreview ? imagePreview.src : ''

    // Set the initial state for the remove button based on whether this is a placeholder image
    if (removeButton && imagePreview) {
        const isPlaceholder = isPlaceholderImage(imagePreview)

        // Set data attribute and button visibility
        imagePreview.dataset.isPlaceholder = isPlaceholder ? 'true' : 'false'
        removeButton.style.display = isPlaceholder ? 'none' : 'flex'
    }

    if (dropzone && fileInput) {
        // Handle click on dropzone to trigger file input
        dropzone.addEventListener('click', function() {
            fileInput.click()
        })

        // Prevent default drag behaviors
        ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false)
        })

        function preventDefaults(e) {
            e.preventDefault()
            e.stopPropagation()
        }

        // Handle dragenter and dragover to highlight dropzone
        ;['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false)
        })

        // Handle dragleave and drop to unhighlight dropzone
        ;['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false)
        })

        function highlight() {
            dropzone.classList.add('dragover')
        }

        function unhighlight() {
            dropzone.classList.remove('dragover')
        }

        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false)

        function handleDrop(e) {
            const dt = e.dataTransfer
            const files = dt.files
            if (files.length) {
                fileInput.files = files
                updateFileInfo(files[0])
                previewImage(files[0])
                showRemoveButton()
            }
        }

        // Handle file input change
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                updateFileInfo(this.files[0])
                previewImage(this.files[0])
                showRemoveButton()
            }
        })

        function updateFileInfo(file) {
            const fileInfoEl = document.querySelector('.dropzone-file-info')
            if (fileInfoEl) {
                fileInfoEl.textContent = `Selected: ${file.name} (${formatFileSize(file.size)})`
                fileInfoEl.style.display = 'block'
            }
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' bytes'
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
            else return (bytes / 1048576).toFixed(1) + ' MB'
        }

        // Show remove button when an image is displayed
        function showRemoveButton() {
            if (!removeButton || !imagePreview) return

            const isPlaceholder = isPlaceholderImage(imagePreview)

            // Only show button for real images, not placeholders
            if (!isPlaceholder) {
                imagePreview.dataset.isPlaceholder = 'false'
                removeButton.style.display = 'flex'
            } else {
                removeButton.style.display = 'none'
            }
        }

        // Check if we already have a non-default image and show the remove button
        if (imagePreview && !imagePreview.src.includes('placehold.co') &&
            !imagePreview.src.includes('default.jpg')) {
            showRemoveButton()
        }
    }

    // Setup remove button outside of dropzone logic
    setupRemoveButton(removeButton, imagePreview, fileInput, defaultImage)
}

/**
 * Set up the remove button click handler
 * @param {Element} removeButton - The remove button element
 * @param {Element} imagePreview - The image preview element
 * @param {Element} fileInput - The file input element
 * @param {string} defaultImage - URL of the default image
 */
function setupRemoveButton(removeButton, imagePreview, fileInput, defaultImage) {
    if (!removeButton || !imagePreview) return

    // First remove any existing listeners to avoid duplicates
    removeButton.replaceWith(removeButton.cloneNode(true))

    // Get the fresh button reference
    const freshButton = document.getElementById('remove-image')

    // Check if this is a placeholder image and set visibility appropriately
    const isPlaceholder = isPlaceholderImage(imagePreview)

    // Update data attributes and visibility based on placeholder status
    imagePreview.dataset.isPlaceholder = isPlaceholder ? 'true' : 'false'
    freshButton.style.display = isPlaceholder ? 'none' : 'flex'

    // Add direct event listener
    freshButton.addEventListener('click', function(e) {
        // Stop propagation and default behavior
        e.preventDefault()
        e.stopPropagation()

        // Different behavior based on which page we're on
        if (window.location.pathname.includes('edit.php')) {
            // In edit.php, we need to handle database deletion
            handleEditPageImageRemoval(imagePreview, fileInput, freshButton)
        } else {
            // In add.php, just reset the image to default
            handleAddPageImageRemoval(imagePreview, fileInput, freshButton, defaultImage)
        }
    }, true) // Use capturing phase to ensure this runs first
}

// Handle image removal on add.php
function handleAddPageImageRemoval(imagePreview, fileInput, removeButton, defaultImage) {
    // Reset image to placeholder
    if (imagePreview) {
        imagePreview.src = defaultImage
    }

    // Clear file input
    if (fileInput) {
        fileInput.value = ''
    }

    // Hide remove button
    removeButton.style.display = 'none'

    // Hide file info
    const fileInfoEl = document.querySelector('.dropzone-file-info')
    if (fileInfoEl) {
        fileInfoEl.style.display = 'none'
    }

    console.log('Image reset on add page')
}

/**
 * Check if an image element has a placeholder image
 * @param {Element} imageElement - The image element to check
 * @returns {boolean} - True if the image is a placeholder
 */
function isPlaceholderImage(imageElement) {
    if (!imageElement) return true

    const imageSrc = imageElement.src || ''
    return imageSrc.includes('generate_placeholder.php') ||
           imageSrc.includes('placeholder') ||
           imageElement.dataset.isPlaceholder === 'true'
}

/**
 * Handle image removal on edit.php
 * @param {Element} imagePreview - The image preview element
 * @param {Element} fileInput - The file input element
 * @param {Element} removeButton - The remove button element
 */
function handleEditPageImageRemoval(imagePreview, fileInput, removeButton) {
    // Check if this is a new file upload or an existing image
    const isNewUpload = fileInput && fileInput.value

    // Don't allow removing placeholder images
    if (isPlaceholderImage(imagePreview)) {
        return
    }

    if (isNewUpload) {
        // Just reset the file input for new uploads
        if (fileInput) {
            fileInput.value = ''
        }

        // Reset preview to original image
        if (imagePreview && imagePreview.dataset.originalSrc) {
            imagePreview.src = imagePreview.dataset.originalSrc
        }

        // Hide the remove button
        removeButton.style.display = 'none'

        // Hide file info
        const fileInfoEl = document.querySelector('.dropzone-file-info')
        if (fileInfoEl) {
            fileInfoEl.style.display = 'none'
        }

        console.log('New upload reset on edit page')
    } else {
        // For existing images, just mark it for deletion by adding a hidden input
        const form = document.querySelector('form')

        // Check if we already have a delete_image input
        let deleteInput = document.getElementById('delete_image')

        if (!deleteInput) {
            // Create a hidden input to indicate the image should be deleted
            deleteInput = document.createElement('input')
            deleteInput.type = 'hidden'
            deleteInput.name = 'delete_image'
            deleteInput.id = 'delete_image'
            deleteInput.value = '1'
            form.appendChild(deleteInput)
        } else {
            // Just make sure the value is set
            deleteInput.value = '1'
        }

        // Set image to placeholder with department color
        if (imagePreview) {
            // Get department color - first try department select, then fallback to data attribute
            const departmentSelect = document.getElementById('department_id')
            const departmentId = departmentSelect?.value

            // Start with any saved department color on the image element itself
            let departmentColor = imagePreview.dataset.deptColor || '#cccccc'

            // Then try to get from selected department, which might have changed
            if (departmentId) {
                const selectedOption = departmentSelect.querySelector(`option[value="${departmentId}"]`)
                if (selectedOption && selectedOption.dataset.color) {
                    departmentColor = selectedOption.dataset.color
                    // Update the data attribute for future reference
                    imagePreview.dataset.deptColor = departmentColor
                }
            }

            // Create initials from staff name
            const firstName = document.getElementById('first_name')?.value || ''
            const lastName = document.getElementById('last_name')?.value || ''

            // Use our custom placeholder generator with department color
            const timestamp = new Date().getTime() // Prevent caching
            imagePreview.src = `../includes/generate_placeholder.php?name=${firstName}+${lastName}&size=200x200&bg_color=${encodeURIComponent(departmentColor)}&t=${timestamp}`

            // Mark as placeholder
            imagePreview.dataset.isPlaceholder = 'true'
        }

        // Hide the remove button since there's no image to remove anymore
        removeButton.style.display = 'none'

        console.log('Image marked for deletion on form submit')
    }
}

// Helper function to get initials from URL
function getInitialsFromUrl(url) {
    // Try to extract first_name and last_name from the URL if they're in GET params
    const urlParams = new URLSearchParams(new URL(url).search)
    const staffId = urlParams.get('id')

    // Default to 'NO IMG' if we can't extract anything
    return 'NO IMG'
}

// Helper function to format file sizes
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes'
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    else return (bytes / 1048576).toFixed(1) + ' MB'
}

/**
 * Updates department filter options based on selected company
 *
 * @param {string} companyName - The selected company name
 * @param {HTMLSelectElement} departmentSelect - The department select dropdown element
 * @param {Array} originalOptions - Original department options to restore when no company is selected
 */
/**
 * Helper function to reset company options while maintaining selected value
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

/**
 * Updates department filter options based on selected company while preserving selection when possible
 * Returns a Promise that resolves when the update is complete
 *
 * @param {string} companyName - The selected company name
 * @param {HTMLSelectElement} departmentSelect - The department select dropdown element
 * @param {Array} originalOptions - Original department options to restore when no company is selected
 * @param {string} maintainSelection - Current department selection to maintain if possible
 * @returns {Promise} Promise that resolves when the update is complete
 */
function updateDepartmentOptions(companyName, departmentSelect, originalOptions, maintainSelection = '') {
    // If no company is selected, restore all original options but maintain current selection if specified
    if (!companyName) {
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
        
        // Return a resolved promise since this is synchronous
        return Promise.resolve()
    }

    // Store current selection (we'll try to maintain it if possible)
    const currentSelection = maintainSelection || departmentSelect.value

    // Build correct path to AJAX handler relative to the current page
    const currentPath = window.location.pathname
    const basePath = currentPath.includes('/admin/') ? '../../' : '../'

    // Return a promise that resolves when the department options are updated
    return new Promise((resolve, reject) => {
        // Fetch departments for this company via AJAX
        fetch(`${basePath}includes/ajax_handlers.php?action=get_departments_by_company&company=${encodeURIComponent(companyName)}`)
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
                departmentSelect.innerHTML = ''
                originalOptions.forEach(option => {
                    const optionEl = document.createElement('option')
                    optionEl.value = option.value
                    optionEl.text = option.text
                    optionEl.selected = option.selected
                    departmentSelect.appendChild(optionEl)
                })
                
                // Reject the promise with the error
                reject(error)
            })
    })
}

/**
 * Preview profile picture (works with both dropzone and regular file input)
 * @param {File|HTMLInputElement} input - Either a File object or an input element with files
 */
function previewImage(input) {
    let file
    const preview = document.getElementById('image-preview')
    const removeButton = document.getElementById('remove-image')

    // Extract the file from the input
    if (input instanceof File) {
        file = input
    } else if (input.files && input.files[0]) {
        file = input.files[0]
    } else {
        return
    }

    const reader = new FileReader()

    reader.onload = function(e) {
        if (preview) {
            // Store original src for potential reset (if not already stored)
            if (!preview.dataset.originalSrc) {
                preview.dataset.originalSrc = preview.src
            }

            // Update the image with the new file
            preview.src = e.target.result

            // Mark this as not a placeholder image since user uploaded it
            preview.dataset.isPlaceholder = 'false'
        }

        // Show remove button since this is a user upload
        if (removeButton) {
            // Make remove button visible for uploaded images
            removeButton.style.display = 'flex'
        }

        // Show file info if present
        const fileInfoEl = document.querySelector('.dropzone-file-info')
        if (fileInfoEl && file) {
            fileInfoEl.textContent = `Selected: ${file.name} (${formatFileSize(file.size)})`
            fileInfoEl.style.display = 'block'
        }
    }

    reader.readAsDataURL(file)
}

// Staff Directory Frontend JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const searchInput = document.getElementById('search')
    const departmentFilter = document.getElementById('department-filter')
    const sortSelect = document.getElementById('sort')
    const staffGrid = document.getElementById('staff-grid')

    // Event Listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterStaff)
    }

    if (departmentFilter) {
        departmentFilter.addEventListener('change', filterStaff)
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', sortStaff)
    }

    // Functions
    function filterStaff() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : ''
        const selectedDepartment = departmentFilter ? departmentFilter.value : ''

        // Get all staff cards
        const staffCards = document.querySelectorAll('.staff-card')

        staffCards.forEach(card => {
            const name = card.querySelector('.staff-name').textContent.toLowerCase()
            const job = card.querySelector('.staff-job').textContent.toLowerCase()
            const department = card.querySelector('.staff-department').textContent.toLowerCase()

            // Check if card matches search term and department filter
            const matchesSearch = name.includes(searchTerm) || job.includes(searchTerm)
            const matchesDepartment = selectedDepartment === '' || department.includes(selectedDepartment.toLowerCase())

            // Show or hide the card
            if (matchesSearch && matchesDepartment) {
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

// Initialize dropzone and remove button functionality
function initializeDropzoneAndRemoveButton() {
    // Set up dropzone if it exists on the page
    const dropzone = document.querySelector('.dropzone')
    const fileInput = document.querySelector('.dropzone-input')
    const imagePreview = document.getElementById('image-preview')
    const removeButton = document.getElementById('remove-image')
    const defaultImage = imagePreview ? imagePreview.src : ''

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
            if (removeButton) {
                removeButton.style.display = 'flex'
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

// Function to set up the remove button click handler
function setupRemoveButton(removeButton, imagePreview, fileInput, defaultImage) {
    if (!removeButton) return

    // First remove any existing listeners
    removeButton.replaceWith(removeButton.cloneNode(true))

    // Get the fresh button reference
    const freshButton = document.getElementById('remove-image')

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

// Handle image removal on edit.php
function handleEditPageImageRemoval(imagePreview, fileInput, removeButton) {
    // Check if this is a new file upload or an existing image
    const isNewUpload = fileInput && fileInput.value

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

        // Set image to placeholder
        if (imagePreview) {
            // Create initials from staff name or use NO IMG
            const firstName = document.getElementById('first_name')?.value || ''
            const lastName = document.getElementById('last_name')?.value || ''
            const initials = firstName && lastName ?
                (firstName.charAt(0) + lastName.charAt(0)).toUpperCase() : 'NO IMG'

            imagePreview.src = 'https://placehold.co/200x200?text=' + initials
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

// Preview profile picture (works with both dropzone and regular file input)
function previewImage(input) {
    let file
    const preview = document.getElementById('image-preview')
    const removeButton = document.getElementById('remove-image')

    // If input is a File object directly
    if (input instanceof File) {
        file = input
    }
    // If input is an input element
    else if (input.files && input.files[0]) {
        file = input.files[0]
    } else {
        return
    }

    const reader = new FileReader()

    reader.onload = function(e) {
        if (preview) {
            preview.src = e.target.result
        }

        // Show remove button whenever a new image is previewed
        if (removeButton) {
            removeButton.style.display = 'flex'
        }
    }

    reader.readAsDataURL(file)
}

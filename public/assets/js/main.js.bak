'use strict'

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

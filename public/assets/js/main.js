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
 * @param {Element} [customDropzone] - Optional custom dropzone element
 * @param {Element} [customFileInput] - Optional custom file input element
 * @param {Element} [customImagePreview] - Optional custom image preview element
 * @param {Element} [customRemoveButton] - Optional custom remove button element
 */
function initializeDropzoneAndRemoveButton(customDropzone, customFileInput, customImagePreview, customRemoveButton) {
    // Set up dropzone if it exists on the page
    const dropzone = customDropzone || document.querySelector('.dropzone')
    const fileInput = customFileInput || document.querySelector('.dropzone-input')
    const imagePreview = customImagePreview || document.getElementById('image-preview')
    const removeButton = customRemoveButton || document.getElementById('remove-image')

    // Store the default image path as a data attribute if not already set
    if (imagePreview && !imagePreview.dataset.defaultImage) {
        imagePreview.dataset.defaultImage = imagePreview.src
    }

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

        // The remove button visibility is now handled by the isPlaceholderImage function
        // which checks for default images and placeholders
    }

    // Setup remove button outside of dropzone logic
    setupRemoveButton(removeButton, imagePreview, fileInput)
}

/**
 * Set up the remove button click handler
 * @param {Element} removeButton - The remove button element
 * @param {Element} imagePreview - The image preview element
 * @param {Element} fileInput - The file input element
 */
function setupRemoveButton(removeButton, imagePreview, fileInput) {
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

        // Determine page type for different handling
        const currentPath = window.location.pathname
        const pageName = currentPath.split('/').pop()

        // Check for custom field to update
        const customFieldName = freshButton.dataset.updateField

        if (pageName === 'edit.php' || currentPath.includes('edit.php')) {
            // In edit.php, we need to handle database deletion
            handleEditPageImageRemoval(imagePreview, fileInput, freshButton)
        }
        else if (pageName === 'settings.php' || currentPath.includes('settings.php')) {
            // Special case for settings page
            handleSettingsPageImageRemoval(imagePreview, fileInput, freshButton)
        }
        else if (pageName === 'companies.php' || currentPath.includes('companies.php')) {
            // Special case for companies page
            handleCompaniesPageImageRemoval(imagePreview, fileInput, freshButton)
        }
        else {
            // Default case (add.php and others)
            handleAddPageImageRemoval(imagePreview, fileInput, freshButton)
        }

        // Handle any custom fields specified via data attributes
        if (customFieldName) {
            const customField = document.getElementById(customFieldName)
            if (customField) {
                customField.value = freshButton.dataset.updateValue || '1'
                if (window?.DEV_MODE === true) console.log(`Updated ${customFieldName} with value ${customField.value}`)
            }
        }
    }, true) // Use capturing phase to ensure this runs first
}

// Handle image removal on add.php
function handleAddPageImageRemoval(imagePreview, fileInput, removeButton) {
    // Reset image to the original default image stored in the data attribute
    if (imagePreview) {
        // Use the data attribute if available
        const defaultSrc = imagePreview.dataset.defaultImage || '../assets/images/add-picture.svg'
        imagePreview.src = defaultSrc
        imagePreview.dataset.isPlaceholder = 'true'
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

    if (window?.DEV_MODE === true) console.log('Image reset on add page')
}

/**
 * Handle image removal on settings.php
 * @param {Element} imagePreview - The image preview element
 * @param {Element} fileInput - The file input element
 * @param {Element} removeButton - The remove button element
 */
function handleSettingsPageImageRemoval(imagePreview, fileInput, removeButton) {
    // Reset image to the original default image stored in the data attribute
    if (imagePreview) {
        // Use the data attribute if available
        const defaultSrc = imagePreview.dataset.defaultImage || '../assets/images/staff-directory-logo.svg'
        // Force the browser to update the image
        imagePreview.src = defaultSrc
        imagePreview.dataset.isPlaceholder = 'true'

        // In case the browser caches the image and doesn't trigger a refresh,
        // recreate the image element
        try {
            const currentParent = imagePreview.parentNode
            const newImage = document.createElement('img')

            // Copy all attributes from the original image
            newImage.id = 'image-preview'
            newImage.className = imagePreview.className
            newImage.alt = 'Default Logo'
            newImage.src = defaultSrc

            // Copy all data attributes from the original image
            for (const key in imagePreview.dataset) {
                newImage.dataset[key] = imagePreview.dataset[key]
            }

            // Ensure critical data attributes are set correctly
            newImage.dataset.defaultImage = defaultSrc
            newImage.dataset.isPlaceholder = 'true'

            // Preserve originalSrc if it exists
            if (imagePreview.dataset.originalSrc) {
                newImage.dataset.originalSrc = imagePreview.dataset.originalSrc
            }

            // Replace the old image with the new one
            if (currentParent) {
                currentParent.replaceChild(newImage, imagePreview)
                if (window?.DEV_MODE === true) console.log('Replaced image element with new one using default logo')

                // After replacing, reinitialize the dropzone and remove button
                // This ensures all event listeners are properly attached to the new element
                setTimeout(() => {
                    initializeDropzoneAndRemoveButton()
                    if (window?.DEV_MODE === true) console.log('Reinitialized dropzone and remove button after image replacement')
                }, 0)
            }
        } catch (error) {
            if (window?.DEV_MODE === true) console.error('Error replacing image:', error)
        }
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

    // Set remove_logo to 1 (using the field specified in data-update-field)
    const updateField = removeButton.dataset.updateField || 'remove_logo'
    const updateValue = removeButton.dataset.updateValue || '1'
    const removeLogoInput = document.getElementById(updateField)
    if (removeLogoInput) {
        removeLogoInput.value = updateValue
        if (window?.DEV_MODE === true) console.log(`${updateField} value set to ${updateValue}`)
    }

    if (window?.DEV_MODE === true) console.log('Image reset on settings page')
}

/**
 * Handle image removal on companies.php
 * @param {Element} imagePreview - The image preview element
 * @param {Element} fileInput - The file input element
 * @param {Element} removeButton - The remove button element
 */
function handleCompaniesPageImageRemoval(imagePreview, fileInput, removeButton) {
    // Reset image to the original default image stored in the data attribute
    if (imagePreview) {
        // Use the data attribute if available
        const defaultSrc = imagePreview.dataset.defaultImage || '../assets/images/add-picture.svg'
        imagePreview.src = defaultSrc
        imagePreview.dataset.isPlaceholder = 'true'
    }

    // Clear file input
    if (fileInput) {
        fileInput.value = ''
    }

    // Set delete_logo to 1 (using the field specified in data-update-field)
    const updateField = removeButton.dataset.updateField || 'delete_logo'
    const updateValue = removeButton.dataset.updateValue || '1'
    const deleteLogoInput = document.getElementById(updateField)
    if (deleteLogoInput) {
        deleteLogoInput.value = updateValue
        if (window?.DEV_MODE === true) console.log(`${updateField} value set to ${updateValue}`)
    }

    // Hide remove button
    removeButton.style.display = 'none'

    // Hide file info
    const fileInfoEl = document.querySelector('.dropzone-file-info')
    if (fileInfoEl) {
        fileInfoEl.style.display = 'none'
        fileInfoEl.textContent = ''
    }

    if (window?.DEV_MODE === true) console.log('Image reset on companies page')
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
           imageSrc.includes('staff-directory-logo.svg') ||
           imageSrc.includes('add-picture.svg') ||
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
        if (imagePreview) {
            // First try originalSrc, then data-default-image, then current src
            const originalSrc = imagePreview.dataset.originalSrc || imagePreview.dataset.defaultImage || imagePreview.src
            imagePreview.src = originalSrc
        }

        // Hide the remove button
        removeButton.style.display = 'none'

        // Hide file info
        const fileInfoEl = document.querySelector('.dropzone-file-info')
        if (fileInfoEl) {
            fileInfoEl.style.display = 'none'
        }

        if (window?.DEV_MODE === true) console.log('New upload reset on edit page')
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
            imagePreview.src = `${window.APP_BASE_URI}/includes/generate_placeholder.php?name=${firstName}+${lastName}&size=200x200&bg_color=${encodeURIComponent(departmentColor)}&t=${timestamp}`

            // Mark as placeholder
            imagePreview.dataset.isPlaceholder = 'true'
        }

        // Hide the remove button since there's no image to remove anymore
        removeButton.style.display = 'none'

        if (window?.DEV_MODE === true) console.log('Image marked for deletion on form submit')
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
    // We'll get a fresh reference in the onload callback

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
        // Get a fresh reference to the image preview element
        // This ensures we're using the current DOM element even if it was replaced
        const currentPreview = document.getElementById('image-preview')
        const currentRemoveButton = document.getElementById('remove-image')

        if (currentPreview) {
            // Store original src for potential reset (if not already stored)
            if (!currentPreview.dataset.originalSrc) {
                currentPreview.dataset.originalSrc = currentPreview.src
            }

            // Update the image with the new file
            currentPreview.src = e.target.result

            // Mark this as not a placeholder image since user uploaded it
            currentPreview.dataset.isPlaceholder = 'false'
        }

        // Show remove button since this is a user upload
        if (currentRemoveButton) {
            // Make remove button visible for uploaded images
            currentRemoveButton.style.display = 'flex'
        }

        // Reset remove_logo flag when a new image is selected
        const removeLogoInput = document.getElementById('remove_logo')
        if (removeLogoInput) {
            removeLogoInput.value = '0'
            if (window?.DEV_MODE === true) console.log('remove_logo reset to 0 because new image was selected')
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

// The temporary fix that was here has been replaced by the structured handler functions above

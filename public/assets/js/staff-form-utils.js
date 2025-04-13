/**
 * Staff form utilities
 * Shared JavaScript functions for staff add/edit forms
 */

/**
 * Updates the department color preview based on the selected department
 * @param {HTMLSelectElement} departmentSelect - The department select element
 * @param {HTMLElement} colorPreviewElement - The element to show the color preview in
 */
function updateDepartmentColorPreview(departmentSelect, colorPreviewElement) {
    const selectedOption = departmentSelect.options[departmentSelect.selectedIndex]
    const color = selectedOption.getAttribute('data-color')
    const deptName = selectedOption.textContent.trim()

    if (color && deptName) {
        const hex = color.replace('#', '')
        const r = parseInt(hex.substr(0, 2), 16)
        const g = parseInt(hex.substr(2, 2), 16)
        const b = parseInt(hex.substr(4, 2), 16)
        const luminance = ((r * 299) + (g * 587) + (b * 114)) / 1000
        const textColor = (luminance > 150) ? 'dark-text' : 'light-text'

        colorPreviewElement.innerHTML = `
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${textColor}" style="background-color: ${color}; --dept-color: ${color}">
                Selected: ${deptName}
            </span>
        `
        colorPreviewElement.style.display = 'block'
    } else {
        colorPreviewElement.style.display = 'none'
    }
}

/**
 * Generates a URL for a placeholder image based on name and department color
 * @param {string} firstName - First name for placeholder
 * @param {string} lastName - Last name for placeholder
 * @param {HTMLSelectElement} departmentSelect - Department select element (for color)
 * @param {string} defaultImagePath - Default image to use if names are empty
 * @param {string} size - Size of the placeholder image (e.g., '150x150')
 * @returns {string} URL for placeholder image
 */
function getPlaceholderImageUrl(firstName, lastName, departmentSelect, defaultImagePath, size = '150x150') {
    const selectedOption = departmentSelect.options[departmentSelect.selectedIndex]
    const color = selectedOption?.getAttribute('data-color') || '#cccccc'
    const fName = firstName?.value || ''
    const lName = lastName?.value || ''

    // Only generate a placeholder with initials if we have a name
    if (!fName && !lName) {
        return defaultImagePath
    }

    const nameParam = `${fName.trim()} ${lName.trim()}`
    const timestamp = new Date().getTime()
    // Use relative path without APP_BASE_URI prefix since the router will handle it
    return window.APP_BASE_URI + `/includes/generate_placeholder.php?name=${encodeURIComponent(nameParam)}&size=${size}&bg_color=${encodeURIComponent(color)}&t=${timestamp}`
}

/**
 * Handles file selection for profile picture uploads
 * @param {File} file - The selected file
 * @param {HTMLImageElement} imagePreview - The image preview element
 * @param {HTMLElement} removeButton - The button to remove the image
 * @returns {boolean} Whether the file was valid and processed
 */
function handleFileSelection(file, imagePreview, removeButton = null) {
    if (file) {
        if (!file.type.match('image.*')) {
            alert('Please upload an image file')
            return false
        }

        if (file.size > 10 * 1024 * 1024) {
            alert('File is too large. Maximum size is 10MB')
            return false
        }

        const reader = new FileReader()
        reader.onload = function(e) {
            imagePreview.src = e.target.result
            imagePreview.dataset.isPlaceholder = 'false'
            if (removeButton) {
                removeButton.style.display = 'block'
            }
        }
        reader.readAsDataURL(file)
        return true
    }
    return false
}

/**
 * Prevent default events for drag and drop
 * @param {Event} e - The event to prevent defaults on
 */
function preventDefaults(e) {
    e.preventDefault()
    e.stopPropagation()
}

/**
 * Sets up drag and drop functionality for file uploads
 * @param {HTMLElement} dropzone - The dropzone element
 * @param {HTMLInputElement} fileInput - The file input element
 * @param {HTMLImageElement} imagePreview - The image preview element
 * @param {HTMLElement} removeButton - The remove button element
 */
function setupDragAndDrop(dropzone, fileInput, imagePreview, removeButton = null) {
    // Prevent defaults for all drag events
    ;['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false)
        document.body.addEventListener(eventName, preventDefaults, false)
    })

    // Add styling for dragenter
    dropzone.addEventListener('dragenter', function() {
        dropzone.classList.remove('border-gray-300')
        dropzone.classList.add('border-indigo-500')
    })

    // Remove styling for dragleave
    dropzone.addEventListener('dragleave', function(e) {
        if (!dropzone.contains(e.relatedTarget)) {
            dropzone.classList.remove('border-indigo-500')
            dropzone.classList.add('border-gray-300')
        }
    })

    // Handle drop event
    dropzone.addEventListener('drop', function(e) {
        preventDefaults(e)
        dropzone.classList.remove('border-indigo-500')
        dropzone.classList.add('border-gray-300')

        const dt = e.dataTransfer
        const files = dt.files

        if (files.length > 0) {
            if (handleFileSelection(files[0], imagePreview, removeButton)) {
                // Update file input to reflect dropped file if it's valid
                const dataTransfer = new DataTransfer()
                dataTransfer.items.add(files[0])
                fileInput.files = dataTransfer.files
            }
        }
    })
}

/**
 * Debounce function to limit repeated calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout
    return function() {
        const context = this
        const args = arguments
        clearTimeout(timeout)
        timeout = setTimeout(() => func.apply(context, args), wait)
    }
}
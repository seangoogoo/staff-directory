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
        const debouncedFilterStaff = debounce(filterStaff, 600)
        searchInput.addEventListener('input', debouncedFilterStaff)
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
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const selectedDepartment = departmentFilter ? departmentFilter.value : '';
        const selectedCompany = companyFilter ? companyFilter.value : '';

        // Get all staff cards
        const staffCards = document.querySelectorAll('.staff-card');

        // First, determine which cards should be visible based on filters
        const newVisibleCards = Array.from(staffCards).filter(card => {
            const name = card.querySelector('.staff-name').textContent.toLowerCase();
            const job = card.querySelector('.staff-job').textContent.toLowerCase();
            const department = card.querySelector('.staff-department').textContent.toLowerCase();
            const company = card.querySelector('.company-name')?.textContent.toLowerCase() || '';

            const matchesSearch = name.includes(searchTerm) || job.includes(searchTerm);
            const matchesDepartment = selectedDepartment === '' || department.includes(selectedDepartment.toLowerCase());
            const matchesCompany = selectedCompany === '' || company.includes(selectedCompany.toLowerCase());

            return matchesSearch && matchesDepartment && matchesCompany;
        });

        // Compare with currently visible cards
        const currentlyVisibleCards = Array.from(staffCards).filter(card =>
            card.style.display !== 'none' && card.classList.contains('card-visible')
        );

        // Check if the visible cards have actually changed by comparing the actual elements
        const hasChanged =
            currentlyVisibleCards.length !== newVisibleCards.length ||
            !currentlyVisibleCards.every(card => newVisibleCards.includes(card)) ||
            !newVisibleCards.every(card => currentlyVisibleCards.includes(card));

        if (hasChanged) {
            // Only kill and reset if we need to change visibility
            ScrollAnimator.killAllInstances();

            document.body.style.overflow = 'clip';

            // Hide all cards first
            staffCards.forEach(card => {
                card.style.transitionDelay = '0ms';
                card.classList.remove('card-visible');

                if (newVisibleCards.includes(card)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Only animate if we have visible cards
            if (newVisibleCards.length > 0) {
                setTimeout(() => {
                    staffCards.forEach(card => {
                        card.style.transitionDelay = '';
                    });

                    const scrollAnimator = new ScrollAnimator({
                        selector: '.staff-card:not([style*="display: none"])',
                        delay: 65,
                        batchThreshold: 20,
                        threshold: 0.35,
                        visibleClass: 'card-visible'
                    });
                    document.body.style.overflow = '';

                    scrollAnimator.revealAboveViewportOnLoad();
                }, 200);
            } else {
                document.body.style.overflow = '';
            }
        }
    }

    function sortStaff() {
        const sortBy = sortSelect.value
        const staffCards = Array.from(document.querySelectorAll('.staff-card'))

        // Only consider currently visible cards
        const currentlyVisibleCards = Array.from(staffCards).filter(card =>
            card.style.display !== 'none' && card.classList.contains('card-visible')
        )

        // Get current order before sorting
        const currentOrder = [...currentlyVisibleCards]

        // Sort only the visible cards
        const sortedVisibleCards = [...currentlyVisibleCards].sort((a, b) => {
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

        // Compare the arrays element by element to see if the order is actually different
        const hasChanged = currentOrder.some((card, index) => card !== sortedVisibleCards[index])

        if (hasChanged) {
            // Kill existing animations
            ScrollAnimator.killAllInstances()

            document.body.style.overflow = 'clip'

            // Reset animation states
            staffCards.forEach(card => {
                card.style.transitionDelay = '0ms'
                card.classList.remove('card-visible')
            })

            // Reappend the sorted cards to the grid
            sortedVisibleCards.forEach(card => {
                staffGrid.appendChild(card)
            })

            // Only animate if we have cards
            if (sortedVisibleCards.length > 0) {
                setTimeout(() => {
                    staffCards.forEach(card => {
                        card.style.transitionDelay = ''
                    })

                    const scrollAnimator = new ScrollAnimator({
                        selector: '.staff-card:not([style*="display: none"])',
                        delay: 65,
                        batchThreshold: 20,
                        threshold: 0.35,
                        visibleClass: 'card-visible'
                    })
                    document.body.style.overflow = ''

                    scrollAnimator.revealAboveViewportOnLoad()
                }, 200)
            } else {
                document.body.style.overflow = ''
            }
        }
    }

    //! this has nothing to do here
    // Delete confirmation for admin
    // const deleteButtons = document.querySelectorAll('.outline-danger')
    // if (deleteButtons) {
    //     deleteButtons.forEach(button => {
    //         button.addEventListener('click', function(e) {
    //             if (!confirm('Are you sure you want to delete this staff member?')) {
    //                 e.preventDefault()
    //             }
    //         })
    //     })
    // }
})

function debounce(func, wait) {
    let timeout
    return function(...args) {
        const context = this
        clearTimeout(timeout)
        timeout = setTimeout(() => func.apply(context, args), wait)
    };
}

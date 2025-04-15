'use strict'

/**
 * @class ScrollAnimator
 * @description Handles scroll-triggered animations for selected elements using Intersection Observer.
 * Applies sequential delays within time-based batches. Also includes a method
 * to instantly reveal elements already above the viewport on load.
 */
class ScrollAnimator {
	static instances = [];

	/**
	 * Initializes the ScrollAnimator.
	 * @param {object} config - Configuration object.
	 * @param {string} config.selector - The CSS selector for elements to animate.
	 * @param {number} config.delay - The base delay in milliseconds between items appearing in the same batch.
	 * @param {number} [config.batchThreshold=100] - Time in ms. If the time between two intersections is greater than this, the batch counter resets.
	 * @param {Element|null} [config.root=null] - The element used as the viewport for checking visibility (null = browser viewport).
	 * @param {string} [config.rootMargin='0px'] - Margin around the root, affecting intersection calculation (e.g., '0px', '-50px 0px').
	 * @param {number|number[]} [config.threshold=0.2] - Visibility percentage(s) (0.0-1.0) required to trigger the callback.
	 * @param {string} [config.visibleClass='card-visible'] - The CSS class to add to the element when it becomes visible to trigger the animation.
	 */
	constructor({
		selector,
		delay,
		batchThreshold = 100,
		root = null,
		rootMargin = '0px',
		threshold = 0.2,
		visibleClass = 'card-visible'
	}) {
		// Add this instance to the static tracking array
		ScrollAnimator.instances.push(this);

		// Store configuration parameters.
		this.selector = selector
		// Query elements immediately. Assumes elements exist or will exist shortly after instantiation.
		this.elementsToAnimate = document.querySelectorAll(selector)
		this.baseDelay = delay
		this.batchThreshold = batchThreshold
		this.visibleClass = visibleClass
		this.batchCounter = 0
		this.lastObservationTime = 0
		this.observer = null // Example from user

		// Kill any existing observer before creating a new one
		if (this.observer) {
			this.killObserver()
		}

		// Configure the IntersectionObserver options based on constructor parameters.
		this.observerOptions = {
			root: root,
			rootMargin: rootMargin,
			threshold: threshold
		}

		// Warn if no elements were found initially. They might be generated later.
		if (this.elementsToAnimate.length === 0) {
			console.warn(`ScrollAnimator Warning: No elements found matching selector "${selector}" at instantiation time.`)
			return
		}

		// Initialize the Intersection Observer.
		this.initObserver()
		// Start observing the selected elements (if any exist now).
		this.observeElements()
	}

	/**
	 * @method revealAboveViewportOnLoad
	 * @description Instantly reveals elements managed by this animator that are currently above the viewport.
	 * Should be called once after elements are guaranteed to be in the DOM and potentially laid out.
	 * Uses the selector and visibleClass configured for this instance.
	 */
	revealAboveViewportOnLoad() {
		// Re-query elements here in case they were added after constructor ran.
		// const elements = document.querySelectorAll(this.selector)
		// console.log(`Checking ${elements.length} elements for reveal.`); // Debug log

		this.elementsToAnimate.forEach(element => {
			const rect = element.getBoundingClientRect()
			// Check if the element's bottom edge is above the viewport's top edge.
			if (rect.bottom < 0) {
				// console.log('Revealing element above viewport:', element); // Debug log
				// Temporarily disable transitions.
				element.style.transition = 'none'
				// Add the visible class configured for this instance.
				element.classList.add(this.visibleClass)

				// Queue the restoration of the transition.
				setTimeout(() => {
					element.style.transition = ''
				}, 0)
			}
		})
	}

	/**
	 * @method observerCallback
	 * @description The function executed by the IntersectionObserver when an element's visibility changes.
	 * Handles batching logic and applies animation class and delay.
	 * @param {IntersectionObserverEntry[]} entries - Array of intersection entries.
	 * @param {IntersectionObserver} observer - The observer instance.
	 */
	observerCallback = (entries, observer) => {
		// Get the current high-resolution timestamp.
		const currentTime = performance.now()

		// Process each intersection entry.
		entries.forEach(entry => {
			// Check if the element is currently intersecting according to the threshold.
			if (entry.isIntersecting) {
				const element = entry.target // The DOM element that intersected.

				// Check if the time since the last observed element exceeds the batch threshold.
				if (currentTime - this.lastObservationTime > this.batchThreshold) {
					// If it exceeds, reset the batch counter, starting a new batch.
					this.batchCounter = 0
				}

				// Calculate the animation delay for this element based on its position in the current batch.
				const delay = this.batchCounter * this.baseDelay
				// Apply the calculated delay as an inline style.
				element.style.transitionDelay = `${delay}ms`
				// Add the configured visible class to trigger the CSS transition/animation.
				element.classList.add(this.visibleClass)

				// Increment the counter for the next element in this batch.
				this.batchCounter++
				// Update the timestamp of the last observation.
				this.lastObservationTime = currentTime

				// Stop observing this element once its animation has been triggered.
				observer.unobserve(element)
			}
		})
	}

	/**
	* @method initObserver
	* @description Creates and initializes the IntersectionObserver instance with the configured options and callback.
	*/
	initObserver() {
		this.observer = new IntersectionObserver(this.observerCallback, this.observerOptions)
	}

	/**
	* @method killObserver
	* @description Disconnects the IntersectionObserver, effectively stopping it from observing any elements.
	*/
	killObserver() {
		if (this.observer) {
			this.observer.disconnect()
			this.resetElements()
			this.observer = null
		}
	}

	/**
	 * @method observeElements
	 * @description Iterates through the selected elements (queried at instantiation)
	 * and starts observing each one with the IntersectionObserver.
	 */
	observeElements() {
		// Use the elements queried during construction.
		this.elementsToAnimate.forEach(element => {
			// Ensure the observer was successfully created before trying to observe.
			if (this.observer) {
				this.observer.observe(element)
			}
		})
	}

	// Add method to reset elements to initial state
	resetElements() {
		this.elementsToAnimate.forEach(element => {
			element.classList.remove(this.visibleClass)
			element.style.transitionDelay = ''
		})
	}

	static killAllInstances() {
		ScrollAnimator.instances.forEach(instance => {
			instance.killObserver();
		});
		ScrollAnimator.instances = [];
	}
}

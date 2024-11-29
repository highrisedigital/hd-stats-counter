document.addEventListener("DOMContentLoaded", function () {
	// Get all counter elements
	const counterElements = document.querySelectorAll('[data-counter-duration]');
	const startValue = 0;
  
	// Ease-out function to make the count slow down towards the end
	function easeOut(t) {
		return 1 - Math.pow(1 - t, 3); // Cubic easing (ease-out)
	}
  
	function animateCounter(counterElement, targetNumber, counterDuration) {
		const startTime = performance.now();
  
		function updateCounter(currentTime) {
			const elapsedTime = currentTime - startTime;
			const progress = Math.min(elapsedTime / counterDuration, 1); // 0 to 1
			const easedProgress = easeOut(progress);
			const currentValue = Math.floor(startValue + (targetNumber - startValue) * easedProgress);
  
			counterElement.textContent = currentValue;
  
			// Continue animating until complete
			if (progress < 1) {
				requestAnimationFrame(updateCounter);
			}
		}
  
		requestAnimationFrame(updateCounter);
	}
  
	// Create an IntersectionObserver to trigger animation when the element is in view
	const observer = new IntersectionObserver((entries, observer) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
  
				// Get the target number from the original value of the counter element
				const targetNumber = parseInt(entry.target.textContent, 10) || 0;
  
				// Add the in-view class
				entry.target.classList.add('in-view');

				// get the current counter duration attr.
				const counterDuration = entry.target.getAttribute('data-counter-duration');

				// Start the animation for this element
				animateCounter(entry.target, targetNumber, counterDuration);
  
				// Unobserve the element once animation starts
				observer.unobserve(entry.target);
			}
		});
	}, { threshold: 0.1 });
  
	// Observe each counter element
	counterElements.forEach(counterElement => {
		observer.observe(counterElement);
	});
});
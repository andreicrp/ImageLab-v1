/**
 * ImageLab Before/After Comparison Slider (Phase 4)
 * Handles interactive split-screen comparison drag controls
 */
const ImageLabComparison = {
    container: null,
    divider: null,
    afterImg: null,
    isDragging: false,

    init(containerId) {
        if (this.initialized && this.container && this.container.id === containerId) {
            return;
        }
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.divider = this.container.querySelector('.comparison-divider');
        this.afterImg = this.container.querySelector('.comparison-image-after');

        if (!this.divider || !this.afterImg) return;

        this.bindEvents();
        this.setSliderPosition(50); // Set to middle initially
        this.initialized = true;
    },

    bindEvents() {
        const startDrag = (e) => {
            this.isDragging = true;
            e.preventDefault();
        };

        const stopDrag = () => {
            this.isDragging = false;
        };

        const onDrag = (e) => {
            if (!this.isDragging) return;

            const rect = this.container.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const offsetX = clientX - rect.left;
            let percentage = (offsetX / rect.width) * 100;

            // Constrain between 0% and 100%
            if (percentage < 0) percentage = 0;
            if (percentage > 100) percentage = 100;

            this.setSliderPosition(percentage);
        };

        // Mouse Events
        this.divider.addEventListener('mousedown', startDrag);
        window.addEventListener('mouseup', stopDrag);
        window.addEventListener('mousemove', onDrag);

        // Touch Events
        this.divider.addEventListener('touchstart', startDrag);
        window.addEventListener('touchend', stopDrag);
        window.addEventListener('touchmove', onDrag);
    },

    setSliderPosition(percentage) {
        if (!this.divider || !this.afterImg) return;
        
        // Update divider position
        this.divider.style.left = `${percentage}%`;
        
        // Update image clipping path
        this.afterImg.style.clipPath = `polygon(0 0, ${percentage}% 0, ${percentage}% 100%, 0 100%)`;
    },

    updateImages(beforeUrl, afterUrl) {
        if (!this.container) return;
        const beforeImg = this.container.querySelector('.comparison-image-before');
        const afterImg = this.container.querySelector('.comparison-image-after img');

        if (beforeImg) beforeImg.src = beforeUrl;
        if (afterImg) afterImg.src = afterUrl;

        this.setSliderPosition(50); // Reset to center
    }
};

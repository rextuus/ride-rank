
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('Loading controller connected!');
    }

    stop() {
        this.hideImages();
        this.showLoadingOverlay();

        // Wait a fixed 2 seconds for everything to load and settle
        setTimeout(() => {
            this.hideLoadingOverlay();
        }, 2000);
    }

    hideImages() {
        const images = this.element.querySelectorAll('img.card-img-top');
        images.forEach(img => {
            img.classList.add('is-loading');
        });
    }

    showImages() {
        const images = this.element.querySelectorAll('img.card-img-top');
        images.forEach(img => {
            img.classList.remove('is-loading');
        });
    }

    showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;

        this.element.style.position = 'relative';
        this.element.appendChild(overlay);
        this.loadingOverlay = overlay;
    }

    hideLoadingOverlay() {
        if (this.loadingOverlay) {
            this.loadingOverlay.remove();
        }

        // Show images with smooth fade-in
        this.showImages();
    }
}

import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static values = {
        image: String,
        title: String,
    };

    open(event) {
        event.preventDefault();
        event.stopPropagation();

        const imageUrl = this.imageValue;
        const title = this.titleValue;

        console.log('Modal opening with:', { imageUrl, title });

        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content bg-dark">
                        <div class="modal-header bg-dark border-secondary">
                            <h5 class="modal-title text-light">${title || 'Image'}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img src="${imageUrl}" class="img-fluid" alt="${title}" style="max-height: 70vh; object-fit: contain; width: 100%;">
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Create temporary container
        const tempContainer = document.createElement('div');
        tempContainer.innerHTML = modalHtml;
        const modalElement = tempContainer.firstElementChild;
        document.body.appendChild(modalElement);

        // Show modal
        const modal = new Modal(modalElement);
        modal.show();

        // Clean up after modal is hidden
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
    }
}
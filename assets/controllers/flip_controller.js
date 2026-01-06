import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['front', 'back'];

    toggle() {
        this.element.classList.toggle('flipped');
    }

    show(side) {
        this.element.classList.toggle('flipped', side === 'back');
    }
}
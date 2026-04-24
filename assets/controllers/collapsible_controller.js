import { Controller } from '@hotwired/stimulus';

/** Expand/collapse panel (Shadcn-style Collapsible; see https://ux.symfony.com/toolkit/kits/shadcn/components/collapsible ). */
export default class extends Controller {
    static targets = ['trigger', 'content'];

    static values = {
        open: { type: Boolean, default: false },
    };

    connect() {
        this.openValueChanged();
    }

    toggle(event) {
        event?.preventDefault();
        this.openValue = !this.openValue;
    }

    openValueChanged() {
        const on = this.openValue;
        if (this.hasContentTarget) {
            this.contentTarget.classList.toggle('hidden', !on);
            this.contentTarget.toggleAttribute('hidden', !on);
        }
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', on ? 'true' : 'false');
        }
    }
}

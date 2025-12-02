import { Controller } from "stimulus";
import Sortable from "sortablejs";

export default class extends Controller {
    static targets = ["tbody"];
    static values = {
        rockId: Number,
        reorderUrl: String
    }

    connect() {
        if (this.hasTbodyTarget && typeof Sortable !== 'undefined') {
            this.sortable = new Sortable(this.tbodyTarget, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: (evt) => {
                    this.handleSortEnd();
                }
            });
        }
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }

    handleSortEnd() {
        const routeIds = Array.from(this.tbodyTarget.querySelectorAll('tr[data-route-id]')).map(tr => {
            return parseInt(tr.getAttribute('data-route-id'));
        });
        
        fetch(this.reorderUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ routeIds: routeIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the displayed numbers (nr is now 3rd column, topoId is 2nd)
                this.tbodyTarget.querySelectorAll('tr').forEach((tr, index) => {
                    const nrCell = tr.querySelector('td:nth-child(3)');
                    if (nrCell) {
                        nrCell.textContent = index + 1;
                        tr.setAttribute('data-route-nr', index + 1);
                    }
                });
                
                // Show success message
                this.showSuccessMessage();
            } else {
                alert('Fehler beim Speichern der Reihenfolge: ' + (data.message || 'Unbekannter Fehler'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Speichern der Reihenfolge');
        });
    }

    showSuccessMessage() {
        // Remove existing alerts
        const existingAlert = this.element.querySelector('.alert-success');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create and show success message
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show mt-2';
        alert.innerHTML = '<strong>Erfolg!</strong> Reihenfolge wurde gespeichert.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        this.element.prepend(alert);
        
        setTimeout(() => alert.remove(), 3000);
    }
}


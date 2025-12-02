import { Controller } from "stimulus";
import Sortable from "sortablejs";

export default class extends Controller {
    static targets = ["tbody"];
    static values = {
        rockId: Number,
        reorderUrl: String
    }

    connect() {
        if (!this.hasTbodyTarget) {
            console.error('[RoutesSortable] Tbody target not found. Cannot initialize sortable.');
            return;
        }

        if (typeof Sortable === 'undefined') {
            console.error('[RoutesSortable] SortableJS library is not loaded. Please ensure sortablejs is installed and imported.');
            this.showInitializationError('SortableJS-Bibliothek konnte nicht geladen werden.');
            return;
        }

        try {
            this.sortable = new Sortable(this.tbodyTarget, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: (evt) => {
                    this.handleSortEnd();
                },
                onError: (evt) => {
                    console.error('[RoutesSortable] Sortable error:', evt);
                }
            });

            if (!this.sortable) {
                throw new Error('Sortable initialization returned null/undefined');
            }

            console.log('[RoutesSortable] Successfully initialized drag-and-drop for routes');
        } catch (error) {
            console.error('[RoutesSortable] Failed to initialize Sortable:', error);
            this.showInitializationError('Drag-and-Drop konnte nicht initialisiert werden: ' + error.message);
        }
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }

    handleSortEnd() {
        if (!this.hasTbodyTarget) {
            console.error('[RoutesSortable] Tbody target not found during sort end');
            return;
        }

        const routeIds = Array.from(this.tbodyTarget.querySelectorAll('tr[data-route-id]')).map(tr => {
            const routeId = tr.getAttribute('data-route-id');
            const parsed = parseInt(routeId);
            if (isNaN(parsed)) {
                console.warn('[RoutesSortable] Invalid route ID:', routeId);
            }
            return parsed;
        }).filter(id => !isNaN(id));

        if (routeIds.length === 0) {
            console.error('[RoutesSortable] No valid route IDs found');
            alert('Fehler: Keine gültigen Routen-IDs gefunden.');
            return;
        }

        if (!this.reorderUrlValue) {
            console.error('[RoutesSortable] Reorder URL not set');
            alert('Fehler: Reorder-URL ist nicht konfiguriert.');
            return;
        }

        console.log('[RoutesSortable] Saving new route order:', routeIds);

        fetch(this.reorderUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ routeIds: routeIds })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
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
                
                console.log('[RoutesSortable] Successfully saved route order');
                // Show success message
                this.showSuccessMessage();
            } else {
                const errorMsg = data.message || 'Unbekannter Fehler';
                console.error('[RoutesSortable] Server returned error:', errorMsg);
                alert('Fehler beim Speichern der Reihenfolge: ' + errorMsg);
            }
        })
        .catch(error => {
            console.error('[RoutesSortable] Network or parsing error:', error);
            alert('Fehler beim Speichern der Reihenfolge: ' + error.message);
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

    showInitializationError(message) {
        // Remove existing error alerts
        const existingAlert = this.element.querySelector('.alert-danger');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create and show error message
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show mt-2';
        alert.innerHTML = '<strong>Fehler!</strong> ' + message + ' <small>(Drag-and-Drop ist nicht verfügbar)</small><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        this.element.prepend(alert);
    }
}


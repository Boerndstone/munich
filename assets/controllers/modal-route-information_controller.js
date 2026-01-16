import { Controller } from "stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  static targets = ["modal", "title", "content"];

  openModal(event) {
    // Get the button that was clicked
    const button = event.currentTarget;
    
    // Read values from the button's data attributes
    const name = button.dataset.modalRouteInformationNameValue || '';
    const grade = button.dataset.modalRouteInformationGradeValue || '';
    const firstAscent = button.dataset.modalRouteInformationFirstAscentValue || '';
    const yearFirstAscent = button.dataset.modalRouteInformationYearFirstAscentValue || '';
    
    let comments = [];
    try {
      const commentsData = button.dataset.modalRouteInformationCommentsValue;
      if (commentsData) {
        comments = JSON.parse(commentsData);
      }
    } catch (e) {
      console.error('Error parsing comments:', e);
    }

    // Populate the shared modal with route-specific data
    if (this.hasTitleTarget) {
      this.titleTarget.textContent = `${name} (${grade})`;
    }

    if (this.hasContentTarget) {
      let html = '';
      
      // First ascent info (mobile)
      if (firstAscent || yearFirstAscent) {
        html += `<p class="fw-medium mb-0 d-lg-none stay-black">`;
        if (firstAscent) {
          html += `Erstbegeher: ${firstAscent} `;
        }
        if (yearFirstAscent) {
          html += yearFirstAscent;
        }
        html += `</p>`;
      }
      
      // Comments
      if (comments && comments.length > 0) {
        comments.forEach((commentData, index) => {
          html += `<p class="mt-2 text-sm fw-normal stay-black">${commentData.comment || ''}</p>`;
          if (commentData.username) {
            html += `<p class="mt-2 fst-italic text-sm fw-normal stay-black">`;
            html += commentData.username;
            if (commentData.date) {
              const date = new Date(commentData.date);
              html += ` ${date.toLocaleDateString('de-DE')}`;
            }
            html += `</p>`;
          }
          if (index < comments.length - 1) {
            html += `<hr/>`;
          }
        });
      }
      
      this.contentTarget.innerHTML = html;
    }

    if (this.hasModalTarget) {
      let modalInstance = Modal.getInstance(this.modalTarget);
      if (!modalInstance) {
        modalInstance = new Modal(this.modalTarget);
      }
      modalInstance.show();
    }
  }
}

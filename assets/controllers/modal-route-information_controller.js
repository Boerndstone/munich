import { Controller } from "stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  static targets = ["modal", "title", "content"];

  // Parse date from various formats (PHP DateTime object, ISO string, MySQL datetime)
  parseDate(dateValue) {
    if (!dateValue) return null;
    
    // If it's a PHP DateTime object serialized as JSON
    if (typeof dateValue === 'object' && dateValue.date) {
      return new Date(dateValue.date.replace(' ', 'T'));
    }
    
    // If it's a string, try to parse it
    if (typeof dateValue === 'string') {
      // Replace space with T for ISO compatibility (MySQL format: "2024-01-15 10:30:00")
      const isoString = dateValue.replace(' ', 'T');
      const parsed = new Date(isoString);
      if (!isNaN(parsed.getTime())) {
        return parsed;
      }
    }
    
    // Fallback: try direct parsing
    const fallback = new Date(dateValue);
    return isNaN(fallback.getTime()) ? null : fallback;
  }

  formatDate(dateValue) {
    const date = this.parseDate(dateValue);
    if (!date) return '';
    return date.toLocaleDateString('de-DE');
  }

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
              const formattedDate = this.formatDate(commentData.date);
              if (formattedDate) {
                html += ` ${formattedDate}`;
              }
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

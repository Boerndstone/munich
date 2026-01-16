import { Controller } from "stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  static targets = ["modal", "title", "content"];
  static values = {
    name: String,
    grade: String,
    firstAscent: String,
    yearFirstAscent: String,
    comments: Array
  };

  openModal(event) {
    // Populate the shared modal with route-specific data
    if (this.hasTitleTarget) {
      this.titleTarget.textContent = `${this.nameValue} (${this.gradeValue})`;
    }

    if (this.hasContentTarget) {
      let html = '';
      
      // First ascent info (mobile)
      if (this.firstAscentValue || this.yearFirstAscentValue) {
        html += `<p class="fw-medium mb-0 d-lg-none stay-black">`;
        if (this.firstAscentValue) {
          html += `Erstbegeher: ${this.firstAscentValue} `;
        }
        if (this.yearFirstAscentValue) {
          html += this.yearFirstAscentValue;
        }
        html += `</p>`;
      }
      
      // Comments
      if (this.commentsValue && this.commentsValue.length > 0) {
        this.commentsValue.forEach((commentData, index) => {
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
          if (index < this.commentsValue.length - 1) {
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

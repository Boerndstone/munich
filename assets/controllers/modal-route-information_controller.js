import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["modal", "title", "content"];

  parseDate(dateValue) {
    if (!dateValue) return null;

    if (typeof dateValue === 'object' && dateValue.date) {
      return new Date(dateValue.date.replace(' ', 'T'));
    }

    if (typeof dateValue === 'string') {
      const isoString = dateValue.replace(' ', 'T');
      const parsed = new Date(isoString);
      if (!isNaN(parsed.getTime())) {
        return parsed;
      }
    }

    const fallback = new Date(dateValue);
    return isNaN(fallback.getTime()) ? null : fallback;
  }

  formatDate(dateValue) {
    const date = this.parseDate(dateValue);
    if (!date) return '';
    return date.toLocaleDateString('de-DE');
  }

  openModal(event) {
    const button = event.currentTarget;

    const name = button.dataset.modalRouteInformationNameValue || '';
    const grade = button.dataset.modalRouteInformationGradeValue || '';
    const firstAscent = button.dataset.modalRouteInformationFirstAscentValue || '';
    const yearFirstAscentRaw = button.dataset.modalRouteInformationYearFirstAscentValue;
    const yearFirstAscent = (yearFirstAscentRaw && yearFirstAscentRaw !== '0') ? yearFirstAscentRaw : '';

    let comments = [];
    try {
      const commentsData = button.dataset.modalRouteInformationCommentsValue;
      if (commentsData) {
        comments = JSON.parse(commentsData);
      }
    } catch (e) {
      console.error('Error parsing comments:', e);
    }

    if (this.hasTitleTarget) {
      this.titleTarget.textContent = `${name} (${grade})`;
    }

    if (this.hasContentTarget) {
      let html = '';

      if (firstAscent || yearFirstAscent) {
        html += `<p class="mb-0 text-sm font-medium text-gray-900 lg:hidden dark:text-gray-100">`;
        if (firstAscent) {
          html += `Erstbegeher: ${firstAscent} `;
        }
        if (yearFirstAscent) {
          html += yearFirstAscent;
        }
        html += `</p>`;
      }

      if (comments && comments.length > 0) {
        comments.forEach((commentData, index) => {
          html += `<p class="mt-2 text-sm font-normal text-gray-800 dark:text-gray-200">${commentData.comment || ''}</p>`;
          if (commentData.username) {
            html += `<p class="mt-2 text-sm font-normal italic text-gray-600 dark:text-gray-400">`;
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
            html += `<hr class="my-2 border-gray-200 dark:border-gray-700"/>`;
          }
        });
      }

      this.contentTarget.innerHTML = html;
    }

    if (this.hasModalTarget && typeof this.modalTarget.showModal === 'function') {
      this.modalTarget.showModal();
    }
  }
}

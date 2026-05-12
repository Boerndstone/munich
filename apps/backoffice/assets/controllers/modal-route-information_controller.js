import { Controller } from "stimulus";
import {
  getGradeDisplayMode,
  labelForPreference,
} from "../js/climbing_grade_display";

export default class extends Controller {
  static targets = ["modal", "title", "content"];

  parseDate(dateValue) {
    if (!dateValue) return null;

    if (typeof dateValue === "object" && dateValue.date) {
      return new Date(dateValue.date.replace(" ", "T"));
    }

    if (typeof dateValue === "string") {
      const isoString = dateValue.replace(" ", "T");
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
    if (!date) return "";
    return date.toLocaleDateString("de-DE");
  }

  openModal(event) {
    const button = event.currentTarget;

    const name = button.dataset.modalRouteInformationNameValue || "";
    const stored = button.dataset.modalRouteInformationGradeStoredValue || "";
    const gradeFixed = button.dataset.modalRouteInformationGradeFixedValue === "1";
    const grade = gradeFixed
      ? stored
      : labelForPreference(stored, getGradeDisplayMode());
    const firstAscent = button.dataset.modalRouteInformationFirstAscentValue || "";
    const yearFirstAscentRaw = button.dataset.modalRouteInformationYearFirstAscentValue;
    const yearFirstAscent =
      yearFirstAscentRaw && yearFirstAscentRaw !== "0" ? yearFirstAscentRaw : "";

    let comments = [];
    try {
      const commentsJson =
        button.getAttribute("data-modal-route-information-comments-value") || "";
      if (commentsJson) {
        comments = JSON.parse(commentsJson);
      }
    } catch (e) {
      console.error("Error parsing comments:", e);
    }

    if (this.hasTitleTarget) {
      this.titleTarget.textContent = `${name} (${grade})`;
    }

    if (this.hasContentTarget) {
      let html = "";

      if (firstAscent || yearFirstAscent) {
        // Modal body: show on all breakpoints (table already hides this column on small screens).
        html += `<p class="mb-0 text-sm font-medium">`;
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
          html += `<p class="mt-2 text-sm font-normal text-[var(--theme-text)]">${commentData.comment || ""}</p>`;
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
            html += `<hr class="my-3 border-0 border-t border-[var(--theme-border)]" />`;
          }
        });
      }

      this.contentTarget.innerHTML = html;
    }

    if (this.hasModalTarget) {
      const el = this.modalTarget;
      if (el instanceof HTMLDialogElement) {
        el.showModal();
      }
    }
  }

  closeModal() {
    if (this.hasModalTarget) {
      const el = this.modalTarget;
      if (el instanceof HTMLDialogElement) {
        el.close();
      }
    }
  }
}

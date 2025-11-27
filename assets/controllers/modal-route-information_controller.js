import { Controller } from "stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  static targets = ["modal"];

  openModal(event) {
    if (this.hasModalTarget) {
      // Get existing modal instance or create a new one
      let modalInstance = Modal.getInstance(this.modalTarget);
      if (!modalInstance) {
        modalInstance = new Modal(this.modalTarget);
      }
      modalInstance.show();
    }
  }
}

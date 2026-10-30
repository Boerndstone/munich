import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["lgItem"];

  connect() {
    this.galleryRoot = this.element;
    this.dialogContainer = this.galleryRoot.closest("dialog");
    this.accordionItem = this.galleryRoot.closest('[data-accordion-target="item"]');
    this._gallery = null;
    this._galleryLoading = null;
    this._hydrated = false;

    if (this.accordionItem) {
      this._openObserver = new MutationObserver(() => {
        if (this.accordionItem?.dataset.open === "true") {
          this._activateGallery();
        }
      });
      this._openObserver.observe(this.accordionItem, {
        attributes: true,
        attributeFilter: ["data-open"],
      });
    }

    if (this.accordionItem?.dataset.open === "true") {
      this._activateGallery();
    }
  }

  disconnect() {
    this._openObserver?.disconnect();
    this._gallery?.destroy?.();
    this._gallery = null;
    this._galleryLoading = null;
    this._openObserver = null;
    this.accordionItem = null;
    this.dialogContainer = null;
    this.galleryRoot = null;
  }

  async _activateGallery() {
    this._hydrateThumbs();

    if (this._gallery || this._galleryLoading) {
      return this._galleryLoading;
    }

    this._galleryLoading = Promise.all([
      import("lightgallery"),
      import("lightgallery/plugins/thumbnail"),
      import("lightgallery/plugins/zoom"),
      import("lightgallery/scss/lightgallery.scss"),
      import("lightgallery/scss/lg-thumbnail.scss"),
    ]).then(([{ default: lightGallery }, { default: lgThumbnail }, { default: lgZoom }]) => {
      if (!this.galleryRoot?.isConnected) {
        return;
      }

      this._gallery = lightGallery(this.galleryRoot, {
        selector: ".lg-item",
        // Render gallery UI in the same native dialog top-layer context.
        container: this.dialogContainer ?? document.body,
        plugins: [lgZoom, lgThumbnail],
        licenseKey: "162AFA5B-3E30-4993-830C-377547A29E8B",
      });
    }).finally(() => {
      this._galleryLoading = null;
    });

    return this._galleryLoading;
  }

  _hydrateThumbs() {
    if (this._hydrated) {
      return;
    }

    this.galleryRoot
      ?.querySelectorAll("img[data-gallery-thumb]")
      .forEach((image) => {
        if (!(image instanceof HTMLImageElement)) {
          return;
        }

        image.src = image.dataset.galleryThumb || image.src;
        delete image.dataset.galleryThumb;
      });

    this._hydrated = true;
  }
}

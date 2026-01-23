/**
 * Gallery entry point - only loaded on pages with image galleries
 * Contains lightGallery
 */
import { Application } from "@hotwired/stimulus";
import OffCanvasGalleryController from "./controllers/off-canvas-gallery_controller";

// Get or create the Stimulus application
const application = window.Stimulus || Application.start();
window.Stimulus = application;

// Register gallery controller
application.register("off-canvas-gallery", OffCanvasGalleryController);

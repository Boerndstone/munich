/**
 * Gallery entry point - only loaded on pages with image galleries
 * Contains lightGallery
 */
import { app } from "./bootstrap";
import OffCanvasGalleryController from "./controllers/off-canvas-gallery_controller";

// Reuse the shared Stimulus app initialized by the main frontend entry.
const application = window.Stimulus || app;

// Register gallery controller
application.register("off-canvas-gallery", OffCanvasGalleryController);

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.css";
import "./styles/global.scss";

// Initialize Bootstrap Offcanvas component for navigation
// This ensures the offcanvas navigation works on all pages
import { Offcanvas } from "bootstrap";

// Initialize all offcanvas elements when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  // Initialize all offcanvas elements that use data-bs-toggle="offcanvas"
  const offcanvasElements = document.querySelectorAll(".offcanvas");
  offcanvasElements.forEach((element) => {
    // Only initialize if not already initialized
    if (!Offcanvas.getInstance(element)) {
      new Offcanvas(element);
    }
  });
});

// start the Stimulus application
import "./bootstrap";

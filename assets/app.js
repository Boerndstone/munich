/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Tailwind (app.css) then site SCSS (Bootstrap + legacy) — one bundle avoids split CSS ordering issues.
import "./styles/app.css";
import "./styles/global.scss";

// Bootstrap JS (offcanvas, collapse, etc.) – required for data-bs-toggle on every page
import "bootstrap";

// start the Stimulus application
import "./bootstrap";

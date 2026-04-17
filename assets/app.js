/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Tailwind (app.css) then site SCSS (legacy + third-party) — one bundle avoids split CSS ordering issues.
import "./styles/app.css";
import "./styles/global.scss";

// Symfony Stimulus bridge (assets/bootstrap.js — not the Bootstrap CSS framework)
import "./bootstrap";

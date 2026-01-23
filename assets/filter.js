/**
 * Filter entry point - only loaded on rock listing pages
 */
import { Application } from "@hotwired/stimulus";
import FilterController from "./controllers/filter_controller";
import GradeFilterController from "./controllers/grade_filter_controller";

// Get or create the Stimulus application
const application = window.Stimulus || Application.start();
window.Stimulus = application;

// Register filter controllers
application.register("filter", FilterController);
application.register("grade_filter", GradeFilterController);

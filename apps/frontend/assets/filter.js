/**
 * Filter entry point - only loaded on rock listing pages
 */
import { app } from "./bootstrap";
import FilterController from "./controllers/filter_controller";
import GradeFilterController from "./controllers/grade_filter_controller";

// Reuse the shared Stimulus app initialized by the main frontend entry.
const application = window.Stimulus || app;

// Register filter controllers (rock-grade-chart lives in app/bootstrap for index + rocks)
application.register("filter", FilterController);
application.register("grade_filter", GradeFilterController);

import "leaflet/dist/leaflet.css";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import { app } from "../bootstrap";
import MapController from "../controllers/map_controller";

app.register("map", MapController);

import "leaflet/dist/leaflet.css";
import { app } from "../bootstrap";
import RockMapController from "../controllers/rock_map_controller";

app.register("rock-map", RockMapController);

import './styles/admin.css';
import './styles/admin-bootstrap-bridge.scss';
// EasyAdmin's bundled `app.js` already includes Bootstrap; do not import `bootstrap` again here
// (double init breaks components and can make the Symfony web debug toolbar misbehave).
import './bootstrap';

// Register admin-specific Stimulus controllers
import AdminDrawMapController from './controllers/admin_draw_map_controller';
import AdminRouteTopoSyncController from './controllers/admin_route_topo_sync_controller';

window.Stimulus.register('admin-draw-map', AdminDrawMapController);
window.Stimulus.register('admin-route-topo-sync', AdminRouteTopoSyncController);
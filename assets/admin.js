import './styles/admin.css';
import './bootstrap';

// Register admin-specific Stimulus controllers
import AdminDrawMapController from './controllers/admin_draw_map_controller';
window.Stimulus.register('admin-draw-map', AdminDrawMapController);
/**
 * Shared map marker UI for the homepage main map and the area (rocks) map.
 * Import from `assets/controllers/*_controller.js` as `../map/icons.js`.
 * Adjust pin SVG, bubble sizes, and rock cluster thresholds here only.
 */
import L from "leaflet";

/** Geo rock clusters (area map + main map rock layer): radius / when pins replace clusters */
export const ROCK_CLUSTER_MAX_RADIUS = 48;
export const ROCK_DISABLE_CLUSTERING_AT_ZOOM = 15;

const PIN_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 40" width="28" height="40" aria-hidden="true">' +
  '<path fill="#ef4444" stroke="#b91c1c" stroke-width="0.75" d="M14 .5C6.5.5.5 6.4.5 13.9c0 5.2 2.6 10.4 6.5 15.8 3.2 4.4 7 8.3 7 8.3s3.8-3.9 7-8.3c3.9-5.4 6.5-10.6 6.5-15.8C27.5 6.4 21.5.5 14 .5z"/>' +
  '<circle cx="14" cy="14" r="4.25" fill="#fff"/>' +
  "</svg>";

/** Create a new icon instance per marker — reusing one DivIcon across markers breaks clicks/popups. */
export function createMapPinIcon() {
  return L.divIcon({
    html: PIN_SVG,
    className: "main-map-pin-icon",
    iconSize: [28, 40],
    iconAnchor: [14, 40],
    popupAnchor: [0, -34],
  });
}

/** Blue glow bubble (cluster counts and per-area rock totals on main map) */
export function bubbleIconForCount(count) {
  const n = Math.max(0, Math.min(9999, Math.round(Number(count) || 0)));
  const d = n < 10 ? 52 : n < 100 ? 60 : n < 1000 ? 64 : 70;
  return L.divIcon({
    html: `<span class="main-map-cluster-count" style="--s:${d}px">${n}</span>`,
    className: "main-map-cluster-icon",
    iconSize: L.point(d, d),
    iconAnchor: L.point(d / 2, d / 2),
  });
}

/** Leaflet.markercluster: merged cluster shows number of child markers */
export function createMarkerCountClusterIcon(cluster) {
  return bubbleIconForCount(cluster.getChildCount());
}

/** Main map: merged area bubbles show sum of marker.options.rocksWithCoordinates */
export function createMergedAreaRockCountClusterIcon(cluster) {
  const markers = cluster.getAllChildMarkers();
  const total = markers.reduce((s, mm) => s + (Number(mm.options.rocksWithCoordinates) || 0), 0);
  return bubbleIconForCount(total);
}

/** Shared defaults for rock MarkerClusterGroup (main map + area map) */
export function rockMarkerClusterGroupOptions() {
  return {
    maxClusterRadius: ROCK_CLUSTER_MAX_RADIUS,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    disableClusteringAtZoom: ROCK_DISABLE_CLUSTERING_AT_ZOOM,
    iconCreateFunction: createMarkerCountClusterIcon,
  };
}

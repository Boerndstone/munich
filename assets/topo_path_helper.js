	(function() {
		function readTphI18n() {
			var el = document.getElementById('topo-path-helper-i18n');
			if (!el || !el.textContent) return {};
			try {
				return JSON.parse(el.textContent);
			} catch (e) {
				return {};
			}
		}

		var TPH_I18N = readTphI18n();

		function tphT(key, params) {
			var s = TPH_I18N[key];
			if (s == null || s === '') {
				s = key;
			}
			if (params && typeof params === 'object') {
				for (var k in params) {
					if (Object.prototype.hasOwnProperty.call(params, k)) {
						s = String(s).split('%' + k + '%').join(String(params[k]));
					}
				}
			}
			return s;
		}

		function escapeHtml(s) {
			var div = document.createElement('div');
			div.textContent = s;
			return div.innerHTML;
		}

		// --- Step 0: Draw on image ---
		var drawArea = document.getElementById('tph-drawArea');
		var drawImg = document.getElementById('tph-drawImg');
		var drawSvg = document.getElementById('tph-drawSvg');
		var drawnPaths = [];
		var currentPath = [];
		var drawW = 1024, drawH = 820;

		function getDrawImageUrl() {
			var file = document.getElementById('tph-drawImageFile').files[0];
			if (file) return URL.createObjectURL(file);
			var url = document.getElementById('tph-drawImageUrl').value.trim();
			return url || null;
		}

		function loadDrawImage() {
			var url = getDrawImageUrl();
			if (!url) {
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_need_url');
				return;
			}
			drawW = 1024;
			drawH = 820;
			drawImg.onload = function() {
				drawArea.style.display = 'block';
				drawArea.style.aspectRatio = drawW + ' / ' + drawH;
				drawSvg.setAttribute('viewBox', '0 0 ' + drawW + ' ' + drawH);
				drawSvg.setAttribute('width', '100%');
				drawSvg.setAttribute('height', '100%');
				drawSvg.style.pointerEvents = 'all';
				drawSvg.style.cursor = 'crosshair';
				drawSvg.classList.add('paths-editable');
				// Always use path list (pathsJson) for overlay — never inject server SVG here to avoid broken content in DOM
				syncDrawnPathsFromPaths();
				redrawOverlay();
				updateDrawPathButtons();
				requestAnimationFrame(function() {
					var count = drawSvg.querySelectorAll('path[id^="svg_"]').length || drawnPaths.length;
					document.getElementById('tph-drawStatus').textContent = count
						? tphT('draw_status_on_image', { count: count })
						: tphT('draw_status_start_routes');
				});
				refreshSuggestionRoutesFromServer();
				var afterBlk = document.getElementById('tph-suggestion-after-image-block');
				if (afterBlk && window.TOPO_EDIT && window.TOPO_EDIT.suggestionMode) {
					afterBlk.style.display = '';
				}
			};
			drawImg.onerror = function() {
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_image_error');
			};
			drawImg.src = url;
		}

		function svgCoords(ev) {
			var rect = drawSvg.getBoundingClientRect();
			var x = ((ev.clientX - rect.left) / rect.width) * drawW;
			var y = ((ev.clientY - rect.top) / rect.height) * drawH;
			return [ Math.round(x), Math.round(y) ];
		}

		function pathToD(pts) {
			if (pts.length === 0) return '';
			var d = 'm' + pts[0][0] + ',' + pts[0][1];
			for (var i = 1; i < pts.length; i++) {
				d += 'l' + (pts[i][0] - pts[i-1][0]) + ',' + (pts[i][1] - pts[i-1][1]);
			}
			return d;
		}

		function pathToPoints(d) {
			if (!d || typeof d !== 'string') return [];
			var points = [];
			var commands = d.trim().split(/(?=[mMlLcCsShHvVzZ])/).filter(Boolean);
			var x = 0, y = 0;
			for (var i = 0; i < commands.length; i++) {
				var cmd = commands[i].trim();
				if (!cmd) continue;
				var type = cmd.charAt(0);
				var rest = cmd.slice(1).replace(/^\s*,\s*|\s*,\s*/g, ',').trim();
				var values = rest ? rest.split(/[\s,]+/).map(parseFloat) : [];
				var j = 0;
				if (type === 'm' || type === 'M') {
					while (j + 1 < values.length) {
						if (type === 'm') { x += values[j]; y += values[j+1]; } else { x = values[j]; y = values[j+1]; }
						points.push([Math.round(x), Math.round(y)]); j += 2;
					}
				} else if (type === 'l' || type === 'L') {
					while (j + 1 < values.length) {
						if (type === 'l') { x += values[j]; y += values[j+1]; } else { x = values[j]; y = values[j+1]; }
						points.push([Math.round(x), Math.round(y)]); j += 2;
					}
				} else if (type === 'c' || type === 'C') {
					while (j + 5 < values.length) {
						if (type === 'c') { x += values[j+4]; y += values[j+5]; } else { x = values[j+4]; y = values[j+5]; }
						points.push([Math.round(x), Math.round(y)]); j += 6;
					}
				} else if (type === 's' || type === 'S') {
					while (j + 3 < values.length) {
						if (type === 's') { x += values[j+2]; y += values[j+3]; } else { x = values[j+2]; y = values[j+3]; }
						points.push([Math.round(x), Math.round(y)]); j += 4;
					}
				}
			}
			return points;
		}

		var selectedPathIndex = null;
		var editingPathPoints = null;
		var draggingPointIndex = null;
		var dragStart = null;

		function redrawOverlay() {
			var ns = 'http://www.w3.org/2000/svg';
			while (drawSvg.firstChild) drawSvg.removeChild(drawSvg.firstChild);
			drawnPaths.forEach(function(p, idx) {
				var color = p.color || '#E42522';
				var isSelected = selectedPathIndex === idx;
				var pathD = (isSelected && editingPathPoints && editingPathPoints.length >= 2) ? pathToD(editingPathPoints) : p.d;
				var path = document.createElementNS(ns, 'path');
				path.setAttribute('d', pathD);
				path.setAttribute('stroke', color);
				path.setAttribute('stroke-width', isSelected ? '3' : '2');
				path.setAttribute('fill', 'none');
				path.setAttribute('data-path-index', String(idx));
				path.setAttribute('class', 'tph-path' + (isSelected ? ' tph-path-selected' : ''));
				drawSvg.appendChild(path);
				if (p.dot && !(isSelected && editingPathPoints)) {
					var end = calculateEndpoint(pathD);
					var circle = document.createElementNS(ns, 'circle');
					circle.setAttribute('class', 'end-dot');
					circle.setAttribute('cx', end[0]);
					circle.setAttribute('cy', end[1]);
					circle.setAttribute('r', '7');
					circle.setAttribute('fill', '#fff');
					circle.setAttribute('stroke', '#000');
					circle.setAttribute('stroke-width', '1');
					drawSvg.appendChild(circle);
				}
				if (isSelected && editingPathPoints && editingPathPoints.length >= 2) {
					editingPathPoints.forEach(function(pt, ptIdx) {
						var circle = document.createElementNS(ns, 'circle');
						circle.setAttribute('class', 'point edit-point');
						circle.setAttribute('data-path-index', String(idx));
						circle.setAttribute('data-point-index', String(ptIdx));
						circle.setAttribute('cx', pt[0]);
						circle.setAttribute('cy', pt[1]);
						circle.setAttribute('r', '5');
						circle.setAttribute('fill', color);
						circle.setAttribute('stroke', '#fff');
						circle.setAttribute('stroke-width', '1');
						drawSvg.appendChild(circle);
					});
				}
			});
			if (currentPath.length >= 2) {
				var path = document.createElementNS(ns, 'path');
				path.setAttribute('d', pathToD(currentPath));
				path.setAttribute('stroke', '#E42522');
				path.setAttribute('stroke-width', '2');
				path.setAttribute('fill', 'none');
				drawSvg.appendChild(path);
			}
			currentPath.forEach(function(pt) {
				var circle = document.createElementNS(ns, 'circle');
				circle.setAttribute('class', 'point');
				circle.setAttribute('cx', pt[0]);
				circle.setAttribute('cy', pt[1]);
				circle.setAttribute('r', '4');
				circle.setAttribute('fill', '#E42522');
				circle.setAttribute('stroke', '#fff');
				circle.setAttribute('stroke-width', '1');
				drawSvg.appendChild(circle);
			});
		}

		function syncDrawnPathsFromPaths() {
			if (typeof paths !== 'undefined' && Array.isArray(paths) && paths.length >= 0) {
				drawnPaths = paths.map(function(p) {
					var d = (p && (p.d != null ? p.d : p.path)) || '';
					return { d: d, color: (p && p.color) || '#E42522', dot: !!(p && p.dot) };
				}).filter(function(p) { return p.d !== ''; });
			}
		}

		function updateDrawPathButtons() {
			var sel = selectedPathIndex !== null;
			document.getElementById('tph-drawDeselect').disabled = !sel;
			document.getElementById('tph-drawDeletePath').disabled = !sel;
		}

		function selectPath(index) {
			if (index < 0 || index >= drawnPaths.length) return;
			var pts = pathToPoints(drawnPaths[index].d);
			if (pts.length < 2) {
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_few_points');
				return;
			}
			selectedPathIndex = index;
			editingPathPoints = pts.map(function(p) { return [p[0], p[1]]; });
			redrawOverlay();
			updateDrawPathButtons();
			document.getElementById('tph-drawStatus').textContent = tphT('draw_status_path_selected', { pathNum: index + 1 });
		}

		function deselectPath() {
			if (selectedPathIndex === null) return;
			if (editingPathPoints && editingPathPoints.length >= 2) {
				var newD = pathToD(editingPathPoints);
				drawnPaths[selectedPathIndex].d = newD;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].d = newD;
			}
			selectedPathIndex = null;
			editingPathPoints = null;
			draggingPointIndex = null;
			pathsUiSync();
			updateDrawPathButtons();
			document.getElementById('tph-drawStatus').textContent = drawnPaths.length
				? tphT('draw_status_paths_click_edit', { count: drawnPaths.length })
				: tphT('draw_status_click_image');
		}

		function deleteSelectedPath() {
			if (selectedPathIndex === null) return;
			drawnPaths.splice(selectedPathIndex, 1);
			paths.splice(selectedPathIndex, 1);
			selectedPathIndex = null;
			editingPathPoints = null;
			pathsUiSync();
			updateDrawPathButtons();
			document.getElementById('tph-drawStatus').textContent = drawnPaths.length
				? tphT('draw_status_path_deleted_remaining', { count: drawnPaths.length })
				: tphT('draw_status_path_deleted_none');
		}

		function onDrawSvgMouseDown(ev) {
			if (selectedPathIndex === null) return;
			var t = ev.target;
			if (t.getAttribute('data-point-index') === null || t.getAttribute('data-path-index') === null) return;
			var pathIdx = parseInt(t.getAttribute('data-path-index'), 10);
			if (pathIdx !== selectedPathIndex) return;
			var pointIdx = parseInt(t.getAttribute('data-point-index'), 10);
			ev.preventDefault();
			ev.stopPropagation();
			draggingPointIndex = pointIdx;
			var dragMove = function(e) {
				if (draggingPointIndex === null) return;
				var pt = svgCoords(e);
				editingPathPoints[draggingPointIndex] = pt;
				redrawOverlay();
			};
			var dragUp = function(e) {
				if (draggingPointIndex === null) return;
				var newD = pathToD(editingPathPoints);
				drawnPaths[selectedPathIndex].d = newD;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].d = newD;
				pathsUiSync();
				draggingPointIndex = null;
				document.removeEventListener('mousemove', dragMove);
				document.removeEventListener('mouseup', dragUp);
			};
			document.addEventListener('mousemove', dragMove);
			document.addEventListener('mouseup', dragUp);
		}

		function onDrawSvgClick(ev) {
			if (!drawImg.src || drawImg.src === window.location.href) return;
			var t = ev.target;
			if (t.getAttribute('data-point-index') !== null) return;
			if (t.tagName && t.tagName.toLowerCase() === 'path' && t.getAttribute('data-path-index') !== null) {
				var idx = parseInt(t.getAttribute('data-path-index'), 10);
				selectPath(idx);
				ev.preventDefault();
				ev.stopPropagation();
				return;
			}
			if (selectedPathIndex !== null) {
				deselectPath();
				return;
			}
			var pt = svgCoords(ev);
			currentPath.push(pt);
			redrawOverlay();
			document.getElementById('tph-drawStatus').textContent = tphT('draw_status_drawing', {
				pathNum: drawnPaths.length + 1,
				pointCount: currentPath.length,
			});
		}

		function newPath() {
			if (currentPath.length >= 2) {
				drawnPaths.push({ d: pathToD(currentPath) });
				currentPath = [];
				redrawOverlay();
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_path_saved', { pathNum: drawnPaths.length });
			} else if (currentPath.length > 0) {
				currentPath = [];
				redrawOverlay();
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_path_cleared');
			} else {
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_need_two_before_new');
			}
		}

		function undoLastPoint() {
			if (currentPath.length > 0) {
				currentPath.pop();
				redrawOverlay();
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_undo_point', { count: currentPath.length });
			}
		}

		function copyPathsToStep1() {
			if (currentPath.length >= 2) drawnPaths.push({ d: pathToD(currentPath) });
			currentPath = [];
			if (drawnPaths.length === 0) {
				showToast(tphT('copy_no_paths_toast'));
				return;
			}
			var lines = drawnPaths.map(function(p, i) {
				return '<path id="svg_' + (i + 1) + '" d="' + escapeHtml(p.d) + '" stroke="#000" fill="#fff"/>';
			});
			var html = lines.join('\n');
			document.getElementById('tph-input').value = html;
			parsePaths();
			navigator.clipboard.writeText(html).then(function() {
				showToast(tphT('copy_parsed_clipboard_toast'));
			});
			document.getElementById('tph-drawStatus').textContent = tphT('copy_paths_to_step1_status', { count: drawnPaths.length });
		}

		document.getElementById('tph-drawLoadImage').addEventListener('click', loadDrawImage);
		document.getElementById('tph-drawNewPath').addEventListener('click', newPath);
		document.getElementById('tph-drawUndo').addEventListener('click', undoLastPoint);
		document.getElementById('tph-drawDeselect').addEventListener('click', deselectPath);
		document.getElementById('tph-drawDeletePath').addEventListener('click', deleteSelectedPath);
		document.getElementById('tph-drawCopyToStep1').addEventListener('click', copyPathsToStep1);
		drawSvg.addEventListener('click', onDrawSvgClick);
		drawSvg.addEventListener('mousedown', onDrawSvgMouseDown);

		// --- Steps 1–2 (paths + routes table; no per-path card UI) ---
		let paths = [];

		/** Same order as rock page: routes for this topo sorted by Nr. ascending. */
		function routesSortedByNr(te) {
			if (!te || !Array.isArray(te.routesForColors)) return [];
			return te.routesForColors.slice().sort(function(a, b) {
				var an = a.nr != null && a.nr !== '' ? Number(a.nr) : Infinity;
				var bn = b.nr != null && b.nr !== '' ? Number(b.nr) : Infinity;
				return an - bn;
			});
		}

		function autoApplyGradeColorsIfTopoRoutes() {
			var te = window.TOPO_EDIT;
			var sorted = routesSortedByNr(te);
			if (!sorted.length || !paths.length) return;
			for (var i = 0; i < paths.length; i++) {
				var route = sorted[i];
				if (route && route.strokeHex) {
					paths[i].color = route.strokeHex;
				}
			}
		}

		function pathsUiSync() {
			syncDrawnPathsFromPaths();
			if (drawSvg && drawSvg.getAttribute('viewBox')) {
				redrawOverlay();
			}
			populateRoutesForColorsSection();
		}

		function wireRouteTableDotCheckboxes() {
			var wrap = document.getElementById('tph-routes-table-wrap');
			if (!wrap) return;
			wrap.querySelectorAll('.tph-route-dot').forEach(function(el) {
				el.addEventListener('change', function() {
					var idx = parseInt(el.getAttribute('data-path-index'), 10);
					if (isNaN(idx) || idx < 0) return;
					if (paths[idx]) {
						paths[idx].dot = el.checked;
						syncDrawnPathsFromPaths();
					}
				});
			});
		}

		function populateRoutesForColorsSection() {
			var te = window.TOPO_EDIT;
			var sec = document.getElementById('tph-routes-section');
			var wrap = document.getElementById('tph-routes-table-wrap');
			if (!sec || !wrap) return;
			if (!te || !Array.isArray(te.routesForColors) || te.routesForColors.length === 0) {
				sec.style.display = 'none';
				return;
			}
			sec.style.display = 'block';
			var sorted = routesSortedByNr(te);
			var rows = sorted.map(function(r, rowIdx) {
				var bucket = r.chartBucket == null ? '—' : String(r.chartBucket);
				var nr = r.nr != null ? String(r.nr) : '';
				var hex = r.strokeHex || '';
				var hasPath = rowIdx < paths.length;
				var dotOn = hasPath && !!paths[rowIdx].dot;
				var disabledAttr = hasPath ? '' : ' disabled';
				var checkedAttr = dotOn ? ' checked' : '';
				return '<tr><td>' + escapeHtml(nr) + '</td><td>' + escapeHtml(r.name || '') + '</td><td>' + escapeHtml(r.grade || '') + '</td><td>' + escapeHtml(bucket) + '</td><td><span class="tph-color-swatch" style="background-color:' + escapeHtml(hex) + '" title="' + escapeHtml(hex) + '"></span><code style="font-size:11px">' + escapeHtml(hex) + '</code></td><td><label class="tph-route-dot-label"><input type="checkbox" class="tph-route-dot" data-path-index="' + rowIdx + '"' + disabledAttr + checkedAttr + '> ' + escapeHtml(tphT('routes_dot_anchor')) + '</label></td></tr>';
			}).join('');
			wrap.innerHTML = '<table class="tph-routes-table"><thead><tr><th>' + escapeHtml(tphT('routes_col_nr')) + '</th><th>' + escapeHtml(tphT('routes_col_route')) + '</th><th>' + escapeHtml(tphT('routes_col_grade')) + '</th><th>' + escapeHtml(tphT('routes_col_bucket')) + '</th><th>' + escapeHtml(tphT('routes_col_color')) + '</th><th>' + escapeHtml(tphT('routes_col_dot')) + '</th></tr></thead><tbody>' + rows + '</tbody></table>';
			wireRouteTableDotCheckboxes();
		}

		function refreshSuggestionRoutesFromServer() {
			var te = window.TOPO_EDIT;
			if (!te || !te.suggestionMode || !te.routesForColorsFetchUrl) {
				return;
			}
			var rockEl = document.getElementById('tph-suggestion-rock');
			var topoNrEl = document.getElementById('tph-suggestion-topoNr');
			if (!rockEl || !topoNrEl) {
				return;
			}
			var rockId = parseInt(rockEl.value, 10) || 0;
			var topoNr = parseInt(String(topoNrEl.value || '').trim(), 10) || 0;
			if (rockId < 1 || topoNr < 1) {
				te.routesForColors = [];
				populateRoutesForColorsSection();
				return;
			}
			var u;
			try {
				u = new URL(te.routesForColorsFetchUrl, window.location.href);
			} catch (e) {
				return;
			}
			u.searchParams.set('rock', String(rockId));
			u.searchParams.set('topoNr', String(topoNr));
			fetch(u.toString(), {
				headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				credentials: 'same-origin',
			})
				.then(function(r) {
					if (!r.ok) {
						throw new Error('routes-json');
					}
					return r.json();
				})
				.then(function(data) {
					te.routesForColors = Array.isArray(data.routesForColors) ? data.routesForColors : [];
					populateRoutesForColorsSection();
					autoApplyGradeColorsIfTopoRoutes();
					pathsUiSync();
				})
				.catch(function() {
					showToast(tphT('routes_fetch_failed'));
				});
		}

		function parsePaths() {
			const html = document.getElementById('tph-input').value || '';
			if (html.trim() === '') {
				return;
			}
			const prevByD = new Map();
			paths.forEach(function(p) {
				if (p && typeof p.d === 'string' && p.d.length) {
					prevByD.set(p.d, { dot: !!p.dot, dashed: !!p.dashed, color: p.color });
				}
			});
			const pattern = /<path[^>]*\sd="([^"]*)"/g;
			const dValues = [];
			let m;
			while ((m = pattern.exec(html)) !== null) {
				dValues.push(m[1]);
			}

			if (dValues.length === 0) {
				paths = [];
				pathsUiSync();
				return;
			}
			paths = dValues.map(function(d) {
				var pr = prevByD.get(d);
				return {
					d: d,
					color: pr && pr.color ? pr.color : '#E42522',
					dashed: pr ? pr.dashed : false,
					dot: pr ? pr.dot : false,
				};
			});
			autoApplyGradeColorsIfTopoRoutes();
			pathsUiSync();
		}

		function calculateEndpoint(d) {
			const commands = d.split(/(?=[a-zA-Z])/).filter(Boolean);
			let currentX = 0, currentY = 0, endX = 0, endY = 0;
			for (const cmd of commands) {
				const type = cmd[0];
				const rest = cmd.slice(1).trim();
				const values = rest ? rest.split(/[\s,]+/).map(parseFloat) : [];
				switch (type) {
					case 'M': currentX = values[0]; currentY = values[1]; break;
					case 'm': currentX += values[0]; currentY += values[1]; break;
					case 'L': currentX = values[0]; currentY = values[1]; break;
					case 'l': currentX += values[0]; currentY += values[1]; break;
					case 'C': currentX = values[4]; currentY = values[5]; break;
					case 'c': currentX += values[4]; currentY += values[5]; break;
					case 'S': currentX = values[2]; currentY = values[3]; break;
					case 's': currentX += values[2]; currentY += values[3]; break;
					case 'Q': currentX = values[2]; currentY = values[3]; break;
					case 'q': currentX += values[2]; currentY += values[3]; break;
					case 'T': currentX = values[0]; currentY = values[1]; break;
					case 't': currentX += values[0]; currentY += values[1]; break;
					case 'A': currentX = values[5]; currentY = values[6]; break;
					case 'a': currentX += values[5]; currentY += values[6]; break;
					case 'H': currentX = values[0]; break;
					case 'h': currentX += values[0]; break;
					case 'V': currentY = values[0]; break;
					case 'v': currentY += values[0]; break;
				}
				endX = currentX; endY = currentY;
			}
			return [endX, endY];
		}

		function generateOutput() {
			parsePaths();
			var lines = paths.map(function(p) {
				var parts = ["'d' => '" + p.d.replace(/'/g, "\\'") + "'"];
				if (p.color && p.color !== '#E42522') parts.push("'color' => '" + p.color + "'");
				if (p.dashed) parts.push("'dashed' => true");
				if (p.dot) parts.push("'dot' => true");
				return '                [' + parts.join(', ') + ']';
			});
			document.getElementById('tph-output').value = lines.join(',\n');
		}

		function getPhpLiteralContent() {
			return paths.map(function(p) {
				var parts = ["'d' => '" + p.d.replace(/'/g, "\\'") + "'"];
				if (p.color && p.color !== '#E42522') parts.push("'color' => '" + p.color + "'");
				if (p.dashed) parts.push("'dashed' => true");
				if (p.dot) parts.push("'dot' => true");
				return '[' + parts.join(', ') + ']';
			}).join(',\n');
		}

		function copyOutput() {
			var el = document.getElementById('tph-output');
			el.select();
			el.setSelectionRange(0, 99999);
			navigator.clipboard.writeText(el.value).then(function() { showToast(tphT('clipboard_copied')); });
		}

		function showToast(msg) {
			var t = document.getElementById('tph-toast');
			t.textContent = msg;
			t.classList.add('show');
			setTimeout(function() { t.classList.remove('show'); }, 2000);
		}

		function applyGradeColorsFromRoutes() {
			var te = window.TOPO_EDIT;
			var sorted = routesSortedByNr(te);
			if (!sorted.length) {
				showToast(tphT('apply_no_routes'));
				return;
			}
			if (!paths.length) {
				showToast(tphT('apply_no_paths_first'));
				return;
			}
			var applied = 0;
			var missing = 0;
			for (var i = 0; i < paths.length; i++) {
				var route = sorted[i];
				if (route && route.strokeHex) {
					paths[i].color = route.strokeHex;
					applied++;
				} else {
					missing++;
				}
			}
			pathsUiSync();
			var msg = tphT('apply_summary_start', { applied: applied });
			if (missing > 0) {
				msg += ' ' + tphT('apply_summary_missing', { missing: missing });
			}
			if (paths.length > sorted.length) {
				msg += ' ' + tphT('apply_summary_extra', { extra: paths.length - sorted.length });
			}
			showToast(msg);
		}

		var tphInputEl = document.getElementById('tph-input');
		if (tphInputEl) {
			tphInputEl.addEventListener('blur', parsePaths);
			tphInputEl.addEventListener('paste', function() {
				setTimeout(parsePaths, 0);
			});
		}

		document.getElementById('tph-generatePhp').addEventListener('click', generateOutput);
		document.getElementById('tph-copyPhp').addEventListener('click', copyOutput);

		// Preload when editing a topo: load image from Topo::$image into step 0 and show paths
		if (window.TOPO_EDIT) {
			var te = window.TOPO_EDIT;
			document.getElementById('tph-drawImageUrl').value = te.imageUrl || '';
			if (te.pathsJson) {
				try {
					var parsed = typeof te.pathsJson === 'string' ? JSON.parse(te.pathsJson) : te.pathsJson;
					paths = Array.isArray(parsed) ? parsed : [];
					drawnPaths = paths.map(function(p) {
						var d = (p && (p.d != null ? p.d : p.path)) || '';
						return { d: d, color: (p && p.color) || '#E42522', dot: !!(p && p.dot) };
					}).filter(function(p) { return p.d !== ''; });
					autoApplyGradeColorsIfTopoRoutes();
					pathsUiSync();
				} catch (e) { paths = []; drawnPaths = []; }
			}
			if (te.imageUrl) {
				loadDrawImage();
			}
		}

		populateRoutesForColorsSection();
		var applyGradBtn = document.getElementById('tph-apply-grade-colors');
		if (applyGradBtn) {
			applyGradBtn.addEventListener('click', applyGradeColorsFromRoutes);
		}

		var suggestionRoutesTimer = null;
		function scheduleSuggestionRoutesRefresh() {
			if (suggestionRoutesTimer) {
				clearTimeout(suggestionRoutesTimer);
			}
			suggestionRoutesTimer = setTimeout(function() {
				suggestionRoutesTimer = null;
				refreshSuggestionRoutesFromServer();
			}, 350);
		}

		if (window.TOPO_EDIT && window.TOPO_EDIT.suggestionMode && window.TOPO_EDIT.routesForColorsFetchUrl) {
			var srRock = document.getElementById('tph-suggestion-rock');
			var srTopo = document.getElementById('tph-suggestion-topoNr');
			if (srRock) {
				srRock.addEventListener('change', function() {
					refreshSuggestionRoutesFromServer();
				});
			}
			if (srTopo) {
				srTopo.addEventListener('change', function() {
					refreshSuggestionRoutesFromServer();
				});
				srTopo.addEventListener('input', scheduleSuggestionRoutesRefresh);
			}
			var srDrawFile = document.getElementById('tph-drawImageFile');
			if (srDrawFile) {
				srDrawFile.addEventListener('change', function() {
					refreshSuggestionRoutesFromServer();
				});
			}
			var bm = document.getElementById('tph-suggestion-open-bookmark');
			if (bm && typeof window.TPH_SUGGESTION_FORM_PATH === 'string' && window.TPH_SUGGESTION_FORM_PATH) {
				bm.addEventListener('click', function(ev) {
					ev.preventDefault();
					var u;
					try {
						u = new URL(window.TPH_SUGGESTION_FORM_PATH, window.location.href);
					} catch (e) {
						return;
					}
					var r = srRock && srRock.value ? String(srRock.value) : '';
					var t = srTopo && srTopo.value != null ? String(srTopo.value).trim() : '';
					if (r) {
						u.searchParams.set('rock', r);
					}
					if (t) {
						u.searchParams.set('topoNr', t);
					}
					window.location.href = u.pathname + u.search;
				});
			}
			var teInit = window.TOPO_EDIT;
			if ((!teInit.routesForColors || teInit.routesForColors.length === 0) && srRock && srTopo && srRock.value) {
				var tnr = parseInt(String(srTopo.value || '').trim(), 10) || 0;
				if (tnr >= 1) {
					refreshSuggestionRoutesFromServer();
				}
			}
		}

		// Save to topo (admin edit) or submit public suggestion (FormData)
		var saveToTopoBtn = document.getElementById('tph-saveToTopo');
		if (saveToTopoBtn && window.TOPO_EDIT) {
			saveToTopoBtn.addEventListener('click', function() {
				var te = window.TOPO_EDIT;
				var viewBox = '0 0 1024 820';
				var phpLiteral = getPhpLiteralContent();
				var csrfToken = (saveToTopoBtn && saveToTopoBtn.getAttribute('data-csrf-token')) || '';
				saveToTopoBtn.disabled = true;

				if (te.suggestionMode) {
					var rockEl = document.getElementById('tph-suggestion-rock');
					var topoNrEl = document.getElementById('tph-suggestion-topoNr');
					var nameEl = document.getElementById('tph-suggestion-name');
					var emailEl = document.getElementById('tph-suggestion-email');
					var commentEl = document.getElementById('tph-suggestion-comment');
					var honeypotEl = document.getElementById('tph-suggestion-website');
					var refImageEl = document.getElementById('tph-suggestion-refImage');
					var rockId = rockEl && rockEl.value ? rockEl.value : '';
					var fd = new FormData();
					fd.append('phpLiteral', phpLiteral);
					fd.append('viewBox', viewBox);
					if (csrfToken) fd.append('_token', csrfToken);
					fd.append('rockId', rockId);
					fd.append('topoNr', topoNrEl && topoNrEl.value != null ? String(topoNrEl.value) : '');
					fd.append('name', nameEl ? nameEl.value.trim() : '');
					fd.append('email', emailEl ? emailEl.value.trim() : '');
					fd.append('comment', commentEl ? commentEl.value.trim() : '');
					fd.append('website', honeypotEl ? honeypotEl.value : '');
					if (refImageEl && refImageEl.files && refImageEl.files[0]) {
						fd.append('refImage', refImageEl.files[0]);
					}
					fetch(te.saveUrl, {
						method: 'POST',
						headers: { 'X-Requested-With': 'XMLHttpRequest' },
						body: fd
					})
					.then(function(r) { return r.json(); })
					.then(function(data) {
						saveToTopoBtn.disabled = false;
						if (data.success && data.redirectUrl) {
							window.location.href = data.redirectUrl;
						} else {
							showToast(data.error || data.message || tphT('save_failed'));
						}
					})
					.catch(function() {
						saveToTopoBtn.disabled = false;
						showToast(tphT('save_failed'));
					});
					return;
				}

				var body = new URLSearchParams({ phpLiteral: phpLiteral, viewBox: viewBox });
				if (csrfToken) {
					body.append('_token', csrfToken);
				}
				fetch(te.saveUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
					body: body.toString()
				})
				.then(function(r) { return r.json(); })
				.then(function(data) {
					saveToTopoBtn.disabled = false;
					if (data.success && data.redirectUrl) {
						window.location.href = data.redirectUrl;
					} else {
						showToast(data.error || data.message || tphT('save_failed'));
					}
				})
				.catch(function() {
					saveToTopoBtn.disabled = false;
					showToast(tphT('save_failed'));
				});
			});
		}
	})();

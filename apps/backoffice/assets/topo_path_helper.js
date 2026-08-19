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
		var drawSmoothMode = true;
		var drawW = 1024, drawH = 820;

		function getDrawImageUrl() {
			var fileInput = document.getElementById('tph-drawImageFile');
			var file = fileInput && fileInput.files ? fileInput.files[0] : null;
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

		function pathToLineD(pts) {
			if (pts.length === 0) return '';
			var d = 'M' + pts[0][0] + ',' + pts[0][1];
			for (var i = 1; i < pts.length; i++) {
				d += ' L' + pts[i][0] + ',' + pts[i][1];
			}
			return d;
		}

		function pathToSmoothD(pts) {
			if (pts.length < 3) return pathToLineD(pts);
			var d = 'M' + pts[0][0] + ',' + pts[0][1];
			for (var i = 0; i < pts.length - 1; i++) {
				var p0 = i > 0 ? pts[i - 1] : pts[i];
				var p1 = pts[i];
				var p2 = pts[i + 1];
				var p3 = i !== pts.length - 2 ? pts[i + 2] : p2;
				var cp1x = p1[0] + (p2[0] - p0[0]) / 6;
				var cp1y = p1[1] + (p2[1] - p0[1]) / 6;
				var cp2x = p2[0] - (p3[0] - p1[0]) / 6;
				var cp2y = p2[1] - (p3[1] - p1[1]) / 6;
				d += ' C' + Math.round(cp1x) + ',' + Math.round(cp1y) + ' ' + Math.round(cp2x) + ',' + Math.round(cp2y) + ' ' + p2[0] + ',' + p2[1];
			}
			return d;
		}

		function buildPathD(pts, smooth) {
			return smooth ? pathToSmoothD(pts) : pathToLineD(pts);
		}

		function pathHasCurves(d) {
			return /[cCsSqQtTaA]/.test(d || '');
		}

		function getPointGuideLine(points, index, guideLength) {
			if (!points || points.length < 2 || index < 0 || index >= points.length) return null;
			var current = points[index];
			var prev = index > 0 ? points[index - 1] : null;
			var next = index < points.length - 1 ? points[index + 1] : null;
			var vx = 0;
			var vy = 0;

			if (prev && next) {
				vx = next[0] - prev[0];
				vy = next[1] - prev[1];
			} else if (next) {
				vx = next[0] - current[0];
				vy = next[1] - current[1];
			} else if (prev) {
				vx = current[0] - prev[0];
				vy = current[1] - prev[1];
			}

			var magnitude = Math.sqrt(vx * vx + vy * vy);
			if (!magnitude) return null;

			var half = guideLength / 2;
			var nx = vx / magnitude;
			var ny = vy / magnitude;

			return {
				x1: Math.round(current[0] - nx * half),
				y1: Math.round(current[1] - ny * half),
				x2: Math.round(current[0] + nx * half),
				y2: Math.round(current[1] + ny * half)
			};
		}

		function clonePoint(pt) {
			return pt ? [pt[0], pt[1]] : null;
		}

		function createPathNode(point, inHandle, outHandle) {
			return {
				point: clonePoint(point),
				inHandle: clonePoint(inHandle),
				outHandle: clonePoint(outHandle)
			};
		}

		function createSmoothModelFromPoints(pts) {
			if (!pts || !pts.length) return [];
			var model = pts.map(function(pt) {
				return createPathNode(pt, null, null);
			});
			if (pts.length < 3) return model;
			for (var i = 0; i < pts.length - 1; i++) {
				var p0 = i > 0 ? pts[i - 1] : pts[i];
				var p1 = pts[i];
				var p2 = pts[i + 1];
				var p3 = i !== pts.length - 2 ? pts[i + 2] : p2;
				model[i].outHandle = [
					Math.round(p1[0] + (p2[0] - p0[0]) / 6),
					Math.round(p1[1] + (p2[1] - p0[1]) / 6)
				];
				model[i + 1].inHandle = [
					Math.round(p2[0] - (p3[0] - p1[0]) / 6),
					Math.round(p2[1] - (p3[1] - p1[1]) / 6)
				];
			}
			return model;
		}

		function createLinearModelFromPoints(pts) {
			return (pts || []).map(function(pt) {
				return createPathNode(pt, null, null);
			});
		}

		function pathModelToD(model, smooth) {
			if (!model || !model.length) return '';
			if (!smooth || model.length < 2) {
				return pathToLineD(model.map(function(node) { return node.point; }));
			}
			var d = 'M' + model[0].point[0] + ',' + model[0].point[1];
			for (var i = 1; i < model.length; i++) {
				var prev = model[i - 1];
				var curr = model[i];
				var cp1 = prev.outHandle || prev.point;
				var cp2 = curr.inHandle || curr.point;
				d += ' C' + cp1[0] + ',' + cp1[1] + ' ' + cp2[0] + ',' + cp2[1] + ' ' + curr.point[0] + ',' + curr.point[1];
			}
			return d;
		}

		function syncEditingPointsFromModel() {
			if (!editingPathModel) {
				editingPathPoints = null;
				return;
			}
			editingPathPoints = editingPathModel.map(function(node) {
				return [node.point[0], node.point[1]];
			});
		}

		function createPathModelFromD(d) {
			if (!d || typeof d !== 'string') return [];
			var commands = d.trim().split(/(?=[a-zA-Z])/).filter(Boolean);
			var model = [];
			var x = 0;
			var y = 0;
			var lastCubicControl = null;
			for (var i = 0; i < commands.length; i++) {
				var cmd = commands[i].trim();
				if (!cmd) continue;
				var type = cmd.charAt(0);
				var rest = cmd.slice(1).replace(/^\s*,\s*|\s*,\s*/g, ',').trim();
				var values = rest ? rest.split(/[\s,]+/).map(parseFloat) : [];
				var j = 0;
				if (type === 'm' || type === 'M') {
					while (j + 1 < values.length) {
						if (type === 'm') {
							x += values[j];
							y += values[j + 1];
						} else {
							x = values[j];
							y = values[j + 1];
						}
						model.push(createPathNode([Math.round(x), Math.round(y)], null, null));
						lastCubicControl = null;
						j += 2;
					}
				} else if (type === 'l' || type === 'L') {
					while (j + 1 < values.length) {
						if (type === 'l') {
							x += values[j];
							y += values[j + 1];
						} else {
							x = values[j];
							y = values[j + 1];
						}
						model.push(createPathNode([Math.round(x), Math.round(y)], null, null));
						lastCubicControl = null;
						j += 2;
					}
				} else if ((type === 'c' || type === 'C') && model.length) {
					while (j + 5 < values.length) {
						var cp1 = type === 'c'
							? [x + values[j], y + values[j + 1]]
							: [values[j], values[j + 1]];
						var cp2 = type === 'c'
							? [x + values[j + 2], y + values[j + 3]]
							: [values[j + 2], values[j + 3]];
						var end = type === 'c'
							? [x + values[j + 4], y + values[j + 5]]
							: [values[j + 4], values[j + 5]];
						model[model.length - 1].outHandle = [Math.round(cp1[0]), Math.round(cp1[1])];
						model.push(createPathNode([Math.round(end[0]), Math.round(end[1])], [Math.round(cp2[0]), Math.round(cp2[1])], null));
						x = end[0];
						y = end[1];
						lastCubicControl = [cp2[0], cp2[1]];
						j += 6;
					}
				} else if ((type === 's' || type === 'S') && model.length) {
					while (j + 3 < values.length) {
						var cp1Reflect = lastCubicControl ? [2 * x - lastCubicControl[0], 2 * y - lastCubicControl[1]] : [x, y];
						var cp2s = type === 's'
							? [x + values[j], y + values[j + 1]]
							: [values[j], values[j + 1]];
						var ends = type === 's'
							? [x + values[j + 2], y + values[j + 3]]
							: [values[j + 2], values[j + 3]];
						model[model.length - 1].outHandle = [Math.round(cp1Reflect[0]), Math.round(cp1Reflect[1])];
						model.push(createPathNode([Math.round(ends[0]), Math.round(ends[1])], [Math.round(cp2s[0]), Math.round(cp2s[1])], null));
						x = ends[0];
						y = ends[1];
						lastCubicControl = [cp2s[0], cp2s[1]];
						j += 4;
					}
				}
			}
			return model;
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
		var editingPathModel = null;
		var selectedPathSmooth = true;
		var draggingPointIndex = null;
		var draggingHandle = null;
		var dragLastPoint = null;
		var dragStart = null;

		function updateDrawModeButton() {
			return;
		}

		function redrawOverlay() {
			var ns = 'http://www.w3.org/2000/svg';
			while (drawSvg.firstChild) drawSvg.removeChild(drawSvg.firstChild);
			drawnPaths.forEach(function(p, idx) {
				var color = p.color || '#E42522';
				var isSelected = selectedPathIndex === idx;
				var pathD = (isSelected && editingPathModel && editingPathModel.length >= 2) ? pathModelToD(editingPathModel, selectedPathSmooth) : p.d;
				var hitPath = document.createElementNS(ns, 'path');
				hitPath.setAttribute('d', pathD);
				hitPath.setAttribute('stroke', 'transparent');
				hitPath.setAttribute('stroke-width', isSelected ? '20' : '16');
				hitPath.setAttribute('fill', 'none');
				hitPath.setAttribute('data-path-index', String(idx));
				hitPath.setAttribute('class', 'tph-path-hit');
				hitPath.setAttribute('pointer-events', 'stroke');
				drawSvg.appendChild(hitPath);
				var path = document.createElementNS(ns, 'path');
				path.setAttribute('d', pathD);
				path.setAttribute('stroke', color);
				path.setAttribute('stroke-width', isSelected ? '3' : '2');
				path.setAttribute('fill', 'none');
				path.setAttribute('data-path-index', String(idx));
				path.setAttribute('class', 'tph-path' + (isSelected ? ' tph-path-selected' : ''));
				path.setAttribute('pointer-events', 'none');
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
				if (isSelected && editingPathModel && editingPathModel.length >= 2) {
					if (selectedPathSmooth) {
						editingPathModel.forEach(function(node, ptIdx) {
							['in', 'out'].forEach(function(kind) {
								var handle = kind === 'in' ? node.inHandle : node.outHandle;
								if (!handle) return;
								var line = document.createElementNS(ns, 'line');
								line.setAttribute('class', 'tph-control-guide');
								line.setAttribute('x1', node.point[0]);
								line.setAttribute('y1', node.point[1]);
								line.setAttribute('x2', handle[0]);
								line.setAttribute('y2', handle[1]);
								line.setAttribute('stroke', color);
								drawSvg.appendChild(line);
								var handleCircle = document.createElementNS(ns, 'circle');
								handleCircle.setAttribute('class', 'tph-control-handle');
								handleCircle.setAttribute('data-path-index', String(idx));
								handleCircle.setAttribute('data-point-index', String(ptIdx));
								handleCircle.setAttribute('data-handle-kind', kind);
								handleCircle.setAttribute('cx', handle[0]);
								handleCircle.setAttribute('cy', handle[1]);
								handleCircle.setAttribute('r', '4');
								handleCircle.setAttribute('fill', '#fff');
								handleCircle.setAttribute('stroke', color);
								handleCircle.setAttribute('stroke-width', '1.5');
								drawSvg.appendChild(handleCircle);
							});
						});
					}
					editingPathModel.forEach(function(node, ptIdx) {
						var circle = document.createElementNS(ns, 'circle');
						circle.setAttribute('class', 'point edit-point');
						circle.setAttribute('data-path-index', String(idx));
						circle.setAttribute('data-point-index', String(ptIdx));
						circle.setAttribute('cx', node.point[0]);
						circle.setAttribute('cy', node.point[1]);
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
				path.setAttribute('d', buildPathD(currentPath, drawSmoothMode));
				path.setAttribute('stroke', '#E42522');
				path.setAttribute('stroke-width', '2');
				path.setAttribute('fill', 'none');
				drawSvg.appendChild(path);
			}
			currentPath.forEach(function(pt, ptIdx) {
				if (drawSmoothMode && currentPath.length >= 2) {
					var currentGuide = getPointGuideLine(currentPath, ptIdx, 22);
					if (currentGuide) {
						var guideLine = document.createElementNS(ns, 'line');
						guideLine.setAttribute('class', 'tph-control-guide tph-control-guide--draft');
						guideLine.setAttribute('x1', currentGuide.x1);
						guideLine.setAttribute('y1', currentGuide.y1);
						guideLine.setAttribute('x2', currentGuide.x2);
						guideLine.setAttribute('y2', currentGuide.y2);
						guideLine.setAttribute('stroke', '#E42522');
						drawSvg.appendChild(guideLine);
					}
				}
			});
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
					return { d: d, color: (p && p.color) || '#E42522', dot: !!(p && p.dot), smooth: !!(p && p.smooth) || pathHasCurves(d) };
				}).filter(function(p) { return p.d !== ''; });
			}
		}

		function updateDrawPathButtons() {
			var sel = selectedPathIndex !== null;
			document.getElementById('tph-drawDeselect').disabled = !sel;
			document.getElementById('tph-drawDeletePath').disabled = !sel;
			updateDrawModeButton();
		}

		function selectPath(index) {
			if (index < 0 || index >= drawnPaths.length) return;
			var model = [];
			var smooth = !!drawnPaths[index].smooth || pathHasCurves(drawnPaths[index].d);
			if (smooth) {
				model = createPathModelFromD(drawnPaths[index].d);
			} else {
				var pts = pathToPoints(drawnPaths[index].d);
				model = createLinearModelFromPoints(pts);
			}
			if (model.length < 2) {
				document.getElementById('tph-drawStatus').textContent = tphT('draw_status_few_points');
				return;
			}
			selectedPathIndex = index;
			selectedPathSmooth = smooth;
			editingPathModel = model;
			syncEditingPointsFromModel();
			redrawOverlay();
			updateDrawPathButtons();
			document.getElementById('tph-drawStatus').textContent = tphT('draw_status_path_selected', { pathNum: index + 1 });
		}

		function deselectPath() {
			if (selectedPathIndex === null) return;
			if (editingPathModel && editingPathModel.length >= 2) {
				var newD = pathModelToD(editingPathModel, selectedPathSmooth);
				drawnPaths[selectedPathIndex].d = newD;
				drawnPaths[selectedPathIndex].smooth = selectedPathSmooth;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].d = newD;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].smooth = selectedPathSmooth;
			}
			selectedPathIndex = null;
			editingPathPoints = null;
			editingPathModel = null;
			selectedPathSmooth = true;
			draggingPointIndex = null;
			draggingHandle = null;
			dragLastPoint = null;
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
			editingPathModel = null;
			selectedPathSmooth = true;
			draggingHandle = null;
			dragLastPoint = null;
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
			var handleKind = t.getAttribute('data-handle-kind');
			draggingPointIndex = handleKind ? null : pointIdx;
			draggingHandle = handleKind ? { pointIndex: pointIdx, kind: handleKind } : null;
			dragLastPoint = svgCoords(ev);
			var dragMove = function(e) {
				var pt = svgCoords(e);
				if (draggingHandle) {
					var handleNode = editingPathModel && editingPathModel[draggingHandle.pointIndex];
					if (!handleNode) return;
					if (draggingHandle.kind === 'in') {
						handleNode.inHandle = [pt[0], pt[1]];
					} else {
						handleNode.outHandle = [pt[0], pt[1]];
					}
				} else if (draggingPointIndex !== null) {
					var node = editingPathModel && editingPathModel[draggingPointIndex];
					if (!node) return;
					var dx = pt[0] - dragLastPoint[0];
					var dy = pt[1] - dragLastPoint[1];
					node.point = [pt[0], pt[1]];
					if (node.inHandle) node.inHandle = [node.inHandle[0] + dx, node.inHandle[1] + dy];
					if (node.outHandle) node.outHandle = [node.outHandle[0] + dx, node.outHandle[1] + dy];
					dragLastPoint = pt;
				} else {
					return;
				}
				syncEditingPointsFromModel();
				redrawOverlay();
			};
			var dragUp = function(e) {
				if (!draggingHandle && draggingPointIndex === null) return;
				var newD = pathModelToD(editingPathModel, selectedPathSmooth);
				drawnPaths[selectedPathIndex].d = newD;
				drawnPaths[selectedPathIndex].smooth = selectedPathSmooth;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].d = newD;
				if (paths[selectedPathIndex]) paths[selectedPathIndex].smooth = selectedPathSmooth;
				pathsUiSync();
				draggingPointIndex = null;
				draggingHandle = null;
				dragLastPoint = null;
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
				drawnPaths.push({ d: buildPathD(currentPath, drawSmoothMode), smooth: drawSmoothMode });
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
			if (currentPath.length >= 2) drawnPaths.push({ d: buildPathD(currentPath, drawSmoothMode), smooth: drawSmoothMode });
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
					prevByD.set(p.d, { dot: !!p.dot, dashed: !!p.dashed, color: p.color, smooth: !!p.smooth });
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
					smooth: pr ? pr.smooth : pathHasCurves(d),
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
			if (te.imageUrl) {
				document.getElementById('tph-drawImageUrl').value = te.imageUrl;
			}
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

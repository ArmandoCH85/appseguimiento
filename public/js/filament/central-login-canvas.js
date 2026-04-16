/**
 * DR RouteX — Central Login Canvas Animation
 * IIFE auto-ejecutable. Busca #drrxMapCanvas y si existe,
 * ejecuta la animación del mapa GPS con nodos y vehículos.
 */
(function () {
  'use strict';

  var canvas = document.getElementById('drrxMapCanvas');
  if (!canvas) return;

  var ctx = canvas.getContext('2d');

  // ── Colores de la paleta ──────────────────────────────────
  var COLORS = ['#003DA5', '#FF6600', '#1E88E5', '#4CAF50'];

  // ── Resize ────────────────────────────────────────────────
  function resizeCanvas() {
    canvas.width = canvas.parentElement.offsetWidth;
    canvas.height = canvas.parentElement.offsetHeight;
  }

  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  // ── Nodos del "mapa" ──────────────────────────────────────
  var nodes = [];
  var nodeCount = 50;

  for (var i = 0; i < nodeCount; i++) {
    nodes.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      vx: (Math.random() - 0.5) * 0.4,
      vy: (Math.random() - 0.5) * 0.4,
      r: Math.random() * 2 + 1,
      color: COLORS[Math.floor(Math.random() * 4)],
      alpha: Math.random() * 0.5 + 0.3
    });
  }

  // ── Vehículos que se mueven entre nodos ───────────────────
  var vehicles = [];
  var vehicleCount = 8;

  for (var v = 0; v < vehicleCount; v++) {
    var fromNode = Math.floor(Math.random() * nodeCount);
    var toNode = Math.floor(Math.random() * nodeCount);
    while (toNode === fromNode) toNode = Math.floor(Math.random() * nodeCount);

    vehicles.push({
      from: fromNode,
      to: toNode,
      progress: Math.random(),
      speed: Math.random() * 0.003 + 0.001,
      color: COLORS[Math.floor(Math.random() * 4)],
      trail: []
    });
  }

  // ── Loop de dibujo ────────────────────────────────────────
  function drawMap() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Conexiones entre nodos cercanos (< 150px)
    for (var i = 0; i < nodes.length; i++) {
      for (var j = i + 1; j < nodes.length; j++) {
        var dx = nodes[i].x - nodes[j].x;
        var dy = nodes[i].y - nodes[j].y;
        var dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < 150) {
          ctx.beginPath();
          ctx.moveTo(nodes[i].x, nodes[i].y);
          ctx.lineTo(nodes[j].x, nodes[j].y);
          ctx.strokeStyle = 'rgba(0, 61, 165, ' + (0.06 * (1 - dist / 150)) + ')';
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }

    // Dibujar y mover nodos
    for (var n = 0; n < nodes.length; n++) {
      var node = nodes[n];
      node.x += node.vx;
      node.y += node.vy;

      if (node.x < 0 || node.x > canvas.width) node.vx *= -1;
      if (node.y < 0 || node.y > canvas.height) node.vy *= -1;

      ctx.beginPath();
      ctx.arc(node.x, node.y, Math.max(0.5, node.r), 0, Math.PI * 2);
      ctx.fillStyle = node.color;
      ctx.globalAlpha = node.alpha;
      ctx.fill();
      ctx.globalAlpha = 1;
    }

    // Dibujar vehículos en movimiento
    for (var vi = 0; vi < vehicles.length; vi++) {
      var veh = vehicles[vi];
      veh.progress += veh.speed;

      if (veh.progress >= 1) {
        veh.from = veh.to;
        var next = Math.floor(Math.random() * nodeCount);
        while (next === veh.from) next = Math.floor(Math.random() * nodeCount);
        veh.to = next;
        veh.progress = 0;
      }

      var fromN = nodes[veh.from];
      var toN = nodes[veh.to];
      var x = fromN.x + (toN.x - fromN.x) * veh.progress;
      var y = fromN.y + (toN.y - fromN.y) * veh.progress;

      // Estela
      veh.trail.push({ x: x, y: y });
      if (veh.trail.length > 20) veh.trail.shift();

      if (veh.trail.length > 1) {
        ctx.beginPath();
        ctx.moveTo(veh.trail[0].x, veh.trail[0].y);
        for (var t = 1; t < veh.trail.length; t++) {
          ctx.lineTo(veh.trail[t].x, veh.trail[t].y);
        }
        ctx.strokeStyle = veh.color;
        ctx.globalAlpha = 0.4;
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.globalAlpha = 1;
      }

      // Punto del vehículo
      ctx.beginPath();
      ctx.arc(x, y, 3.5, 0, Math.PI * 2);
      ctx.fillStyle = veh.color;
      ctx.fill();

      // Resplandor
      ctx.beginPath();
      ctx.arc(x, y, 8, 0, Math.PI * 2);
      ctx.fillStyle = veh.color;
      ctx.globalAlpha = 0.15;
      ctx.fill();
      ctx.globalAlpha = 1;
    }

    requestAnimationFrame(drawMap);
  }

  drawMap();

  // ── Contadores en vivo ────────────────────────────────────
  var elVehicles = document.getElementById('drrxStatVehicles');
  var elRoutes = document.getElementById('drrxStatRoutes');
  var elAlerts = document.getElementById('drrxStatAlerts');

  if (elVehicles) {
    setInterval(function () {
      var current = parseInt(elVehicles.textContent, 10);
      var delta = Math.floor(Math.random() * 3) - 1;
      elVehicles.textContent = Math.max(240, current + delta);
    }, 4000);
  }

  if (elRoutes) {
    setInterval(function () {
      var current = parseInt(elRoutes.textContent, 10);
      var delta = Math.floor(Math.random() * 3) - 1;
      elRoutes.textContent = Math.max(80, current + delta);
    }, 5000);
  }

  if (elAlerts) {
    setInterval(function () {
      var current = parseInt(elAlerts.textContent, 10);
      var delta = Math.floor(Math.random() * 3) - 1;
      elAlerts.textContent = Math.max(0, current + delta);
    }, 7000);
  }
})();

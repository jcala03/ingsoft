<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Mapa de Desastres</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Segoe UI', sans-serif; }

#map {
    height: 100vh;
    width: 100%;
}

/* ── Panel lateral ── */
#panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: #0f172a;
    color: #e2e8f0;
    padding: 24px 20px;
    display: none;
    z-index: 500;
    border-left: 1px solid #1e293b;
    overflow-y: auto;
}

#panel h3 {
    color: #5eead4;
    font-size: 16px;
    margin-bottom: 14px;
    text-transform: capitalize;
}

#panel .campo {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 6px;
}

#panel .valor {
    font-size: 14px;
    color: #e2e8f0;
    margin-bottom: 14px;
}

#btn-cerrar-panel {
    position: absolute;
    top: 14px;
    right: 14px;
    background: none;
    border: none;
    color: #64748b;
    font-size: 18px;
    cursor: pointer;
}

#btn-cerrar-panel:hover { color: #e2e8f0; }

/* ── Modal crear evento ── */
#modal-evento {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

#modal-evento.activo {
    display: flex;
}

.modal-box {
    background: #1e2433;
    border: 1px solid #2e3a50;
    border-radius: 10px;
    padding: 28px;
    width: 360px;
    color: #e2e8f0;
}

.modal-box h3 {
    color: #5eead4;
    font-size: 15px;
    margin-bottom: 18px;
}

.modal-box label {
    display: block;
    font-size: 12px;
    color: #94a3b8;
    margin-bottom: 5px;
}

.modal-box select,
.modal-box textarea,
.modal-box input[type="date"] {
    width: 100%;
    padding: 8px 10px;
    background: #0f172a;
    color: #e2e8f0;
    border: 1px solid #2e3a50;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 14px;
    outline: none;
}

.modal-box select:focus,
.modal-box textarea:focus,
.modal-box input[type="date"]:focus {
    border-color: #0d9488;
}

.modal-box textarea { resize: vertical; }

#modal-error {
    color: #f87171;
    font-size: 12px;
    margin-bottom: 12px;
    display: none;
}

.modal-acciones {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-cancelar {
    padding: 8px 16px;
    background: #2e3a50;
    color: #e2e8f0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}

.btn-guardar {
    padding: 8px 16px;
    background: #0d9488;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}

.btn-cancelar:hover { background: #3e4a60; }
.btn-guardar:hover  { background: #0f766e; }
</style>
</head>

<body>

<div id="map"></div>

<!-- Panel lateral -->
<div id="panel">
    <button id="btn-cerrar-panel" onclick="cerrarPanel()">✕</button>
    <div id="panel-contenido"></div>
</div>

<!-- Modal crear evento -->
<div id="modal-evento">
    <div class="modal-box">
        <h3>Registrar nuevo evento</h3>

        <label>Tipo de desastre</label>
        <select id="input-tipo">
            <option value="inundacion">Inundación</option>
            <option value="vendaval">Vendaval</option>
            <option value="deslizamiento">Deslizamiento</option>
            <option value="erosion_costera">Erosión costera</option>
            <option value="sequia">Sequía</option>
            <option value="marejada">Marejada ciclónica</option>
            <option value="incendio_forestal">Incendio forestal</option>
            <option value="sismo">Sismo</option>
        </select>

        <label>Descripción</label>
        <textarea id="input-descripcion" rows="3" placeholder="Describe brevemente el evento..."></textarea>

        <label>Fecha</label>
        <input type="date" id="input-fecha">

        <p id="modal-error"></p>

        <div class="modal-acciones">
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-guardar" onclick="guardarEvento()">Guardar</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
// ── Estado global ──
var coordsSeleccionadas = null;
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── Mapa ──
var map = L.map('map').setView([10.9639, -74.7964], 11);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

map.on('tileerror', function() {
    alert('No se pudo cargar el mapa. Verifique su conexión.');
});

// ── Colores por tipo ──
function getColor(tipo) {
    switch (tipo) {
        case 'inundacion':       return '#3b82f6'; // azul
        case 'vendaval':         return '#a855f7'; // púrpura
        case 'deslizamiento':    return '#92400e'; // marrón
        case 'erosion_costera':  return '#f97316'; // naranja
        case 'sequia':           return '#eab308'; // amarillo
        case 'marejada':         return '#06b6d4'; // cyan
        case 'incendio_forestal':return '#ef4444'; // rojo
        case 'sismo':            return '#6b7280'; // gris
        default:                 return '#22c55e'; // verde
    }
}

// ── Panel lateral ──
function mostrarPanel(e) {
    var panel = document.getElementById('panel');
    var contenido = document.getElementById('panel-contenido');

    contenido.innerHTML = `
        <h3>${e.tipo.replace(/_/g, ' ')}</h3>
        <p class="campo">Descripción</p>
        <p class="valor">${e.descripcion}</p>
        <p class="campo">Fecha</p>
        <p class="valor">${e.fecha}</p>
        <p class="campo">Reportado por</p>
        <p class="valor">${e.usuario?.name ?? 'Anónimo'}</p>
    `;

    panel.style.display = 'block';
}

function cerrarPanel() {
    document.getElementById('panel').style.display = 'none';
}

// ── Agregar marcador al mapa ──
function agregarMarcador(e) {
    var marker = L.circleMarker([e.lat, e.lng], {
        radius: 8,
        fillColor: getColor(e.tipo),
        color: '#fff',
        weight: 1,
        opacity: 1,
        fillOpacity: 0.85,
    }).addTo(map);

    marker.on('click', function() {
        mostrarPanel(e);
    });
}

// ── Cargar eventos existentes ──
fetch('/apieventos')
    .then(function(res) {
        if (!res.ok) throw new Error();
        return res.json();
    })
    .then(function(data) {
        if (data.length === 0) {
            mostrarPanel({
                tipo: 'Sin eventos',
                descripcion: 'No hay eventos reportados en esta área.',
                fecha: '',
                usuario: null,
            });
            return;
        }
        data.forEach(function(e) {
            agregarMarcador(e);
        });
    })
    .catch(function() {
        alert('No se pudieron cargar los eventos. Intente más tarde.');
    });

// ── Clic en mapa → abrir modal ──
map.on('click', function(e) {
    coordsSeleccionadas = e.latlng;

    var hoy = new Date().toISOString().split('T')[0];
    document.getElementById('input-fecha').value = hoy;
    document.getElementById('input-fecha').max = hoy;
    document.getElementById('input-descripcion').value = '';
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('modal-evento').classList.add('activo');
});

// ── Modal ──
function cerrarModal() {
    document.getElementById('modal-evento').classList.remove('activo');
    coordsSeleccionadas = null;
}

function guardarEvento() {
    var tipo        = document.getElementById('input-tipo').value;
    var descripcion = document.getElementById('input-descripcion').value.trim();
    var fecha       = document.getElementById('input-fecha').value;
    var errorEl     = document.getElementById('modal-error');

    // Validación cliente
    if (!descripcion) {
        errorEl.textContent = 'La descripción es obligatoria.';
        errorEl.style.display = 'block';
        return;
    }
    if (!fecha) {
        errorEl.textContent = 'La fecha es obligatoria.';
        errorEl.style.display = 'block';
        return;
    }

    fetch('/crearevento', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
        },
        body: JSON.stringify({
            tipo:        tipo,
            descripcion: descripcion,
            lat:         coordsSeleccionadas.lat,
            lng:         coordsSeleccionadas.lng,
            fecha:       fecha,
        }),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data.success) throw new Error();
        agregarMarcador(data.evento);
        cerrarModal();
    })
    .catch(function() {
        errorEl.textContent = 'Error al guardar el evento. Intenta de nuevo.';
        errorEl.style.display = 'block';
    });
}
</script>

</body>
</html>
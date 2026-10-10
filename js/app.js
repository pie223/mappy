(async function () {
  'use strict';

  // ---------- intro overlay (attached first, independent of map loading) ----------
  const introOverlay = document.getElementById('intro-overlay');
  if (introOverlay) {
    function dismissIntro() {
      introOverlay.classList.add('fade-out');
      setTimeout(() => introOverlay.classList.add('hidden'), 350);
    }
    introOverlay.addEventListener('click', dismissIntro);
    // iOS/mobile browsers don't always reliably synthesize a 'click' from a
    // tap on a plain div (especially with backdrop-filter + fixed
    // positioning involved) — handling touchend directly makes the dismiss
    // work off the real gesture instead of depending on that translation.
    introOverlay.addEventListener('touchend', (e) => {
      e.preventDefault();
      dismissIntro();
    }, { passive: false });
  }

  // ---------- map setup ----------

  async function loadEnglishStyle(url) {
    const res = await fetch(url);
    const style = await res.json();

    // Self-hosted glyphs — GFS Complutum with Noto Sans as a fallback for
    // any character it doesn't cover.
  

    style.layers.forEach((layer) => {
      if (layer.layout && layer.layout['text-field']) {
        layer.layout['text-field'] = ['coalesce', ['get', 'name:en'], ['get', 'name']];
      }
      // Drop any icon image on symbol layers — if the sprite sheet ever
      // fails to load, MapLibre shows a placeholder square where the icon
      // would've been. We don't use POI icons anywhere in this app, so it's
      // simplest to remove them entirely rather than depend on sprite loading.
      if (layer.layout && layer.layout['icon-image']) {
        delete layer.layout['icon-image'];
      }
    });
    return style;
  }

  const map = new maplibregl.Map({
    container: 'map',
    style: await loadEnglishStyle('https://tiles.openfreemap.org/styles/positron'),
    center: [23.7275, 37.9838],
    zoom: 11,
    pitch: 0,
    renderWorldCopies: false,
    attributionControl: { compact: true, position: '' }
  });

  map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
  map.addControl(new maplibregl.GeolocateControl({ }), 'bottom-right');

  // Group the zoom control and the geolocate control into one vertical
  // stack. Plain CSS on .maplibregl-ctrl-group only affects the buttons
  // *inside* each individual control — it can't relate two separate
  // sibling controls to each other, so we wrap both group divs in a
  // shared container that CSS can then lay out as a column.
  const bottomRight = document.querySelector('.maplibregl-ctrl-bottom-right');
  if (bottomRight) {
    const groups = bottomRight.querySelectorAll('.maplibregl-ctrl-group');
    if (groups.length) {
      const stack = document.createElement('div');
      stack.className = 'ctrl-stack';
      groups.forEach((g) => stack.appendChild(g));
      bottomRight.appendChild(stack);
    }
  }

  const markersById = {};

  // ---------- state ----------
  let placingMode = false;
  let pendingLatLng = null;
  const ownedPins = loadOwnedPins(); // ids created by this browser -> can delete

  // ---------- DOM refs ----------
  const addModeBtn = document.getElementById('add-mode-btn');
  const hintBanner = document.getElementById('hint-banner');
  const cancelModeBtn = document.getElementById('cancel-mode');
  const pinCountEl = document.getElementById('pin-count');

  const uploadModal = document.getElementById('upload-modal');
  const uploadForm = document.getElementById('upload-form');
  const modalCoords = document.getElementById('modal-coords');
  const fieldLat = document.getElementById('field-lat');
  const fieldLng = document.getElementById('field-lng');
  const formError = document.getElementById('form-error');
  const submitBtn = document.getElementById('submit-btn');
  const modalClose = document.getElementById('modal-close');

  const playerModal = document.getElementById('player-modal');
  const playerClose = document.getElementById('player-close');
  const playerCoords = document.getElementById('player-coords');
  const playerTitle = document.getElementById('player-title');
  const playerMeta = document.getElementById('player-meta');
  const playerDescription = document.getElementById('player-description');
  const playerAudio = document.getElementById('player-audio');
  const playerDelete = document.getElementById('player-delete');

  const toast = document.getElementById('toast');

  let activePlayerPinId = null;

  // ---------- helpers ----------
  function fmtCoords(lat, lng) {
    return lat.toFixed(4) + ', ' + lng.toFixed(4);
  }

  function showToast(msg) {
    toast.textContent = msg;
    toast.classList.remove('hidden');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toast.classList.add('hidden'), 2600);
  }

  function loadOwnedPins() {
    try {
      return JSON.parse(localStorage.getItem('echoatlas_owned') || '[]');
    } catch (e) {
      return [];
    }
  }
  function saveOwnedPin(id) {
    ownedPins.push(id);
    localStorage.setItem('echoatlas_owned', JSON.stringify(ownedPins));
  }
  function removeOwnedPin(id) {
    const idx = ownedPins.indexOf(id);
    if (idx > -1) ownedPins.splice(idx, 1);
    localStorage.setItem('echoatlas_owned', JSON.stringify(ownedPins));
  }

  function pinEl() {
    const el = document.createElement('div');
    el.className = 'sound-pin';
    el.innerHTML = '<span class="ring"></span><span class="ring delay"></span><span class="core"></span>';
    return el;
  }

  function timeAgo(unixSeconds) {
    const diff = Math.max(0, Math.floor(Date.now() / 1000) - unixSeconds);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  // ---------- rendering pins ----------
  function addPinToMap(pin) {
    if (markersById[pin.id]) return;
    const el = pinEl();
    el.addEventListener('click', (e) => {
      e.stopPropagation();
      openPlayer(pin);
    });
    const marker = new maplibregl.Marker({ element: el, anchor: 'center' })
      .setLngLat([pin.lng, pin.lat])
      .addTo(map);
    markersById[pin.id] = marker;
  }

  function removePinFromMap(id) {
    const marker = markersById[id];
    if (marker) {
      marker.remove();
      delete markersById[id];
    }
  }

  async function loadPins() {
    try {
      const res = await fetch('api/pins.php');
      const data = await res.json();
      const pins = data.pins || [];
      pins.forEach(addPinToMap);
      pinCountEl.textContent = pins.length;
      window.__allPins = pins;
    } catch (e) {
      showToast('Could not reach the server for pins.');
    }
  }

  // ---------- placing mode ----------
  function setPlacingMode(on) {
    placingMode = on;
    addModeBtn.classList.toggle('active', on);
    hintBanner.classList.toggle('hidden', !on);
    map.getCanvas().style.cursor = on ? 'crosshair' : '';
    addModeBtn.querySelector('.fab-label').textContent = on ? 'Click The Map…' : 'Place A Sound';
  }

  addModeBtn.addEventListener('click', () => setPlacingMode(!placingMode));
  cancelModeBtn.addEventListener('click', () => setPlacingMode(false));

  map.on('click', (e) => {
    if (!placingMode) return;
    pendingLatLng = e.lngLat;
    openUploadModal(e.lngLat);
    setPlacingMode(false);
  });

  // ---------- upload modal ----------
  function openUploadModal(latlng) {
    fieldLat.value = latlng.lat;
    fieldLng.value = latlng.lng;
    modalCoords.textContent = fmtCoords(latlng.lat, latlng.lng);
    formError.classList.add('hidden');
    uploadForm.reset();
    fieldLat.value = latlng.lat;
    fieldLng.value = latlng.lng;
    uploadModal.classList.remove('hidden');
  }
  function closeUploadModal() {
    uploadModal.classList.add('hidden');
  }
  modalClose.addEventListener('click', closeUploadModal);
  uploadModal.addEventListener('click', (e) => {
    if (e.target === uploadModal) closeUploadModal();
  });

  uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    formError.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.querySelector('.submit-label').textContent = 'Uploading…';

    try {
      const formData = new FormData(uploadForm);
      const res = await fetch('api/upload.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Upload failed.');
      }

      addPinToMap(data.pin);
      saveOwnedPin(data.pin.id);
      pinCountEl.textContent = (parseInt(pinCountEl.textContent, 10) || 0) + 1;
      closeUploadModal();
      showToast('Pin dropped — your sound is on the map.');
      map.panTo([data.pin.lng, data.pin.lat]);
    } catch (err) {
      formError.textContent = err.message;
      formError.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.querySelector('.submit-label').textContent = 'Drop pin & upload';
    }
  });

  // ---------- player modal ----------
  function openPlayer(pin) {
    activePlayerPinId = pin.id;
    playerCoords.textContent = fmtCoords(pin.lat, pin.lng);
    playerTitle.textContent = pin.title;
    playerMeta.textContent = (pin.author || 'Anonymous') + ' · ' + timeAgo(pin.createdAt);
    playerDescription.textContent = pin.description || '';
    playerDescription.classList.toggle('hidden', !pin.description);
    playerAudio.src = pin.url;
    playerDelete.classList.toggle('hidden', ownedPins.indexOf(pin.id) === -1);
    playerModal.classList.remove('hidden');
  }
  function closePlayer() {
    playerModal.classList.add('hidden');
    playerAudio.pause();
    playerAudio.src = '';
    activePlayerPinId = null;
  }
  playerClose.addEventListener('click', closePlayer);
  playerModal.addEventListener('click', (e) => {
    if (e.target === playerModal) closePlayer();
  });

  playerDelete.addEventListener('click', async () => {
    if (!activePlayerPinId) return;
    if (!confirm('Remove this pin and its sound? This can\'t be undone.')) return;
    try {
      const res = await fetch('api/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: activePlayerPinId })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Could not delete pin.');
      removePinFromMap(activePlayerPinId);
      removeOwnedPin(activePlayerPinId);
      pinCountEl.textContent = Math.max(0, (parseInt(pinCountEl.textContent, 10) || 1) - 1);
      showToast('Pin removed.');
      closePlayer();
    } catch (err) {
      showToast(err.message);
    }
  });

  // esc to close whichever modal is open
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!uploadModal.classList.contains('hidden')) closeUploadModal();
    if (!playerModal.classList.contains('hidden')) closePlayer();
    if (placingMode) setPlacingMode(false);
  });

  // ---------- reduce marker/map desync during fast zoom/pan ----------
  // DOM markers and the WebGL canvas are two separate rendering systems
  // that resync every frame. Any lag between them shows up as pins
  // visually drifting away from the map underneath them. Rather than
  // chase that lag, hide markers for the duration of the gesture and
  // let them reappear already-correct the instant it settles.
  const mapEl = document.getElementById('map');
  map.on('movestart', () => mapEl.classList.add('map-interacting'));
  map.on('zoomstart', () => mapEl.classList.add('map-interacting'));
  map.on('moveend', () => mapEl.classList.remove('map-interacting'));
  map.on('zoomend', () => mapEl.classList.remove('map-interacting'));
  map.on('idle', () => map.resize());

  // ---------- init ----------
  map.on('load', () => {
    loadPins();
    // const attrib = document.querySelector('.maplibregl-ctrl-attrib');
    // if (attrib) {
    //   attrib.classList.add('maplibregl-compact');
    //   attrib.classList.remove('maplibregl-compact-show');
    // }
  });
})();
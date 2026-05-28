function qs(selector, root = document) { return root.querySelector(selector); }
function qsa(selector, root = document) { return Array.from(root.querySelectorAll(selector)); }

window.LuminaAdmin = {
  toast(type, message) {
    if (!message) return;
    if (window.Swal) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true,
        customClass: { popup: 'rounded-xl' }
      });
      Toast.fire({ icon: type || 'success', title: message });
      return;
    }
    console[type === 'error' ? 'error' : 'log'](message);
  }
};

function initSidebar() {
  const root = document.documentElement;
  const sidebarState = localStorage.getItem('lumina-sidebar');
  if (sidebarState === 'collapsed') root.classList.add('sidebar-collapsed');

  qsa('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      root.classList.toggle('sidebar-collapsed');
      localStorage.setItem('lumina-sidebar', root.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
    });
  });

  qsa('[data-mobile-sidebar-open]').forEach((button) => button.addEventListener('click', () => document.body.classList.add('mobile-sidebar-open')));
  qsa('[data-mobile-sidebar-close]').forEach((button) => button.addEventListener('click', () => document.body.classList.remove('mobile-sidebar-open')));
}

function initDeleteConfirm() {
  qsa('.js-delete-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.Swal) return;
      event.preventDefault();
      const label = form.getAttribute('data-delete-label') || 'data ini';
      Swal.fire({
        icon: 'warning',
        title: 'Hapus data?',
        text: `Data ${label} akan dihapus dan tidak bisa dikembalikan.`,
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ba1a1a',
        cancelButtonColor: '#000000',
        customClass: { popup: 'rounded-xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' }
      }).then((result) => {
        if (result.isConfirmed) form.submit();
      });
    });
  });
}

function initSubmitLoading() {
  qsa('form').forEach((form) => {
    form.addEventListener('submit', () => {
      qsa('[data-submit-label]', form).forEach((button) => {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span>${button.getAttribute('data-submit-label')}</span>`;
      });
    });
  });
}

function initPasswordToggle() {
  qsa('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = qs(button.getAttribute('data-toggle-password'));
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
    });
  });
}

function initTableSelection() {
  qsa('[data-table-select-all]').forEach((selectAll) => {
    const table = selectAll.closest('table');
    if (!table) return;
    const checkboxes = qsa('tbody [data-table-row-check]', table);
    selectAll.addEventListener('change', () => {
      checkboxes.forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
        checkbox.closest('tr')?.classList.toggle('bg-[#e9e7ea]', selectAll.checked);
      });
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => checkbox.closest('tr')?.classList.toggle('bg-[#e9e7ea]', checkbox.checked)));
  });
}

function initImagePreview() {
  qsa('input[type="file"][data-preview-target]').forEach((input) => {
    input.addEventListener('change', () => {
      const target = qs(`[data-image-preview="${input.dataset.previewTarget}"]`);
      const file = input.files && input.files[0];
      if (!target || !file) return;
      if (!file.type.startsWith('image/')) {
        window.LuminaAdmin?.toast('warning', 'File harus berupa gambar.');
        input.value = '';
        return;
      }
      target.src = URL.createObjectURL(file);
    });
  });
}

function initActionMenus() {

  document.addEventListener('click', (event) => {
    qsa('details[data-action-menu]').forEach((menu) => {
      if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
  });
}

async function fetchJson(url) {
  const response = await fetch(url, { headers: { 'Accept': 'application/json' }});
  if (!response.ok) throw new Error('Request failed');
  return response.json();
}

function initMapPickers(retry = 0) {
  if (!qsa('[data-map-picker]').length) return;

  if (!window.L) {
    if (retry < 20) {
      setTimeout(() => initMapPickers(retry + 1), 150);
    } else {
      window.LuminaAdmin?.toast('error', 'Map library gagal dimuat. Periksa koneksi internet atau CDN Leaflet.');
    }
    return;
  }

  qsa('[data-map-picker]').forEach((picker) => {
    const canvas = qs('[data-map-canvas]', picker);
    if (!canvas || canvas.dataset.initialized) return;
    canvas.dataset.initialized = '1';

    const readonly = picker.dataset.readonly === '1';
    const latInput = qs('[data-map-lat]', picker) || qs('input[name="latitude"]');
    const lngInput = qs('[data-map-lng]', picker) || qs('input[name="longitude"]');
    const searchInput = qs('[data-map-search]', picker);
    const statusText = qs('[data-map-status]', picker);
    const addressField = document.querySelector('textarea[name="address"]');
    const hasSavedCoordinate = Boolean((latInput?.value || '').trim() && (lngInput?.value || '').trim());
    const initialLat = parseFloat(latInput?.value || picker.dataset.lat || '-6.2');
    const initialLng = parseFloat(lngInput?.value || picker.dataset.lng || '106.816666');
    const map = L.map(canvas, { scrollWheelZoom: true }).setView([initialLat, initialLng], hasSavedCoordinate ? 16 : 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const pinIcon = L.divIcon({
      className: 'lumina-map-pin-wrap',
      html: `
        <div style="position:relative;width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#ba1a1a;color:#ffffff;border-radius:9999px;border:4px solid #ffffff;box-shadow:0 16px 28px rgba(186,26,26,.32);">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="position:relative;z-index:2;display:block;">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z" />
          </svg>
          <span style="position:absolute;left:50%;bottom:-7px;width:14px;height:14px;background:#ba1a1a;border-radius:3px;transform:translateX(-50%) rotate(45deg);box-shadow:4px 4px 10px rgba(186,26,26,.22);"></span>
        </div>
      `,
      iconSize: [44, 50],
      iconAnchor: [22, 48],
      popupAnchor: [0, -48]
    });

    const marker = L.marker([initialLat, initialLng], {
      draggable: !readonly,
      icon: pinIcon,
      autoPan: true,
      title: readonly ? 'Lokasi branch' : 'Geser pin untuk menentukan titik branch'
    }).addTo(map);

    const setStatus = (message) => {
      if (statusText) statusText.textContent = message;
    };

    const updateCoordinates = (lat, lng) => {
      const nextLat = Number(lat).toFixed(7);
      const nextLng = Number(lng).toFixed(7);
      if (latInput) latInput.value = nextLat;
      if (lngInput) lngInput.value = nextLng;
      marker.bindPopup(`<strong>Pin lokasi branch</strong><br>${nextLat}, ${nextLng}`);
      setStatus(`Koordinat aktif: ${nextLat}, ${nextLng}`);
    };

    const reverseGeocode = async (lat, lng, forceAddress = false) => {
      try {
        const result = await fetchJson(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
        if (searchInput && result.display_name) searchInput.value = result.display_name;
        if (addressField && result.display_name && (forceAddress || !addressField.value)) {
          addressField.value = result.display_name;
        }
      } catch (_) {}
    };

    const moveMarker = async (lat, lng, zoom = 16, reverse = true, forceAddress = false) => {
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], zoom);
      updateCoordinates(lat, lng);
      marker.openPopup();
      if (reverse) await reverseGeocode(lat, lng, forceAddress);
    };

    updateCoordinates(initialLat, initialLng);
    marker.openPopup();

    marker.on('dragstart', () => setStatus('Pin sedang digeser...'));
    marker.on('dragend', async () => {
      const point = marker.getLatLng();
      await moveMarker(point.lat, point.lng, map.getZoom(), true, true);
    });

    if (!readonly) {
      map.on('click', async (event) => {
        await moveMarker(event.latlng.lat, event.latlng.lng, 16, true, true);
      });
    }

    const runSearch = async () => {
      const query = searchInput?.value?.trim();
      if (!query) return;
      setStatus('Mencari lokasi...');
      try {
        const results = await fetchJson(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&q=${encodeURIComponent(query)}`);
        if (!results.length) {
          setStatus('Lokasi tidak ditemukan.');
          window.LuminaAdmin?.toast('warning', 'Lokasi tidak ditemukan. Coba kata kunci lain.');
          return;
        }
        const location = results[0];
        const lat = parseFloat(location.lat);
        const lng = parseFloat(location.lon);
        await moveMarker(lat, lng, 16, false);
        if (addressField) addressField.value = location.display_name || query;
        if (searchInput && location.display_name) searchInput.value = location.display_name;
      } catch (error) {
        setStatus('Gagal mencari lokasi.');
        window.LuminaAdmin?.toast('error', 'Gagal mencari lokasi. Periksa koneksi internet.');
      }
    };

    qs('[data-map-search-button]', picker)?.addEventListener('click', runSearch);
    searchInput?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') { event.preventDefault(); runSearch(); }
    });

    qs('[data-map-current-button]', picker)?.addEventListener('click', () => {
      if (!navigator.geolocation) {
        window.LuminaAdmin?.toast('warning', 'Browser tidak mendukung geolocation.');
        return;
      }
      setStatus('Mengambil lokasi perangkat...');
      navigator.geolocation.getCurrentPosition(async (position) => {
        await moveMarker(position.coords.latitude, position.coords.longitude, 16, true, true);
      }, () => {
        setStatus('Tidak dapat mengambil lokasi perangkat.');
        window.LuminaAdmin?.toast('error', 'Tidak dapat mengambil lokasi saat ini.');
      });
    });

    setTimeout(() => map.invalidateSize(), 250);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initDeleteConfirm();
  initSubmitLoading();
  initPasswordToggle();
  initTableSelection();
  initImagePreview();
  initActionMenus();
  initMapPickers();
});

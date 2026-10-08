/**
 * app.js
 * Main script for Sistem Pengelolaan Ruangan FT Unsoed
 * Implements Stitch Design System (Screen 1 & Screen 3)
 */

let roomsData = [];

// DOM Elements
const roomGrid = document.getElementById('roomGrid');
const searchInput = document.getElementById('searchInput');
const filterBuilding = document.getElementById('filterBuilding');
const filterStatus = document.getElementById('filterStatus');
const lastUpdatedEl = document.getElementById('lastUpdated');

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
  fetchRooms();
  
  // Event Listeners for Filters
  if (searchInput) searchInput.addEventListener('input', handleFilter);
  if (filterBuilding) filterBuilding.addEventListener('change', handleFilter);
  if (filterStatus) filterStatus.addEventListener('change', handleFilter);
  
  // Polling Real-time 60 detik (FR-013)
  setInterval(() => {
    fetchRooms();
  }, 60000);
});

// Fetch data from API
async function fetchRooms() {
  try {
    const url = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/api/rooms';
    const response = await fetch(url);
    const result = await response.json();
    
    if (result.success) {
      roomsData = result.data;
      handleFilter(); // Re-apply filters and render
      updateTimestamp(result.timestamp);
    }
  } catch (error) {
    console.error("Failed to fetch rooms:", error);
    if (lastUpdatedEl) {
      lastUpdatedEl.innerHTML = '<span style="color:var(--danger)">⚠️ Gagal memuat pembaruan data real-time.</span>';
    }
  }
}

// Render Room Cards according to Stitch Screen 1 & Screen 3
function renderRooms(rooms) {
  if (!roomGrid) return;
  
  roomGrid.innerHTML = '';
  
  if (rooms.length === 0) {
    roomGrid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 3.5rem 1rem; color: var(--text-muted); background: #ffffff; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem; opacity: 0.4;">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h3 style="margin-bottom: 0.5rem; color: var(--primary);">Tidak ada ruangan yang cocok</h3>
        <p style="margin-bottom: 1.25rem; font-size: 0.875rem;">Coba sesuaikan kata kunci pencarian atau longgarkan filter gedung / status ketersediaan.</p>
        <button class="btn btn-outline btn-sm" onclick="resetFilters()">Reset Semua Filter</button>
      </div>
    `;
    return;
  }
  
  rooms.forEach(room => {
    const card = document.createElement('div');
    card.className = 'stitch-room-card';
    
    // Status styling according to Stitch design
    let statusClass = 'status-available';
    let statusBadgeText = room.statusText || 'Tersedia Sekarang';
    let categoryBadge = room.type === 'lab' ? 'Laboratorium Komputer' : (room.type === 'meeting' ? 'Ruang Sidang' : (room.type === 'auditorium' ? 'Auditorium' : 'Kelas Teori'));
    
    if (room.status === 'occupied_class' || room.status === 'occupied_booking') {
      statusClass = 'status-occupied';
    } else if (room.status === 'occupied_class_unconfirmed' || room.status === 'vacant_temp') {
      statusClass = 'status-special';
    } else if (room.status === 'inactive') {
      statusClass = 'status-maintenance';
      statusBadgeText = 'Pemeliharaan';
    }
    
    const canBook = (room.status === 'vacant' || room.status === 'vacant_temp') && room.can_be_booked;
    
    // Choose banner color/gradient based on room type
    let headerBg = 'linear-gradient(135deg, #001e40 0%, #003366 100%)';
    if (room.type === 'lab') headerBg = 'linear-gradient(135deg, #0f2d59 0%, #1e40af 100%)';
    else if (room.type === 'meeting') headerBg = 'linear-gradient(135deg, #312e81 0%, #4338ca 100%)';
    else if (room.type === 'auditorium') headerBg = 'linear-gradient(135deg, #7c5800 0%, #b45309 100%)';

    card.innerHTML = `
      <div style="background: ${headerBg}; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
        <span class="stitch-thumbnail-tag" style="position: static; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">
          ${categoryBadge}
        </span>
        <span class="status-chip ${statusClass}" style="background: #ffffff; border: none; font-size: 0.7rem; padding: 0.2rem 0.65rem;">
          <span class="status-chip-dot"></span>
          ${statusBadgeText}
        </span>
      </div>
      
      <div class="stitch-room-content">
        <div class="stitch-room-header">
          <div>
            <h3 class="stitch-room-name">${room.name}</h3>
            <div class="stitch-room-location">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
              <span>Gedung ${room.building}, Lantai ${room.floor} (${room.code})</span>
            </div>
          </div>
        </div>
        
        <div class="stitch-specs-grid">
          <div class="stitch-spec-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            <span>Kapasitas: <strong>${room.capacity} Org</strong></span>
          </div>
          <div class="stitch-spec-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            <span>Fasilitas: <strong>AC, Proyektor</strong></span>
          </div>
          <div class="stitch-spec-item" style="grid-column: span 2; border-top: 1px dashed var(--border); padding-top: 0.35rem; margin-top: 0.2rem;">
            <span style="color: var(--secondary); font-weight: 700;">Waktu:</span>
            <span style="font-weight: 600; color: var(--text-main);">${room.timeKeterangan}</span>
          </div>
        </div>

        ${room.details ? `
          <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; background: #ffffff; padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--border);">
            ${room.details}
          </div>
        ` : ''}
        
        <div class="stitch-card-actions">
          <button class="btn btn-outline btn-sm" onclick="viewDetails(${room.id})" style="flex: 1;">Detail Ruangan</button>
          ${canBook ? `<button class="btn btn-primary btn-sm" onclick="bookRoom(${room.id})" style="flex: 1;">Booking Ruang</button>` : ''}
        </div>
      </div>
    `;
    
    roomGrid.appendChild(card);
  });
}

// Filter Logic
window.handleFilter = function() {
  const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
  const bldgFilter = filterBuilding ? filterBuilding.value : 'all';
  const statFilter = filterStatus ? filterStatus.value : 'all';
  
  const filtered = roomsData.filter(room => {
    const matchesSearch = !searchTerm || 
                          room.name.toLowerCase().includes(searchTerm) || 
                          room.code.toLowerCase().includes(searchTerm) ||
                          (room.details && room.details.toLowerCase().includes(searchTerm));
                          
    const matchesBldg = (bldgFilter === 'all') || (room.building === bldgFilter);
    
    let matchesStat = true;
    if (statFilter === 'vacant') {
      matchesStat = (room.status === 'vacant' || room.status === 'vacant_temp');
    } else if (statFilter === 'occupied') {
      matchesStat = (room.status.startsWith('occupied'));
    } else if (statFilter === 'unconfirmed') {
      matchesStat = (room.status === 'occupied_class_unconfirmed');
    } else if (statFilter !== 'all') {
      matchesStat = (room.status === statFilter);
    }
    
    return matchesSearch && matchesBldg && matchesStat;
  });
  
  renderRooms(filtered);
};

// Reset Filters
window.resetFilters = function() {
  if (searchInput) searchInput.value = '';
  if (filterBuilding) filterBuilding.value = 'all';
  if (filterStatus) filterStatus.value = 'all';
  handleFilter();
};

function updateTimestamp(timestamp) {
  if (lastUpdatedEl) {
    const timeStr = timestamp ? timestamp.split(' ')[1] : new Date().toLocaleTimeString('id-ID');
    lastUpdatedEl.innerHTML = `Sinkronisasi: <strong>${timeStr} WIB</strong>`;
  }
}

window.viewDetails = function(roomId) {
  const target = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/rooms/' + roomId;
  window.location.href = target;
};

window.bookRoom = function(roomId) {
  const target = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/booking/create?room_id=' + roomId;
  window.location.href = target;
};

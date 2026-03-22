// assets/js/app.js

const API_BASE = '/echobase/api/';  // adjust if your base path is different

const STATUSES = [
  { id: 'backlog',     label: 'Backlog',     color: '#6b7280' },
  { id: 'todo',        label: 'To Do',       color: '#3b82f6' },
  { id: 'in_progress', label: 'In Progress', color: '#eab308' },
  { id: 'review',      label: 'Review',      color: '#8b5cf6' },
  { id: 'done',        label: 'Done',        color: '#10b981' }
];

let allProjects = [];

// ────────────────────────────────────────────────
// Modal management
// ────────────────────────────────────────────────
const modal = document.getElementById('modal');
const modalContent = modal.querySelector('.modal-content');

function showToast(message, isError = false) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${isError ? 'error' : ''}`;
  toast.textContent = message;
  
  container.appendChild(toast);
  
  // Trigger animation
  setTimeout(() => toast.classList.add('show'), 10);
  
  // Auto-dismiss after 4s
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

function openModal(project = null) {  // null = new project
  const isNew = !project;
  const title = isNew ? 'New Project' : `Edit: ${project.name}`;

  modalContent.innerHTML = `
    <div class="modal-header">
      <h2>${title}</h2>
      <button class="modal-btn close-btn">Close</button>
    </div>

    <div class="tabs">
      <button class="tab-btn active" data-tab="details">Details</button>
      <button class="tab-btn" data-tab="notes">Notes</button>
      <button class="tab-btn" data-tab="documents">Documents</button>
    </div>

    <div class="tab-content">
      <!-- Details tab (default) -->
      <div id="tab-details" class="tab-panel active">
        <form id="project-form">
          <input type="hidden" name="id" value="${project?.id || ''}">
          <label>Name: <input type="text" name="name" value="${project?.name || ''}" required></label>
          <label>Description:<br><textarea name="description" rows="5">${project?.description || ''}</textarea></label>
          <label>Status:
            <select name="status">
              ${STATUSES.map(s => `
                <option value="${s.id}" ${project?.status === s.id ? 'selected' : ''}>
                  ${s.label}
                </option>
              `).join('')}
            </select>
          </label>
          <label>Priority:
            <select name="priority">
              <option value="low"    ${project?.priority === 'low'    ? 'selected' : ''}>Low</option>
              <option value="medium" ${project?.priority === 'medium' ? 'selected' : ''}>Medium</option>
              <option value="high"   ${project?.priority === 'high'   ? 'selected' : ''}>High</option>
              <option value="urgent" ${project?.priority === 'urgent' ? 'selected' : ''}>Urgent</option>
            </select>
          </label>
          <label>Tags (comma separated): 
            <input type="text" name="tags" value="${project?.tags?.join(', ') || ''}">
          </label>
		  
          <!-- Action bar at bottom -->
		  <div class="form-actions">
			${project ? `
			  <button type="button" class="modal-btn delete-btn">Delete Project</button>
			` : ''}
			<button type="submit" class="modal-btn save-btn">Save Project</button>
		  </div>
		  </form>
		  </div>
	
      <!-- Notes tab -->
      <div id="tab-notes" class="tab-panel hidden">
        <div id="notes-list"></div>
        <form id="note-form" style="margin-top:1rem;">
          <textarea name="note" rows="3" placeholder="Add a new note..." required></textarea>
          <button type="submit">Add Note</button>
        </form>
      </div>

      <!-- Documents tab -->
      <div id="tab-documents" class="tab-panel hidden">
        <div id="documents-list"></div>
        <form id="document-form" enctype="multipart/form-data" style="margin-top:1rem;">
          <input type="file" name="files[]" multiple accept="*/*">
          <button type="submit">Upload File(s)</button>
        </form>
      </div>
    </div>
  `;

  modal.classList.remove('hidden');

  // Tab switching
  modalContent.querySelectorAll('.tab-btn').forEach(btn => {
    btn.onclick = () => {
      modalContent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      modalContent.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
      btn.classList.add('active');
      modalContent.querySelector(`#tab-${btn.dataset.tab}`).classList.remove('hidden');

      // Lazy load content when tab is opened
      if (btn.dataset.tab === 'notes' && project) loadNotes(project.id);
      if (btn.dataset.tab === 'documents' && project) loadDocuments(project.id);
    };
  });

	// Delete button (only if editing an existing project)
	if (project) {
	  const deleteBtn = modalContent.querySelector('.delete-btn');
	  if (deleteBtn) {
		deleteBtn.onclick = async () => {
		  if (!confirm(`Permanently delete "${project.name}"? This action cannot be undone.`)) return;

		  try {
			const res = await fetch(`${API_BASE}projects.php?id=${project.id}`, { method: 'DELETE' });
			const result = await res.json();
			if (result.success) {
			  modal.classList.add('hidden');
			  loadProjects();
			  showToast('Project deleted', false);
			} else {
			  showToast('Delete failed: ' + (result.error || 'Unknown'), true);
			}
		  } catch (err) {
			showToast('Network error: ' + (err.message || 'Unknown'), true);
		  }
		};
	  }
	}

	// Close still works the same
	modalContent.querySelector('.close-btn').onclick = () => modal.classList.add('hidden');

  // Save project (create or update)
  modalContent.querySelector('#project-form').onsubmit = async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    const id = data.id ? parseInt(data.id) : null;
    const url = id ? `${API_BASE}projects.php?id=${id}` : `${API_BASE}projects.php`;
    const method = id ? 'PUT' : 'POST';

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await res.json();
      if (result.success) {
        modal.classList.add('hidden');
        loadProjects();
      } else {
        showToast('Error: ' + (result.error || 'Save failed'), true);
      }
    } catch (err) {
	  showToast('Network error: ' + (err.message || 'Unknown'), true);
    }
  };

  // Add note
  modalContent.querySelector('#note-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const note = e.target.note.value.trim();
    if (!note || !project) return;

    try {
      const res = await fetch(API_BASE + 'notes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_id: project.id, note })
      });
      const result = await res.json();
      if (result.success) {
        e.target.note.value = '';
        loadNotes(project.id);
      } else {
		showToast('Error: ' + (result.error || 'Error adding note'), true);
      }
    } catch (err) {
	  showToast('Network error: ' + (err.message || 'Unknown'), true);
    }
  });

  // Upload documents
  modalContent.querySelector('#document-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!project) return;

    const formData = new FormData(e.target);
    formData.append('project_id', project.id);

    try {
      const res = await fetch(API_BASE + 'documents.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        e.target.reset();
        loadDocuments(project.id);
      } else {
		showToast('Error: ' + (result.error || 'Upload error'), true);
      }
    } catch (err) {
	  showToast('Network error: ' + (err.message || 'Unknown'), true);
    }
  });

  // Initial load if editing
  if (project) {
    loadNotes(project.id);
    // Documents load only when tab clicked (lazy)
  }
}

// ────────────────────────────────────────────────
// Load notes into tab
// ────────────────────────────────────────────────
async function loadNotes(projectId) {
  try {
    const res = await fetch(`${API_BASE}notes.php?project_id=${projectId}`);
    const data = await res.json();
    if (!data.success) return;

    const list = modalContent.querySelector('#notes-list');
    list.innerHTML = data.notes.length
      ? data.notes.map(n => `
          <div class="note-item">
            <small>${new Date(n.created_at).toLocaleString()}</small>
            <p>${n.note.replace(/\n/g, '<br>')}</p>
          </div>
        `).join('')
      : '<p style="color:var(--text-secondary);">No notes yet.</p>';
  } catch (err) {
    console.error('Notes load failed', err);
  }
}

// ────────────────────────────────────────────────
// Load documents into tab
// ────────────────────────────────────────────────
async function loadDocuments(projectId) {
  try {
    const res = await fetch(`${API_BASE}documents.php?project_id=${projectId}`);
    const data = await res.json();
    if (!data.success) return;

    const list = modalContent.querySelector('#documents-list');
    list.innerHTML = data.documents.length
      ? data.documents.map(d => `
          <div class="document-item">
            <a href="${d.url}" target="_blank" download>${d.original_name}</a>
            <small>(${formatFileSize(d.file_size)}) • ${new Date(d.uploaded_at).toLocaleDateString()}</small>
            <button class="delete-doc" data-id="${d.id}">Delete</button>
          </div>
        `).join('')
      : '<p style="color:var(--text-secondary);">No documents yet.</p>';

    // Delete handlers
    list.querySelectorAll('.delete-doc').forEach(btn => {
      btn.onclick = async () => {
        if (!confirm('Delete this file?')) return;
        try {
          const res = await fetch(`${API_BASE}documents.php?id=${btn.dataset.id}`, { method: 'DELETE' });
          const result = await res.json();
          if (result.success) loadDocuments(projectId);
          else showToast('Error: ' + (result.error || 'Delete failed'), true);
        } catch (err) {
		  showToast('Network error: ' + (err.message || 'Unknown'), true);
        }
      };
    });
  } catch (err) {
    console.error('Documents load failed', err);
  }
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// And for new project button:
document.getElementById('new-project').onclick = () => openModal();  // no project = create

// ────────────────────────────────────────────────
// Load & render all projects
// ────────────────────────────────────────────────
async function loadProjects() {
  try {
    const res = await fetch(API_BASE + 'projects.php');
    const data = await res.json();

    if (!data.success) {
      console.error('Failed to load projects:', data.error);
      return;
    }

    allProjects = data.projects;

    const board = document.querySelector('.board');
    board.innerHTML = ''; // clear existing

    STATUSES.forEach(status => {
      const column = document.createElement('div');
      column.className = 'column';
      column.dataset.status = status.id;

      column.innerHTML = `
        <div class="column-header" style="background:${status.color}22; color:${status.color}">
          ${status.label}
          <span class="count">(${allProjects.filter(p => p.status === status.id).length})</span>
        </div>
        <div class="column-body"></div>
      `;

      const body = column.querySelector('.column-body');

      allProjects
        .filter(p => p.status === status.id)
        .forEach(project => {
          const card = document.createElement('div');
          card.className = 'card';
          card.draggable = true;
          card.dataset.id = project.id;

          // Priority badge
          const prioClass = `priority-${project.priority}`;
          const prioLabel = project.priority.charAt(0).toUpperCase() + project.priority.slice(1);

          // Tags
          const tagsHtml = project.tags?.length
            ? project.tags.map(tag => `<span class="tag">${tag.trim()}</span>`).join('')
            : '';

          card.innerHTML = `
            <div class="card-header">
              <span class="priority-badge ${prioClass}">${prioLabel}</span>
              <button class="edit-btn" title="Edit">✏️</button>
            </div>
            <h3>${project.name}</h3>
            ${tagsHtml ? `<div class="tags">${tagsHtml}</div>` : ''}
            <p class="description-preview">${project.description?.substring(0, 120) || ''}${project.description?.length > 120 ? '...' : ''}</p>
          `;

		  // Replace the existing edit-btn listener with this broader one
		  card.addEventListener('click', (e) => {
		    // Prevent opening modal if user clicked these interactive elements
		    if (e.target.closest('.edit-btn') || 
			    e.target.closest('.delete-project') ||   // if you added delete to card later
			    e.target.tagName === 'A' || 
			    e.target.tagName === 'BUTTON') {
			  return;
		    }
		  
  		    openModal(project);
		  });
		  
          // Edit click → open modal (to be expanded later)
		  card.querySelector('.edit-btn').addEventListener('click', (e) => {
		    e.stopPropagation();
			openModal(project);
		  });

          body.appendChild(card);
        });

      board.appendChild(column);
    });

    initDragAndDrop();
  } catch (err) {
    console.error('Error loading projects:', err);
  }
}

// ────────────────────────────────────────────────
// Drag & Drop between columns
// ────────────────────────────────────────────────
function initDragAndDrop() {
  const cards = document.querySelectorAll('.card');
  const columns = document.querySelectorAll('.column-body');

  cards.forEach(card => {
    card.addEventListener('dragstart', (e) => {
      card.classList.add('dragging');
      e.dataTransfer.setData('text/plain', card.dataset.id);
    });

    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
    });
  });

  columns.forEach(column => {
    column.addEventListener('dragover', (e) => {
      e.preventDefault();
      const dragging = document.querySelector('.dragging');
      if (dragging) {
        const afterElement = getDragAfterElement(column, e.clientY);
        if (afterElement == null) {
          column.appendChild(dragging);
        } else {
          column.insertBefore(dragging, afterElement);
        }
      }
    });

    column.addEventListener('drop', async (e) => {
      e.preventDefault();
      const projectId = e.dataTransfer.getData('text/plain');
      const newStatus = column.parentElement.dataset.status;

      // Find the project
      const project = allProjects.find(p => p.id == projectId);
      if (!project || project.status === newStatus) return;

      try {
        const res = await fetch(`${API_BASE}projects.php?id=${projectId}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ status: newStatus })
        });

        const result = await res.json();

        if (result.success) {
          // Optimistic UI update
          project.status = newStatus;
          loadProjects(); // simple refresh for now
        } else {
		  showToast('Error: ' + (result.error || 'Failed to update status'), true);
          loadProjects(); // revert
        }
      } catch (err) {
        console.error('Drag-drop save failed:', err);
        loadProjects(); // revert
      }
    });
  });
}

function getDragAfterElement(column, y) {
  const draggableElements = [...column.querySelectorAll('.card:not(.dragging)')];

  return draggableElements.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    if (offset < 0 && offset > closest.offset) {
      return { offset: offset, element: child };
    }
    return closest;
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Theme toggle
const themeToggle = document.getElementById('theme-toggle');
const html = document.documentElement;
let currentTheme = localStorage.getItem('theme') || 'light';

html.setAttribute('data-theme', currentTheme);

// No need for text anymore - icon is handled by CSS

themeToggle.addEventListener('click', () => {
  currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', currentTheme);
  localStorage.setItem('theme', currentTheme);
});


// Load projects when page is ready
document.addEventListener('DOMContentLoaded', () => {
  loadProjects();
});

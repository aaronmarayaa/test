@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Rooms';
    $pageSubtitle = 'Add, edit, archive, and manage school rooms.';
@endphp

@section('form')
    <h2 id="form-title">Add Room</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="room_code">Room Code</label>
        <input type="text" id="room_code">
    </div>

    <div class="form-group">
        <label for="room_name">Room Name</label>
        <input type="text" id="room_name">
    </div>

    <div class="form-group">
        <label for="room_type">Room Type</label>
        <select id="room_type">
            <option value="lecture">lecture</option>
            <option value="computer_lab">computer_lab</option>
            <option value="laboratory">laboratory</option>
            <option value="gym">gym</option>
        </select>
    </div>

    <div class="form-group">
        <label for="capacity">Capacity</label>
        <input type="number" id="capacity" min="1">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Room</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Room List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="room-table-body">
            <tr>
                <td colspan="7">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const apiUrl = "/api/rooms";

        const tableBody = document.getElementById("room-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const roomCodeInput = document.getElementById("room_code");
        const roomNameInput = document.getElementById("room_name");
        const roomTypeInput = document.getElementById("room_type");
        const capacityInput = document.getElementById("capacity");
        const statusInput = document.getElementById("status");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        function escapeHtml(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function resetForm() {
            editIdInput.value = "";
            roomCodeInput.value = "";
            roomNameInput.value = "";
            roomTypeInput.value = "lecture";
            capacityInput.value = "";
            statusInput.value = "Active";
            formTitle.textContent = "Add Room";
            saveBtn.textContent = "Save Room";
            cancelBtn.style.display = "none";
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7">No rooms found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map(item => `
                <tr>
                    <td>${escapeHtml(item.id)}</td>
                    <td>${escapeHtml(item.room_code)}</td>
                    <td>${escapeHtml(item.room_name)}</td>
                    <td>${escapeHtml(item.room_type)}</td>
                    <td>${escapeHtml(item.capacity)}</td>
                    <td>
                        <span class="tag ${item.status === 'Active' ? 'tag-green' : 'tag-gray'}">
                            ${escapeHtml(item.status)}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button"
                                class="btn-edit"
                                onclick='editItem(${JSON.stringify(item)})'>
                                Edit
                            </button>
                            <button type="button"
                                class="btn-delete"
                                onclick="deleteItem(${item.id})">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `).join("");
        }

        async function loadRooms() {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7">Loading...</td>
                </tr>
            `;

            try {
                const response = await fetch(apiUrl, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load rooms.");
                }

                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7">${escapeHtml(error.message)}</td>
                    </tr>
                `;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            roomCodeInput.value = item.room_code ?? "";
            roomNameInput.value = item.room_name ?? "";
            roomTypeInput.value = item.room_type ?? "lecture";
            capacityInput.value = item.capacity ?? "";
            statusInput.value = item.status ?? "Active";

            formTitle.textContent = "Edit Room";
            saveBtn.textContent = "Update Room";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                room_code: roomCodeInput.value.trim(),
                room_name: roomNameInput.value.trim(),
                room_type: roomTypeInput.value,
                capacity: Number(capacityInput.value),
                status: statusInput.value
            };

            if (!payload.room_code || !payload.room_name || !payload.room_type || !payload.capacity) {
                alert("Room Code, Room Name, Room Type, and Capacity are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${apiUrl}/${id}` : apiUrl;
            const method = isEdit ? "PUT" : "POST";

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        const firstError = Object.values(result.errors).flat()[0];
                        throw new Error(firstError || "Validation failed.");
                    }
                    throw new Error(result.message || "Save failed.");
                }

                resetForm();
                await loadRooms();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this room?")) return;

            try {
                const response = await fetch(`${apiUrl}/${id}`, {
                    method: "DELETE",
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Delete failed.");
                }

                if (String(editIdInput.value) === String(id)) {
                    resetForm();
                }

                await loadRooms();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        loadRooms();
    </script>
@endsection

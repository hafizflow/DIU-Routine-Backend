<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Routine Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .suggestions-container {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 0.75rem 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            z-index: 50;
            top: 100%;
            left: 0;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background-color: #f8fafc;
            padding-left: 1.25rem;
        }

        .input-container {
            position: relative;
            z-index: 40;
        }

        .day-row {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .class-row:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
            transition: all 0.2s;
        }

        /* Mobile Timeline Styles */
        .mobile-timeline {
            background: #1a1a1a;
            min-height: 100vh;
            color: white;
        }

        .mobile-class-item {
            background: #2a2a2a;
            border-radius: 12px;
            margin-bottom: 1rem;
            padding: 1rem;
            border-left: 4px solid #3b82f6;
            position: relative;
        }

        .mobile-break-item {
            background: rgba(64, 64, 64, 0.5);
            border-radius: 12px;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-left: 4px solid #6b7280;
            position: relative;
        }

        .mobile-break-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 8px,
                rgba(107, 114, 128, 0.1) 8px,
                rgba(107, 114, 128, 0.1) 16px
            );
            border-radius: 12px;
            pointer-events: none;
        }

        .time-label {
            font-size: 1.25rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .course-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.75rem;
        }

        .course-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #94a3b8;
            min-width: 60px;
        }

        .detail-value {
            color: white;
            font-weight: 500;
        }

        .course-code {
            color: #60a5fa;
            font-weight: 600;
        }

        .section-code {
            color: #34d399;
            font-weight: 600;
        }

        .teacher-name {
            color: #a78bfa;
            font-weight: 600;
        }

        .room-name {
            color: #fbbf24;
            font-weight: 600;
        }

        .break-text {
            color: #9ca3af;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobile-header {
            background: #1a1a1a;
            padding: 1rem;
            border-bottom: 1px solid #374151;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .mobile-day-header {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(45deg, #3b82f6, #1d4ed8);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 100;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, #3b82f6, #1d4ed8);
            position: fixed;
            top: 0;
            left: 0;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            transition: all 0.3s ease-in-out;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 1rem 0.5rem;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            opacity: 1;
        }

        .sidebar.collapsed .sidebar-header h2 {
            opacity: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease-in-out;
            white-space: nowrap;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
            transition: all 0.3s ease-in-out;
        }

        .sidebar-menu a span {
            transition: all 0.3s ease-in-out;
            opacity: 1;
        }

        .sidebar.collapsed .sidebar-menu a {
            padding: 0.75rem 1rem;
            justify-content: center;
        }

        .sidebar.collapsed .sidebar-menu a span {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-menu a i {
            margin-right: 0;
            font-size: 1.2rem;
        }

        .sidebar-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            background: linear-gradient(45deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
        }

        .sidebar-toggle.hidden {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-10px);
        }

        .content-wrapper {
            transition: all 0.3s ease-in-out;
            margin-left: 250px;
            min-height: 100vh;
        }

        .sidebar.collapsed + .content-wrapper {
            margin-left: 70px;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 900;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }

            .mobile-view {
                display: block;
            }

            body {
                background: #1a1a1a;
            }

            .gradient-bg {
                background: #1a1a1a;
            }

            /* Mobile-specific styles for glass card */
            .glass-card {
                background: rgba(26, 26, 26, 0.9);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Mobile-specific styles for search section */
            .input-container label {
                color: #94a3b8 !important;
            }

            .input-container input {
                background-color: #2a2a2a !important;
                border-color: #374151 !important;
                color: white !important;
            }

            .input-container input::placeholder {
                color: #6b7280 !important;
            }

            .input-container button {
                background-color: #3b82f6 !important;
            }

            .input-container button:hover {
                background-color: #2563eb !important;
            }

            .suggestions-container {
                background: #1e293b !important;
                border-color: #374151 !important;
            }

            .suggestion-item {
                color: #e5e7eb !important;
                border-bottom-color: #374151 !important;
            }

            .suggestion-item:hover {
                background-color: #334155 !important;
            }

            /* Header text colors for mobile */
            .glass-card h1 {
                color: white !important;
            }

            .glass-card p {
                color: #94a3b8 !important;
            }

            /* Mobile sidebar behavior */
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar.collapsed {
                width: 250px;
                left: -250px;
            }

            .sidebar.collapsed.active {
                left: 0;
            }

            .content-wrapper {
                margin-left: 0 !important;
            }
        }

        @media (min-width: 769px) {
            .desktop-table {
                display: block;
            }

            .mobile-view {
                display: none;
            }

            .floating-btn {
                display: none;
            }

            .sidebar-toggle {
                display: none;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-ring {
            content: '';
            position: absolute;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }
            80%, 100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="text-white text-xl font-bold">Routine Viewer</h2>
    </div>
    <div class="sidebar-menu">
        <a href="#">
            <i class="fas fa-user-graduate"></i>
            <span>Student</span>
        </a>
        <a href="#">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Teacher</span>
        </a>
        <a href="#">
            <i class="fas fa-door-open"></i>
            <span>Room</span>
        </a>
        <a href="#">
            <i class="fas fa-calendar-times"></i>
            <span>Empty</span>
        </a>
        <a href="#">
            <i class="fas fa-clipboard-list"></i>
            <span>Exam</span>
        </a>
    </div>
</div>

<!-- Main Content Wrapper -->
<div class="content-wrapper" id="contentWrapper">
    <div class="min-h-screen py-4 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header Card -->
            <div class="glass-card rounded-2xl shadow-xl p-6 mb-6 animate-fade-in">
                <div class="text-center mb-6">
                    <div class="relative inline-block mb-4">
                        <i class="fas fa-calendar-alt text-4xl text-blue-600"></i>
                        <div class="pulse-ring"></div>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-2">
                        Class Routine Viewer
                    </h1>
                    <p class="text-gray-600 text-sm sm:text-base">
                        View your class schedule by entering your section
                    </p>
                </div>

                <!-- Search Section -->
                <div class="max-w-lg mx-auto">
                    <div class="relative">
                        <label for="section" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter Section
                        </label>
                        <div class="input-container">
                            <div class="relative">
                                <input type="text"
                                       id="section"
                                       name="section"
                                       class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 pr-12 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm sm:text-base"
                                       placeholder="e.g., 61_A, 61_B, 61_N"
                                       autocomplete="off"
                                       oninput="showSuggestions()"/>
                                <button onclick="getRoutine()"
                                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all duration-200 text-sm">
                                    <i class="fas fa-search"></i>
                                    <span class="hidden sm:inline ml-2">Search</span>
                                </button>
                            </div>
                            <div id="suggestions" class="suggestions-container hidden"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loading" class="text-center hidden py-8">
                <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white mb-4"></div>
                <p class="text-white font-medium">Loading routine...</p>
            </div>

            <!-- Error Message -->
            <div id="errorMessage"
                 class="hidden bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p id="errorText" class="text-sm text-red-700"></p>
                </div>
            </div>

            <!-- Results Container -->
            <div id="routineResult" class="animate-fade-in"></div>
        </div>
    </div>
</div>

<!-- Floating Action Button for Mobile -->
<div class="floating-btn" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</div>

<script>
    let allSections = [];
    let currentRoutineData = null;
    let isSidebarCollapsed = false;

    // Toggle sidebar function
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const contentWrapper = document.getElementById('contentWrapper');
        const overlay = document.querySelector('.sidebar-overlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (window.innerWidth <= 768) {
            // Mobile behavior - toggle active state
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');

            // Hide toggle button when sidebar is open
            if (sidebar.classList.contains('active')) {
                toggleBtn.classList.add('hidden');
            } else {
                toggleBtn.classList.remove('hidden');
            }
        } else {
            // Desktop behavior - toggle collapsed state
            isSidebarCollapsed = !isSidebarCollapsed;
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
    }

    // Load section suggestions
    async function loadSections() {
        try {
            const response = await fetch('/api/sections');
            if (!response.ok) {
                throw new Error('Failed to fetch sections');
            }
            const data = await response.json();
            if (data.status === 'success') {
                allSections = data.data;
            }
        } catch (error) {
            console.error('Failed to load sections:', error);
            showError('Failed to load section list. Please refresh the page.');
        }
    }

    // Show suggestions based on input
    function showSuggestions() {
        const input = document.getElementById('section').value.trim().toUpperCase();
        const suggestionsContainer = document.getElementById('suggestions');

        suggestionsContainer.innerHTML = '';
        suggestionsContainer.classList.add('hidden');

        if (!input || input.length < 1) {
            return;
        }

        const filtered = allSections.filter(section =>
            section.toUpperCase().includes(input)
        ).slice(0, 8); // Limit to 8 suggestions

        if (filtered.length === 0) {
            return;
        }

        filtered.forEach(section => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span>${section}</span>
                        <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                    </div>
                `;
            div.onclick = () => {
                document.getElementById('section').value = section;
                suggestionsContainer.classList.add('hidden');
                getRoutine();
            };
            suggestionsContainer.appendChild(div);
        });

        suggestionsContainer.classList.remove('hidden');
    }

    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        const suggestions = document.getElementById('suggestions');
        if (!e.target.closest('#section') && !e.target.closest('#suggestions')) {
            suggestions.classList.add('hidden');
        }
    });

    // Show error message
    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        errorText.textContent = message;
        errorDiv.classList.remove('hidden');
        setTimeout(() => errorDiv.classList.add('hidden'), 5000);
    }

    // Hide error message
    function hideError() {
        document.getElementById('errorMessage').classList.add('hidden');
    }

    // Format time from HH:MM:SS to HH:MM
    function formatTime(timeString) {
        if (!timeString) return '';
        return timeString.substring(0, 5);
    }

    // Create desktop table view
    function createDesktopView(routine) {
        const container = document.createElement('div');
        container.className = 'glass-card rounded-2xl shadow-xl overflow-hidden desktop-table';

        const table = document.createElement('table');
        table.className = 'w-full';

        // Table header
        const headerRow = document.createElement('thead');
        headerRow.innerHTML = `
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Course</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teacher</th>
                </tr>
            `;
        table.appendChild(headerRow);

        const tbody = document.createElement('tbody');
        tbody.className = 'divide-y divide-gray-200';

        for (const day in routine) {
            // Day header row
            const dayHeaderRow = document.createElement('tr');
            dayHeaderRow.className = 'day-row';
            dayHeaderRow.innerHTML = `
                    <td colspan="5" class="px-6 py-3 font-semibold text-center">
                        <i class="fas fa-calendar-day mr-2"></i>
                        ${day.charAt(0).toUpperCase() + day.slice(1).toLowerCase()}
                    </td>
                `;
            tbody.appendChild(dayHeaderRow);

            // Class rows
            routine[day].forEach((item, index) => {
                const row = document.createElement('tr');
                row.className = 'class-row bg-white';
                row.innerHTML = `
                        <td class="px-6 py-4">
                            <span class="time-badge">
                                ${formatTime(item.start_time)} - ${formatTime(item.end_time)}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="course-badge">
                                ${item.course || '-'}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="section-badge">
                                ${item.section || '-'}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <i class="fas fa-door-open mr-1"></i>
                            ${item.room || '-'}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <i class="fas fa-user mr-1"></i>
                            ${item.teacher || '-'}
                        </td>
                    `;
                tbody.appendChild(row);
            });
        }

        table.appendChild(tbody);
        container.appendChild(table);
        return container;
    }

    // Create mobile timeline view
    function createMobileView(routine) {
        const container = document.createElement('div');
        container.className = 'mobile-view mobile-timeline';

        for (const day in routine) {
            const dayContainer = document.createElement('div');
            dayContainer.className = 'p-4';

            // Day header
            const dayHeader = document.createElement('div');
            dayHeader.className = 'mobile-day-header';
            dayHeader.innerHTML = `
                    <i class="fas fa-calendar-day mr-2"></i>
                    ${day.charAt(0).toUpperCase() + day.slice(1).toLowerCase()}
                `;
            dayContainer.appendChild(dayHeader);

            // Process classes with break detection
            const dayClasses = routine[day];
            for (let i = 0; i < dayClasses.length; i++) {
                const currentClass = dayClasses[i];
                const nextClass = dayClasses[i + 1];

                // Add current class
                const classItem = document.createElement('div');
                classItem.className = 'mobile-class-item';

                classItem.innerHTML = `
                        <div class="time-label">
                            ${formatTime(currentClass.start_time)}
                            <span style="font-size: 0.9rem; color: #6b7280;">- ${formatTime(currentClass.end_time)}</span>
                        </div>
                        <div class="course-title">${currentClass.course || 'Unknown Course'}</div>
                        <div class="course-details">
                            <div class="detail-label">Course</div>
                            <div class="detail-value course-code">${currentClass.course || '-'}</div>
                            <div class="detail-label">Section</div>
                            <div class="detail-value section-code">${currentClass.section || '-'}</div>
                            <div class="detail-label">Teacher</div>
                            <div class="detail-value teacher-name">${currentClass.teacher || '-'}</div>
                            <div class="detail-label">Room</div>
                            <div class="detail-value room-name">${currentClass.room || '-'}</div>
                        </div>
                    `;
                dayContainer.appendChild(classItem);

                // Add break time if there's a gap to next class
                if (nextClass) {
                    const currentEndTime = new Date(`1970-01-01T${currentClass.end_time}`);
                    const nextStartTime = new Date(`1970-01-01T${nextClass.start_time}`);
                    const breakDuration = (nextStartTime - currentEndTime) / 1000 / 60; // minutes

                    if (breakDuration > 0) {
                        const breakItem = document.createElement('div');
                        breakItem.className = 'mobile-break-item';

                        const hours = Math.floor(breakDuration / 60);
                        const minutes = breakDuration % 60;
                        let breakText = '';

                        if (hours > 0) {
                            breakText = `${hours}h ${minutes}m Break`;
                        } else {
                            breakText = `${minutes}m Break`;
                        }

                        breakItem.innerHTML = `
                                <div class="break-text">
                                    <i class="fas fa-coffee"></i>
                                    <span>Break Time (Section: ${currentClass.section || 'N/A'})</span>
                                </div>
                                <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">
                                    ${formatTime(currentClass.end_time)} - ${formatTime(nextClass.start_time)} (${breakText})
                                </div>
                            `;
                        dayContainer.appendChild(breakItem);
                    }
                }
            }

            container.appendChild(dayContainer);
        }

        return container;
    }

    // Fetch routine by section
    async function getRoutine() {
        const section = document.getElementById('section').value.trim().toUpperCase();
        const result = document.getElementById('routineResult');
        const loading = document.getElementById('loading');

        result.innerHTML = '';
        hideError();

        if (!section) {
            showError('Please enter a section (e.g., 61_A, 61_B, 61_N)');
            return;
        }

        loading.classList.remove('hidden');

        try {
            const response = await fetch(`/api/routine?section=${encodeURIComponent(section)}`);

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            if (data.status === 'error') {
                showError(data.message || `No schedule found for section ${section}`);
                return;
            }

            const routine = data.data;
            currentRoutineData = routine;

            if (!routine || Object.keys(routine).length === 0) {
                result.innerHTML = `
                        <div class="glass-card rounded-2xl p-6 text-center">
                            <i class="fas fa-calendar-times text-4xl text-yellow-500 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Routine Found</h3>
                            <p class="text-gray-600">No schedule found for section ${section}</p>
                        </div>
                    `;
                return;
            }

            // Create both desktop and mobile views
            const desktopView = createDesktopView(routine);
            const mobileView = createMobileView(routine);

            result.appendChild(desktopView);
            result.appendChild(mobileView);

        } catch (error) {
            console.error('Error fetching routine:', error);
            showError('Failed to fetch routine. Please try again.');
        } finally {
            loading.classList.add('hidden');
        }
    }

    // Scroll to top function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        loadSections();

        // Add event listener for Enter key
        document.getElementById('section').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('suggestions').classList.add('hidden');
                getRoutine();
            }
        });

        // Add touch support for mobile suggestions
        document.addEventListener('touchstart', (e) => {
            const suggestions = document.getElementById('suggestions');
            if (!e.target.closest('#section') && !e.target.closest('#suggestions')) {
                suggestions.classList.add('hidden');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.querySelector('.sidebar-overlay');

            if (window.innerWidth > 768) {
                // Desktop view - ensure sidebar is visible and not in mobile active state
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggleBtn.classList.add('hidden');

                // Reset collapsed state if needed
                if (isSidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                }
            } else {
                // Mobile view - hide sidebar by default
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggleBtn.classList.remove('hidden');
            }
        });
    });
</script>
</body>
</html>

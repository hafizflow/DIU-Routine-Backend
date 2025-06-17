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

        .mobile-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .mobile-card-header {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 1rem;
            font-weight: 600;
        }

        .mobile-card-content {
            padding: 1rem;
        }

        .time-badge {
            background: linear-gradient(90deg, #10b981, #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .course-badge {
            background: linear-gradient(90deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
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

        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }

            .mobile-view {
                display: block;
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
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white mb-4"></div>
            <p class="text-white font-medium">Loading routine...</p>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p id="errorText" class="text-sm text-red-700"></p>
            </div>
        </div>

        <!-- Results Container -->
        <div id="routineResult" class="animate-fade-in"></div>
    </div>
</div>

<!-- Floating Action Button for Mobile -->
<div class="floating-btn" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</div>

<script>
    let allSections = [];
    let currentRoutineData = null;

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
                <td colspan="4" class="px-6 py-3 font-semibold text-center">
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
                    <td class="px-6 py-4 text-gray-600">
                        <i class="fas fa-location-dot mr-1"></i>
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

    // Create mobile card view
    function createMobileView(routine) {
        const container = document.createElement('div');
        container.className = 'mobile-view space-y-4';

        for (const day in routine) {
            const dayCard = document.createElement('div');
            dayCard.className = 'mobile-card';

            const dayHeader = document.createElement('div');
            dayHeader.className = 'mobile-card-header';
            dayHeader.innerHTML = `
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        <i class="fas fa-calendar-day mr-2"></i>
                        ${day.charAt(0).toUpperCase() + day.slice(1).toLowerCase()}
                    </h3>
                    <span class="text-sm opacity-75">${routine[day].length} classes</span>
                </div>
            `;
            dayCard.appendChild(dayHeader);

            const dayContent = document.createElement('div');
            dayContent.className = 'mobile-card-content space-y-3';

            routine[day].forEach((item, index) => {
                const classItem = document.createElement('div');
                classItem.className = 'p-3 bg-gray-50 rounded-lg border-l-4 border-blue-500';
                classItem.innerHTML = `
                    <div class="flex flex-col space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="time-badge">
                                ${formatTime(item.start_time)} - ${formatTime(item.end_time)}
                            </span>
                            <span class="course-badge">
                                ${item.course || '-'}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span>
                                <i class="fas fa-location-dot mr-1"></i>
                                ${item.room || 'No room'}
                            </span>
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                ${item.teacher || 'No teacher'}
                            </span>
                        </div>
                    </div>
                `;
                dayContent.appendChild(classItem);
            });

            dayCard.appendChild(dayContent);
            container.appendChild(dayCard);
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
    });
</script>

</body>
</html>

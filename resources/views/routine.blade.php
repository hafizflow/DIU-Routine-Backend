<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Routine - Student Portal</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .time-highlight {
            background: rgba(102, 126, 234, 0.2);
            border-left: 4px solid #667eea;
        }

        .day-container {
            background: rgba(39, 39, 42, 0.7);
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 0.5);
            margin-bottom: 1.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .day-header {
            background: rgba(55, 65, 81, 0.7);
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen font-sans">
<div x-data="routineApp()" class="min-h-screen">
    <!-- Header -->
    <div class="gradient-bg px-8 pt-8 pb-4">
        <div class="container mx-auto text-center">
            <!-- Main Heading -->
            <h1 class="text-2xl font-bold text-white mb-6">Class Routine Viewer</h1>

            <!-- Search Section -->
            <div class="max-w-md mx-auto bg-white/10 backdrop-blur-sm rounded-xl p-6">
                <!-- Input Label -->
                <label class="block text-left text-white/90 mb-2 text-sm font-medium">Enter Section</label>

                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <input
                            type="text"
                            x-model="searchSection"
                            @keyup.enter="searchRoutine()"
                            placeholder="e.g., 61_A, 61_B, 61_N"
                            class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 text-base"
                        >
                    </div>
                    <button
                        @click="searchRoutine()"
                        :disabled="!searchSection.trim() || loading"
                        class="px-5 py-3 bg-white/30 hover:bg-white/40 disabled:bg-white/20 rounded-lg transition-colors flex items-center justify-center"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto pb-20 max-w-md">
        <!-- Welcome Message -->
        <div x-show="!currentSection && !loading && !error" x-cloak class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="mb-5">
                    <svg class="w-16 h-16 mx-auto text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-9 4v10a2 2 0 002 2h8a2 2 0 002-2V11a2 2 0 00-2-2H7a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-300 mb-3">Search for Class Routine</h3>
                <p class="text-gray-500 mb-4">Enter your section to view the class schedule</p>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" x-cloak class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
            <p class="mt-3 text-gray-400">Loading routine...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" x-cloak
             class="bg-red-900/50 border border-red-500/50 text-red-300 px-5 py-3 rounded-lg mb-6">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-medium">Error Loading Routine</p>
                    <p x-text="error" class="text-sm mt-1"></p>
                </div>
            </div>
        </div>

        <!-- Routine Display -->
        <div x-show="routineData && !loading && !error" x-cloak>
            <template x-for="(dayClasses, dayName) in routineData" :key="dayName">
                <div x-show="dayClasses.length > 0" class="day-container">
                    <!-- Day Header -->
                    <div class="day-header">
                        <div class="flex items-center">
                            <h2 class="text-xl font-bold text-teal-500" x-text="formatDayName(dayName)"></h2>
                            <div class="flex-1 h-px bg-gray-600 mx-3"></div>
                            <div class="text-xs text-gray-300 bg-gray-700 px-2 py-1 rounded-full">
                                <span x-text="dayClasses.length + ' classes'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Classes for the day -->
                    <template x-for="(classItem, index) in dayClasses"
                              :key="classItem.course_code + classItem.start_time + dayName">
                        <div class="border-b border-gray-700 last:border-b-0">
                            <!-- Time Highlight -->
                            <div class="time-highlight px-4 py-2 flex items-center justify-between">
                                <div class="font-bold text-purple-300"
                                     x-text="formatTime(classItem.start_time) + ' - ' + formatTime(classItem.end_time)">
                                </div>
                            </div>

                            <!-- Class Details -->
                            <div class="p-4">
                                <h3 class="text-lg font-semibold mb-3" x-text="classItem.course_title"></h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="flex space-x-2">
                                        <p class="text-gray-400">Course:</p>
                                        <p class="font-medium" x-text="classItem.course_code"></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <p class="text-gray-400">Section:</p>
                                        <p class="font-medium" x-text="classItem.section"></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <p class="text-gray-400">Teacher:</p>
                                        <p class="text-blue-400 font-bold cursor-pointer hover:text-blue-700 transition-colors"
                                           @click="showTeacherDetails(classItem.teacher_info, classItem.teacher)"
                                           x-text="classItem.teacher"></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <p class="text-gray-400">Room:</p>
                                        <p class="text-green-300 font-medium" x-text="classItem.room"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- No classes message -->
            <div x-show="!hasAnyClasses()" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-400 mb-2">No Classes Found</h3>
                <p class="text-gray-500">No scheduled classes for this section</p>
            </div>
        </div>
    </div>

    <!-- Teacher Details Modal -->
    <div x-show="showTeacherModal" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 px-8">
        <div class="bg-gray-800 w-full max-w-md rounded-lg overflow-hidden"
             @click.away="showTeacherModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <!-- Teacher Image in rounded container -->
                        <div
                            class="relative h-14 w-14 rounded-full bg-gradient-to-r from-purple-600 to-blue-500 overflow-hidden flex-shrink-0">
                            <!-- Default avatar if no image available -->
                            <template x-if="!selectedTeacher.info?.image_url">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </template>

                            <!-- Teacher image if available -->
                            <img x-show="selectedTeacher.info?.image_url"
                                 :src="selectedTeacher.info?.image_url"
                                 :alt="selectedTeacher.name || selectedTeacher.shortName"
                                 class="w-full h-full object-cover">
                        </div>

                        <div>
                            <h3 class="text-xl font-bold"
                                x-text="selectedTeacher.name || selectedTeacher.shortName"></h3>
                            <p x-show="selectedTeacher.info?.designation"
                               class="text-sm text-gray-300"
                               x-text="selectedTeacher.info?.designation || 'N/A'"></p>
                        </div>
                    </div>

                    <button @click="showTeacherModal = false" class="text-gray-400 hover:text-white p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div x-show="selectedTeacher.info" class="space-y-3 text-sm">
                    <div class="flex items-center py-2 border-b border-gray-700">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a :href="'mailto:' + selectedTeacher.info?.email"
                           class="text-blue-300 font-medium hover:text-blue-200"
                           x-text="selectedTeacher.info?.email || 'N/A'"></a>
                    </div>
                    <div class="flex items-center py-2">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a :href="'tel:' + selectedTeacher.info?.cell_phone"
                           class="text-blue-300 font-medium hover:text-blue-200"
                           x-text="selectedTeacher.info?.cell_phone || 'N/A'"></a>
                    </div>
                </div>

                <div x-show="!selectedTeacher.info" class="text-center py-4">
                    <p class="text-gray-500">No additional information available</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-800 px-6 py-3">
        <div class="flex justify-around items-center max-w-md mx-auto">
            <div class="flex flex-col items-center text-blue-400">
                <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                </svg>
                <span class="text-xs">Student</span>
            </div>
            <div class="flex flex-col items-center text-gray-500">
                <svg class="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                </svg>
                <span class="text-xs">Teacher</span>
            </div>
            <div class="flex flex-col items-center text-gray-500">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="text-xs">Room</span>
            </div>
            <div class="flex flex-col items-center text-gray-500">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-xs">Empty</span>
            </div>
        </div>
    </div>
</div>

<script>
    function routineApp() {
        return {
            routineData: null,
            loading: false,
            error: null,
            searchSection: '',
            currentSection: null,
            showTeacherModal: false,
            selectedTeacher: {},

            searchRoutine() {
                if (!this.searchSection.trim()) return;
                this.fetchRoutine(this.searchSection.trim());
            },

            clearSearch() {
                this.searchSection = '';
                this.currentSection = null;
                this.routineData = null;
                this.error = null;
            },

            async fetchRoutine(section) {
                try {
                    this.loading = true;
                    this.error = null;

                    const response = await fetch(`https://diu.zahidp.xyz/api/routine?section=${section}`);

                    if (!response.ok) {
                        if (response.status === 404) {
                            throw new Error(`No routine found for section "${section}"`);
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.routineData = data.data;
                        this.currentSection = section;

                        const hasClasses = Object.values(data.data).some(dayClasses => dayClasses.length > 0);
                        if (!hasClasses) {
                            this.error = `No classes found for section "${section}"`;
                            this.routineData = null;
                            this.currentSection = null;
                        }
                    } else {
                        throw new Error(data.message || 'API returned error status');
                    }
                } catch (err) {
                    this.error = err.message || 'Failed to load routine data';
                    this.routineData = null;
                    this.currentSection = null;
                } finally {
                    this.loading = false;
                }
            },

            hasAnyClasses() {
                if (!this.routineData) return false;
                return Object.values(this.routineData).some(dayClasses => dayClasses.length > 0);
            },

            formatDayName(dayName) {
                return dayName.charAt(0) + dayName.slice(1).toLowerCase();
            },

            showTeacherDetails(teacherInfo, shortName) {
                this.selectedTeacher = {
                    name: teacherInfo?.name || shortName,
                    shortName: shortName,
                    info: teacherInfo
                };
                this.showTeacherModal = true;
            },

            formatTime(timeString) {
                try {
                    const [hours, minutes] = timeString.split(':');
                    const hour = parseInt(hours);
                    const minute = minutes;

                    if (hour === 0) return `12:${minute}`;
                    if (hour < 12) return `${hour}:${minute}`;
                    if (hour === 12) return `12:${minute}`;
                    return `${hour - 12}:${minute}`;
                } catch (err) {
                    return timeString;
                }
            }
        }
    }
</script>
</body>
</html>

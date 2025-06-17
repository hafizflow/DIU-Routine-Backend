import React, {useState, useEffect} from 'react';
import {
    FaBars,
    FaSearch,
    FaArrowUp,
    FaCalendarAlt,
    FaCalendarDay,
    FaDoorOpen,
    FaUser,
    FaCoffee,
    FaExclamationCircle,
    FaGraduationCap,
    FaChalkboardTeacher,
    FaClipboardList
} from 'react-icons/fa';

function App() {
    const [allSections, setAllSections] = useState([]);
    const [sectionInput, setSectionInput] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [filteredSuggestions, setFilteredSuggestions] = useState([]);
    const [routineData, setRoutineData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);
    const [isSidebarActive, setIsSidebarActive] = useState(false);
    const [isMobileView, setIsMobileView] = useState(window.innerWidth <= 768);

    // Load sections on component mount
    useEffect(() => {
        const loadSections = async () => {
            try {
                const response = await fetch('/api/sections');
                if (!response.ok) {
                    throw new Error('Failed to fetch sections');
                }
                const data = await response.json();
                if (data.status === 'success') {
                    setAllSections(data.data);
                } else {
                    throw new Error(data.message || 'Failed to load sections');
                }
            } catch (err) {
                console.error('Failed to load sections:', err);
                showError('Failed to load section list. Please refresh the page.');
            }
        };

        loadSections();

        // Handle window resize
        const handleResize = () => {
            setIsMobileView(window.innerWidth <= 768);
        };

        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    // Toggle sidebar
    const toggleSidebar = () => {
        if (isMobileView) {
            setIsSidebarActive(!isSidebarActive);
            // Hide toggle button when sidebar is open
            if (!isSidebarActive) {
                document.getElementById('sidebarToggle').style.display = 'none';
            } else {
                document.getElementById('sidebarToggle').style.display = 'flex';
            }
        } else {
            setIsSidebarCollapsed(!isSidebarCollapsed);
        }
    };

    // Show suggestions based on input
    const handleInputChange = (e) => {
        const input = e.target.value.trim().toUpperCase();
        setSectionInput(e.target.value);
        setError(null);

        if (!input || input.length < 1) {
            setShowSuggestions(false);
            return;
        }

        const filtered = allSections.filter(section =>
            section.toUpperCase().includes(input))
            .slice(0, 8); // Limit to 8 suggestions

        setFilteredSuggestions(filtered);
        setShowSuggestions(filtered.length > 0);
    };

    // Select a suggestion
    const selectSuggestion = (section) => {
        setSectionInput(section);
        setShowSuggestions(false);
        getRoutine(section);
    };

    // Show error message
    const showError = (message) => {
        setError(message);
        setTimeout(() => setError(null), 5000);
    };

    // Format time from HH:MM:SS to HH:MM
    const formatTime = (timeString) => {
        if (!timeString) return '';
        return timeString.substring(0, 5);
    };

    // Fetch routine by section
    const getRoutine = async (section = null) => {
        const sectionToFetch = section || sectionInput.trim().toUpperCase();
        setError(null);

        if (!sectionToFetch) {
            showError('Please enter a section (e.g., 61_A, 61_B, 61_N)');
            return;
        }

        setLoading(true);
        setRoutineData(null);

        try {
            const response = await fetch(`/api/routine?section=${encodeURIComponent(sectionToFetch)}`);

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            if (data.status === 'error') {
                showError(data.message || `No schedule found for section ${sectionToFetch}`);
                return;
            }

            setRoutineData(data.data);

        } catch (err) {
            console.error('Error fetching routine:', err);
            showError('Failed to fetch routine. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    // Scroll to top function
    const scrollToTop = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    // Desktop table view component
    const DesktopTableView = ({routine}) => {
        return (
            <div className="glass-card rounded-2xl shadow-xl overflow-hidden desktop-table">
                <table className="w-full">
                    <thead>
                    <tr className="bg-gradient-to-r from-gray-50 to-gray-100">
                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Course</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teacher</th>
                    </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                    {Object.entries(routine).map(([day, classes]) => (
                        <React.Fragment key={day}>
                            <tr className="day-row">
                                <td colSpan="5" className="px-6 py-3 font-semibold text-center">
                                    <FaCalendarDay className="inline mr-2"/>
                                    {day.charAt(0).toUpperCase() + day.slice(1).toLowerCase()}
                                </td>
                            </tr>
                            {classes.map((item, index) => (
                                <tr key={index}
                                    className="class-row bg-white hover:bg-gray-50 transition-all duration-200">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {formatTime(item.start_time)} - {formatTime(item.end_time)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {item.course || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {item.section || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-gray-600">
                                        <FaDoorOpen className="inline mr-1"/>
                                        {item.room || '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-gray-600">
                                        <FaUser className="inline mr-1"/>
                                        {item.teacher || '-'}
                                    </td>
                                </tr>
                            ))}
                        </React.Fragment>
                    ))}
                    </tbody>
                </table>
            </div>
        );
    };

    // Mobile timeline view component
    const MobileTimelineView = ({routine}) => {
        return (
            <div className="mobile-view mobile-timeline">
                {Object.entries(routine).map(([day, classes]) => (
                    <div key={day} className="p-4">
                        <div className="mobile-day-header">
                            <FaCalendarDay className="inline mr-2"/>
                            {day.charAt(0).toUpperCase() + day.slice(1).toLowerCase()}
                        </div>

                        {classes.map((currentClass, index) => {
                            const nextClass = classes[index + 1];
                            let breakItem = null;

                            // Calculate break time if there's a next class
                            if (nextClass) {
                                const currentEndTime = new Date(`1970-01-01T${currentClass.end_time}`);
                                const nextStartTime = new Date(`1970-01-01T${nextClass.start_time}`);
                                const breakDuration = (nextStartTime - currentEndTime) / 1000 / 60; // minutes

                                if (breakDuration > 0) {
                                    const hours = Math.floor(breakDuration / 60);
                                    const minutes = breakDuration % 60;
                                    let breakText = '';

                                    if (hours > 0) {
                                        breakText = `${hours}h ${minutes}m Break`;
                                    } else {
                                        breakText = `${minutes}m Break`;
                                    }

                                    breakItem = (
                                        <div key={`break-${index}`} className="mobile-break-item">
                                            <div className="break-text">
                                                <FaCoffee className="inline mr-1"/>
                                                <span>Break Time (Section: {currentClass.section || 'N/A'})</span>
                                            </div>
                                            <div className="text-xs text-gray-400 mt-1">
                                                {formatTime(currentClass.end_time)} - {formatTime(nextClass.start_time)} ({breakText})
                                            </div>
                                        </div>
                                    );
                                }
                            }

                            return (
                                <React.Fragment key={index}>
                                    <div className="mobile-class-item">
                                        <div className="time-label">
                                            {formatTime(currentClass.start_time)}
                                            <span
                                                className="text-xs text-gray-500 ml-1">- {formatTime(currentClass.end_time)}</span>
                                        </div>
                                        <div className="course-title">{currentClass.course || 'Unknown Course'}</div>
                                        <div className="course-details">
                                            <div className="detail-label">Course</div>
                                            <div className="detail-value course-code">{currentClass.course || '-'}</div>
                                            <div className="detail-label">Section</div>
                                            <div
                                                className="detail-value section-code">{currentClass.section || '-'}</div>
                                            <div className="detail-label">Teacher</div>
                                            <div
                                                className="detail-value teacher-name">{currentClass.teacher || '-'}</div>
                                            <div className="detail-label">Room</div>
                                            <div className="detail-value room-name">{currentClass.room || '-'}</div>
                                        </div>
                                    </div>
                                    {breakItem}
                                </React.Fragment>
                            );
                        })}
                    </div>
                ))}
            </div>
        );
    };

    return (
        <div className={`gradient-bg min-h-screen ${isMobileView ? 'bg-gray-900' : ''}`}>
            {/* Sidebar Toggle Button */}
            <button
                id="sidebarToggle"
                className="sidebar-toggle"
                onClick={toggleSidebar}
            >
                <FaBars/>
            </button>

            {/* Sidebar Overlay (for mobile) */}
            {isSidebarActive && (
                <div
                    className="sidebar-overlay active"
                    onClick={toggleSidebar}
                />
            )}

            {/* Sidebar Navigation */}
            <div
                id="sidebar"
                className={`sidebar ${isSidebarCollapsed ? 'collapsed' : ''} ${isSidebarActive ? 'active' : ''}`}
            >
                <div className="sidebar-header">
                    <h2 className="text-white text-xl font-bold">Routine Viewer</h2>
                </div>
                <div className="sidebar-menu">
                    <a href="#">
                        <FaGraduationCap/>
                        <span>Student</span>
                    </a>
                    <a href="#">
                        <FaChalkboardTeacher/>
                        <span>Teacher</span>
                    </a>
                    <a href="#">
                        <FaDoorOpen/>
                        <span>Room</span>
                    </a>
                    <a href="#">
                        <FaCalendarAlt/>
                        <span>Empty</span>
                    </a>
                    <a href="#">
                        <FaClipboardList/>
                        <span>Exam</span>
                    </a>
                </div>
            </div>

            {/* Main Content Wrapper */}
            <div
                id="contentWrapper"
                className={`content-wrapper ${isSidebarCollapsed && !isMobileView ? 'collapsed' : ''}`}
            >
                <div className="min-h-screen py-4 px-4 sm:px-6 lg:px-8">
                    <div className="max-w-7xl mx-auto">
                        {/* Header Card */}
                        <div className="glass-card rounded-2xl shadow-xl p-6 mb-6 animate-fade-in">
                            <div className="text-center mb-6">
                                <div className="relative inline-block mb-4">
                                    <FaCalendarAlt className="text-4xl text-blue-600"/>
                                    <div className="pulse-ring"></div>
                                </div>
                                <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-2">
                                    Class Routine Viewer
                                </h1>
                                <p className="text-gray-600 text-sm sm:text-base">
                                    View your class schedule by entering your section
                                </p>
                            </div>

                            {/* Search Section */}
                            <div className="max-w-lg mx-auto">
                                <div className="relative">
                                    <label htmlFor="section" className="block text-sm font-medium text-gray-700 mb-2">
                                        Enter Section
                                    </label>
                                    <div className="input-container">
                                        <div className="relative">
                                            <input
                                                type="text"
                                                id="section"
                                                name="section"
                                                className="w-full border-2 border-gray-300 rounded-xl px-4 py-3 pr-12 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm sm:text-base"
                                                placeholder="e.g., 61_A, 61_B, 61_N"
                                                autoComplete="off"
                                                value={sectionInput}
                                                onChange={handleInputChange}
                                                onKeyPress={(e) => e.key === 'Enter' && getRoutine()}
                                            />
                                            <button
                                                onClick={() => getRoutine()}
                                                className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all duration-200 text-sm"
                                            >
                                                <FaSearch/>
                                                <span className="hidden sm:inline ml-2">Search</span>
                                            </button>
                                        </div>
                                        {showSuggestions && (
                                            <div className="suggestions-container">
                                                {filteredSuggestions.map((section, index) => (
                                                    <div
                                                        key={index}
                                                        className="suggestion-item"
                                                        onClick={() => selectSuggestion(section)}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span>{section}</span>
                                                            <FaArrowRight className="text-gray-400 text-xs"/>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Loading Indicator */}
                        {loading && (
                            <div id="loading" className="text-center py-8">
                                <div
                                    className="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white mb-4"></div>
                                <p className="text-white font-medium">Loading routine...</p>
                            </div>
                        )}

                        {/* Error Message */}
                        {error && (
                            <div id="errorMessage"
                                 className="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl animate-fade-in">
                                <div className="flex items-center">
                                    <FaExclamationCircle className="text-red-500 mr-3"/>
                                    <p id="errorText" className="text-sm text-red-700">{error}</p>
                                </div>
                            </div>
                        )}

                        {/* Results Container */}
                        <div id="routineResult" className="animate-fade-in">
                            {routineData ? (
                                <>
                                    {!isMobileView && <DesktopTableView routine={routineData}/>}
                                    {isMobileView && <MobileTimelineView routine={routineData}/>}
                                </>
                            ) : (
                                !loading && routineData === null && (
                                    <div className="glass-card rounded-2xl p-6 text-center">
                                        <FaCalendarAlt className="text-4xl text-blue-500 mb-4 mx-auto"/>
                                        <h3 className="text-lg font-semibold text-gray-800 mb-2">Search for a
                                            Section</h3>
                                        <p className="text-gray-600">Enter a section to view the class routine</p>
                                    </div>
                                )
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Floating Action Button for Mobile */}
            {isMobileView && (
                <div className="floating-btn" onClick={scrollToTop}>
                    <FaArrowUp/>
                </div>
            )}
        </div>
    );
}

export default App;

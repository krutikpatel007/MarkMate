<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SCSA Attendance')</title>
    <script>
        // Inline theme initialization to prevent UI flashing on reload
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark-theme');
        } else {
            document.documentElement.classList.remove('dark-theme');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
@auth
    <div class="shell">
        <!-- Backdrop Overlay for Mobile Navigation -->
        <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

        <aside class="sidebar" aria-label="Main navigation">
            <div class="brand" style="position: relative;">
                <strong>Shreyarth University</strong>
                <span>SCSA Department</span>
                <!-- Sidebar Close Button for Mobile -->
                <button type="button" id="sidebar-close" class="sidebar-close-btn" aria-label="Close Navigation Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--border-radius-lg); padding: 0.75rem; margin-top: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <!-- Initials Avatar -->
                    <div style="width: 2.25rem; height: 2.25rem; border-radius: 50%; background: linear-gradient(135deg, var(--color-scsa-accent) 0%, var(--color-scsa-accent-deep) 100%); display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 700; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.15); flex-shrink: 0;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(strrchr(auth()->user()->name, ' ') ?: ' ', 1, 1)) }}
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ auth()->user()->name }}</div>
                        <div style="font-size: 0.6875rem; font-weight: 700; color: var(--color-scsa-gold); text-transform: uppercase; letter-spacing: 0.04em;">{{ auth()->user()->role }}</div>
                    </div>
                </div>
            </div>

            <nav class="nav">
                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>

                @if(auth()->user()->isStudent())
                    <a href="{{ route('leaves.student.index') }}"
                       class="nav-link {{ request()->routeIs('leaves.student.*') ? 'is-active' : '' }}">Leave Applications</a>
                    <a href="{{ route('marks.student') }}"
                       class="nav-link {{ request()->routeIs('marks.student') ? 'is-active' : '' }}">Internal Marks</a>
                @endif
                
                @if(auth()->user()->isFaculty())
                    <a href="{{ route('leaves.faculty.index') }}"
                       class="nav-link {{ request()->routeIs('leaves.faculty.index') ? 'is-active' : '' }}">Leave Applications</a>
                @endif

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('departments.index') }}"
                       class="nav-link {{ request()->routeIs('departments.*') ? 'is-active' : '' }}">Departments</a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                    <a href="{{ route('programs.index') }}"
                       class="nav-link {{ request()->routeIs('programs.*') ? 'is-active' : '' }}">Programs</a>
                    <a href="{{ route('setup.index') }}"
                       class="nav-link {{ request()->routeIs('setup.*') ? 'is-active' : '' }}">Academic Setup</a>
                    <a href="{{ route('academics.index') }}"
                       class="nav-link {{ request()->routeIs('academics.*') ? 'is-active' : '' }}">Classes &amp; Students</a>
                    <a href="{{ route('staff.index') }}"
                       class="nav-link {{ request()->routeIs('staff.*') ? 'is-active' : '' }}">Staff Users</a>
                    <a href="{{ route('attendance.monitor') }}"
                       class="nav-link {{ request()->routeIs('attendance.monitor*') ? 'is-active' : '' }}">Attendance Monitor</a>
                    <a href="{{ route('attendance-corrections.index') }}"
                       class="nav-link {{ request()->routeIs('attendance-corrections.*') ? 'is-active' : '' }}">Correction Requests</a>
                    <a href="{{ route('assignments.index') }}"
                       class="nav-link {{ request()->routeIs('assignments.*') ? 'is-active' : '' }}">Faculty Assignments</a>
                    <a href="{{ route('timetables.index') }}"
                       class="nav-link {{ request()->routeIs('timetables.*') ? 'is-active' : '' }}">Timetable</a>
                    <a href="{{ route('notices.index') }}"
                       class="nav-link {{ request()->routeIs('notices.*') ? 'is-active' : '' }}">Notice Management</a>
                    <a href="{{ route('extra-lectures.index') }}"
                       class="nav-link {{ request()->routeIs('extra-lectures.*') ? 'is-active' : '' }}">Extra Lectures</a>
                    <a href="{{ route('leaves.hod.index') }}"
                       class="nav-link {{ request()->routeIs('leaves.hod.*') ? 'is-active' : '' }}">Student Leaves</a>
                    <a href="{{ route('leaves.faculty.hod.index') }}"
                       class="nav-link {{ request()->routeIs('leaves.faculty.hod.*') ? 'is-active' : '' }}">Faculty Leaves</a>
                    <a href="{{ route('reports.index') }}"
                       class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">Reports</a>
                    <a href="{{ route('marks.index') }}"
                       class="nav-link {{ request()->routeIs('marks.*') ? 'is-active' : '' }}">Internal Marks</a>
                    <a href="{{ route('attendance.heatmap') }}"
                       class="nav-link {{ request()->routeIs('attendance.heatmap*') ? 'is-active' : '' }}">Attendance Heatmap</a>
                    <a href="{{ route('defaulters.index') }}"
                       class="nav-link {{ request()->routeIs('defaulters.*') ? 'is-active' : '' }}">Defaulter System</a>
                @endif

                @if(auth()->user()->isFaculty())
                    <a href="{{ route('timetables.faculty') }}"
                       class="nav-link {{ request()->routeIs('timetables.faculty') ? 'is-active' : '' }}">My Timetable</a>
                    <a href="{{ route('notices.index') }}"
                       class="nav-link {{ request()->routeIs('notices.*') ? 'is-active' : '' }}">Notice Management</a>
                    <a href="{{ route('extra-lectures.index') }}"
                       class="nav-link {{ request()->routeIs('extra-lectures.*') ? 'is-active' : '' }}">Extra Lectures</a>
                    <a href="{{ route('attendance-corrections.index') }}"
                       class="nav-link {{ request()->routeIs('attendance-corrections.*') ? 'is-active' : '' }}">Correction Requests</a>
                    <a href="{{ route('leaves.hod.index') }}"
                       class="nav-link {{ request()->routeIs('leaves.hod.*') ? 'is-active' : '' }}">Student Leaves</a>
                    <a href="{{ route('marks.index') }}"
                       class="nav-link {{ request()->routeIs('marks.*') ? 'is-active' : '' }}">Internal Marks</a>
                @endif

                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="main" id="main-content">
            <div class="topbar">
                <div style="display: flex; align-items: center; gap: 0.75rem; width: 100%;">
                    <!-- Hamburger Menu Button for Mobile Drawer Navigation -->
                    <button type="button" id="sidebar-toggle" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div style="flex: 1; min-width: 0;">
                        <span style="font-size: 0.6875rem; font-weight: 700; color: var(--color-scsa-gold); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.15rem;">Shreyarth University Portal</span>
                        <h1 style="display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                            @yield('page-title', 'Dashboard')
                            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Toggle theme" title="Toggle theme mode">
                                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.2rem; height: 1.2rem; display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.2rem; height: 1.2rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>
                            </button>
                        </h1>
                        <div class="muted">@yield('page-subtitle')</div>
                    </div>
                </div>
                @yield('page-actions')
            </div>

            @if(session('status'))
                <div class="alert" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert error" role="alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@else
    <main id="main-content">
        @yield('content')
    </main>
@endauth

<script>
    (function() {
        function initTheme() {
            const toggleBtn = document.getElementById('theme-toggle');
            if (!toggleBtn) return;
            
            const sunIcon = toggleBtn.querySelector('.sun-icon');
            const moonIcon = toggleBtn.querySelector('.moon-icon');
            
            function updateIcons(theme) {
                if (theme === 'dark') {
                    if (sunIcon) sunIcon.style.setProperty('display', 'block', 'important');
                    if (moonIcon) moonIcon.style.setProperty('display', 'none', 'important');
                } else {
                    if (sunIcon) sunIcon.style.setProperty('display', 'none', 'important');
                    if (moonIcon) moonIcon.style.setProperty('display', 'block', 'important');
                }
            }
            
            // Initial setups
            const activeTheme = localStorage.getItem('theme') || 'light';
            updateIcons(activeTheme);
            
            toggleBtn.addEventListener('click', function() {
                const isDark = document.documentElement.classList.toggle('dark-theme');
                const theme = isDark ? 'dark' : 'light';
                localStorage.setItem('theme', theme);
                updateIcons(theme);
            });
        }

        function initSidebarDrawer() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle = document.getElementById('sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close');
            
            if (sidebar && overlay && toggle) {
                function openDrawer() {
                    sidebar.classList.add('is-open');
                    overlay.classList.add('is-active');
                    document.body.style.overflow = 'hidden';
                }
                
                function closeDrawer() {
                    sidebar.classList.remove('is-open');
                    overlay.classList.remove('is-active');
                    document.body.style.overflow = '';
                }
                
                toggle.addEventListener('click', openDrawer);
                overlay.addEventListener('click', closeDrawer);
                if (closeBtn) {
                    closeBtn.addEventListener('click', closeDrawer);
                }

                // Close drawer on link clicks
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', closeDrawer);
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initTheme();
                initSidebarDrawer();
            });
        } else {
            initTheme();
            initSidebarDrawer();
        }
    })();
</script>
</body>
</html>

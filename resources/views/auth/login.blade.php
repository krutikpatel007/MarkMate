@extends('layouts.app')

@section('title', 'University Portal Login | SCSA Attendance')

@section('content')
<style>
    /* Staggered entrance animation keyframes */
    @keyframes fadeSlideUp {
        0% {
            opacity: 0;
            transform: translateY(16px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Floating bobbing crest keyframe */
    @keyframes floatCrest {
        0%, 100% {
            transform: translateY(0) scale(1);
        }
        50% {
            transform: translateY(-4px) scale(1.02);
        }
    }

    /* Ambient mesh orbit keyframes */
    @keyframes orbit-1 {
        0%, 100% {
            transform: translate(0, 0) scale(1);
        }
        33% {
            transform: translate(30px, -40px) scale(1.15);
        }
        66% {
            transform: translate(-20px, 20px) scale(0.9);
        }
    }

    @keyframes orbit-2 {
        0%, 100% {
            transform: translate(0, 0) scale(1);
        }
        33% {
            transform: translate(-40px, 30px) scale(0.9);
        }
        66% {
            transform: translate(25px, -20px) scale(1.1);
        }
    }

    /* Button sweeping shimmer sheen */
    @keyframes sweepShimmer {
        0% {
            left: -150%;
        }
        50% {
            left: 150%;
        }
        100% {
            left: 150%;
        }
    }

    /* Demo card active pulse */
    @keyframes pulseField {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(13, 148, 136, 0);
        }
        50% {
            box-shadow: 0 0 0 5px rgba(13, 148, 136, 0.25);
            border-color: var(--color-scsa-accent);
        }
    }

    /* Apply animations to classes */
    .animate-entrance {
        opacity: 0;
        animation: fadeSlideUp 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        animation-delay: var(--anim-delay, 0s);
    }

    .crest-float {
        animation: floatCrest 6s ease-in-out infinite;
    }

    /* Ambient Background Orbs */
    .aurora-container {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        overflow: hidden;
        z-index: 0;
        pointer-events: none;
    }

    .aurora-orb {
        position: absolute;
        width: clamp(350px, 45vw, 600px);
        height: clamp(350px, 45vw, 600px);
        border-radius: 50%;
        filter: blur(120px);
        opacity: 0.25;
        mix-blend-mode: multiply;
        transition: all 0.5s ease;
    }

    .aurora-orb-1 {
        background: radial-gradient(circle, var(--color-scsa-accent) 0%, transparent 70%);
        top: -10%;
        right: -10%;
        animation: orbit-1 22s ease-in-out infinite;
    }

    .aurora-orb-2 {
        background: radial-gradient(circle, var(--color-scsa-gold) 0%, transparent 70%);
        bottom: -10%;
        left: -10%;
        animation: orbit-2 26s ease-in-out infinite;
        opacity: 0.12;
    }

    /* Glassmorphism Card base */
    .auth-card-premium {
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        background: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        box-shadow: 
            0 4px 6px -1px rgba(15, 23, 42, 0.01),
            0 20px 40px -4px rgba(15, 23, 42, 0.08),
            inset 0 1px 0 0 rgba(255, 255, 255, 0.6) !important;
    }

    /* Premium fields style */
    .field-premium input {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .field-premium:focus-within label {
        color: var(--color-scsa-accent-deep) !important;
        transform: translateY(-1px);
    }

    .field-premium:focus-within svg {
        color: var(--color-scsa-accent) !important;
        transform: scale(1.1);
    }

    .field-premium svg {
        transition: all 0.25s ease !important;
    }

    /* Button Shimmer elements */
    .shimmer-btn-wrap {
        position: relative;
        overflow: hidden;
    }

    .shimmer-btn-wrap::after {
        content: '';
        position: absolute;
        top: 0;
        left: -150%;
        width: 50%;
        height: 100%;
        background: linear-gradient(
            to right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.35) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: skewX(-25deg);
        animation: sweepShimmer 6s infinite ease-in-out;
    }
    
    .shimmer-btn-wrap:hover::after {
        animation: sweepShimmer 1.5s infinite ease-in-out;
    }

    /* Clickable Demo Cards */
    .demo-card-clickable {
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        user-select: none;
    }

    .demo-card-clickable:hover {
        border-color: var(--color-scsa-accent) !important;
        background: #f0fdfa !important;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.08);
    }

    .demo-card-clickable:active {
        transform: translateY(0px) scale(0.98);
    }

    /* Pulse Highlight Class */
    .pulsing-highlight {
        animation: pulseField 0.8s ease-in-out;
    }
</style>

<div class="auth-wrap" style="position: relative; overflow: hidden; background: linear-gradient(180deg, #f8fafc 0%, #edf2f7 100%);">
    <!-- Ambient Aurora Mesh Orbs -->
    <div class="aurora-container">
        <div class="aurora-orb aurora-orb-1"></div>
        <div class="aurora-orb aurora-orb-2"></div>
    </div>

    <div class="auth-card auth-card-premium animate-entrance" style="position: relative; overflow: hidden; border-radius: 1.25rem; padding: 2.25rem; --anim-delay: 0.05s;">
        <!-- Prestigious Top Gold Ribbon -->
        <div style="height: 4px; background: linear-gradient(90deg, var(--color-scsa-accent) 0%, var(--color-scsa-gold) 100%); width: 100%; position: absolute; top: 0; left: 0;"></div>
        
        <div class="animate-entrance" style="text-align: center; margin-bottom: 1.75rem; --anim-delay: 0.15s;">
            <!-- Official Horizontal University Logo -->
            <div class="crest-float" style="margin-top: 0; margin-bottom: 0.5rem; display: inline-block; width: 100%;">
                <img src="{{ asset('su_logo_horizontal.png') }}" alt="Shreyarth University Logo" style="max-height: 100%; width: 100%; object-fit: contain;">
            </div>

            <div style="margin-top: 0.25rem;">
                <h1 style="font-size: 1.625rem; font-weight: 850; color: var(--color-scsa-sidebar); margin-bottom: 0.25rem; letter-spacing: -0.04em;">System Gateway</h1>
                <p class="muted" style="font-size: 0.8125rem; font-weight: 600; color: var(--color-scsa-muted); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">School of Computer Science &amp; Applications</p>
            </div>
        </div>

        @if($errors->any())
            <div class="alert error animate-entrance" style="padding: 0.75rem 1rem; font-size: 0.8125rem; border-radius: 0.5rem; margin-bottom: 1.25rem; --anim-delay: 0.2s;">
                @foreach($errors->all() as $error)
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <div class="field field-premium animate-entrance" style="position: relative; --anim-delay: 0.25s;">
                <label for="username" style="font-size: 0.8125rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem; transition: color 0.2s ease;">Username / Enrollment No.</label>
                <div style="position: relative;">
                    <!-- Inline Vector User Profile Icon -->
                    <span style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: #94a3b8; display: flex; align-items: center; pointer-events: none;">
                        <svg style="width: 1.125rem; height: 1.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </span>
                    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" autofocus required style="padding: 0.625rem 0.75rem 0.625rem 2.625rem; font-size: 0.875rem; border-radius: 0.5rem; width: 100%;">
                </div>
            </div>

            <div class="field field-premium animate-entrance" style="position: relative; margin-top: 1rem; --anim-delay: 0.35s;">
                <label for="password" style="font-size: 0.8125rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem; transition: color 0.2s ease;">Password</label>
                <div style="position: relative;">
                    <!-- Inline Vector Lock Security Icon -->
                    <span style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: #94a3b8; display: flex; align-items: center; pointer-events: none;">
                        <svg style="width: 1.125rem; height: 1.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </span>
                    <input id="password" name="password" type="password" autocomplete="current-password" required style="padding: 0.625rem 0.75rem 0.625rem 2.625rem; font-size: 0.875rem; border-radius: 0.5rem; width: 100%;">
                </div>
            </div>

            <div class="animate-entrance" style="display: flex; justify-content: space-between; align-items: center; margin: 1.125rem 0; font-size: 0.8125rem; --anim-delay: 0.4s;">
                <label class="radio-option" style="margin: 0; display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid transparent;">
                    <input type="checkbox" name="remember" value="1" style="width: auto; margin: 0; cursor: pointer;">
                    <span style="font-weight: 600; color: #475569; user-select: none;">Keep me signed in</span>
                </label>
            </div>

            <button class="button shimmer-btn-wrap animate-entrance" type="submit" style="width: 100%; min-height: 2.75rem; font-size: 0.9375rem; font-weight: 700; background: linear-gradient(135deg, var(--color-scsa-accent) 0%, var(--color-scsa-accent-deep) 100%); border: 0; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); --anim-delay: 0.45s;">Sign In to Portal</button>
        </form>

        @if(config('app.env') !== 'production')
        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--color-scsa-line); font-size: 0.75rem; line-height: 1.5; --anim-delay: 0.55s;" class="muted animate-entrance">
            <strong style="color: var(--color-scsa-sidebar);">Standard Demo Accounts (Click to Auto-fill):</strong>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.35rem 0.875rem; margin-top: 0.5rem;">
                <div class="demo-card-clickable" onclick="quickFill('admin', 'admin123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">ADMINISTRATOR</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">admin / admin123</code>
                </div>
                <div class="demo-card-clickable" onclick="quickFill('hod', 'hod123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">HEAD OF DEPT (HOD)</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">hod / hod123</code>
                </div>
                <div class="demo-card-clickable" onclick="quickFill('faculty', 'faculty123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">FACULTY MEMBER</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">faculty / faculty123</code>
                </div>
                <div class="demo-card-clickable" onclick="quickFill('SU2026BCA001', 'student123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">STUDENT PORTAL</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">SU2026BCA001 / student123</code>
                </div>
                <div class="demo-card-clickable" onclick="quickFill('exam_hod', 'exam123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">EXAM CONTROLLER</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">exam_hod / exam123</code>
                </div>
                <div class="demo-card-clickable" onclick="quickFill('exam_staff', 'exam123')" style="background: #f8fafc; border: 1px solid #edf2f7; padding: 0.35rem 0.5rem; border-radius: 0.375rem; display: flex; flex-direction: column; justify-content: center;">
                    <span style="font-weight: 700; color: #475569; font-size: 0.6875rem;">EXAM STAFF</span>
                    <code style="color: var(--color-scsa-accent-deep); font-weight: 700; margin-top: 0.1rem; font-size: 0.75rem;">exam_staff / exam123</code>
                </div>
            </div>
        </div>
        @endif
        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.75rem; --anim-delay: 0.6s;" class="muted animate-entrance">
            &copy; 2026 MarkMet. All rights reserved. Developed by Krutik Sojitra.
        </div>
    </div>
</div>

<script>
    function quickFill(username, password) {
        const userField = document.getElementById('username');
        const passField = document.getElementById('password');
        
        if (!userField || !passField) return;

        // Reset classes for highlight animation
        userField.classList.remove('pulsing-highlight');
        passField.classList.remove('pulsing-highlight');
        
        // Trigger reflow to restart CSS animation
        void userField.offsetWidth;
        void passField.offsetWidth;

        // Set field values
        userField.value = username;
        passField.value = password;

        // Add active highlighting pulse
        userField.classList.add('pulsing-highlight');
        passField.classList.add('pulsing-highlight');

        // Focus password field with a slight delay
        setTimeout(() => {
            passField.focus();
        }, 100);
    }
</script>
@endsection

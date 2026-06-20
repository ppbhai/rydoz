<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'RYDOZ' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --app-bg: #f4f6f8;
            --panel-bg: #ffffff;
            --surface-bg: #f8fafb;
            --brand: #FF8000;
            --brand-dark: #FF8000;
            --accent: #f0df36;
            --text: #1f2933;
            --muted: #697586;
            --line: #dde4eb;
            --success-bg: #e8f7ee;
            --danger-bg: #feeaeb;
            --radius: 12px;
            --radius-sm: 10px;
            --shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--app-bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app-shell {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            background: transparent;
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(12px);
            background: rgba(244, 246, 248, 0.95);
            border-bottom: 1px solid rgba(219, 227, 236, 0.9);
        }

        .app-header-inner {
            display: grid;
            grid-template-columns: 56px 1fr 56px;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            min-height: 64px;
        }

        .header-action {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--text);
            font-size: 0.95rem;
        }

        .header-action.right {
            margin-left: auto;
        }

        .header-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.1;
            text-align: center;
        }

        .header-kicker {
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .header-title {
            margin: 2px 0 0;
            font-size: 1.02rem;
            font-weight: 700;
        }

        .page-body {
            padding: 24px 20px 32px;
        }

        .hero-card,
        .panel,
        .metric-card,
        .search-panel {
            background: #fff;
            border: 1px solid rgba(219, 227, 236, 0.9);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .hero-card {
            padding: 18px;
            margin-bottom: 14px;
        }

        .hero-title {
            margin: 0 0 6px;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .hero-copy {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .metric-card {
            padding: 14px;
            min-height: 118px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(27, 132, 198, 0.08);
            color: var(--brand);
            font-size: 1.05rem;
        }

        .metric-label {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .metric-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
        }

        .metric-note {
            color: var(--brand);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .panel {
            padding: 18px;
            margin-bottom: 16px;
        }

        .panel-title {
            margin: 0 0 4px;
            font-size: 1rem;
            font-weight: 700;
        }

        .panel-subtitle {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .search-panel {
            padding: 14px;
            margin-bottom: 16px;
        }

        .stack-sm > * + * {
            margin-top: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .info-item {
            padding: 10px 12px;
            background: var(--surface-bg);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
        }

        .info-label {
            display: block;
            margin-bottom: 4px;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .info-value {
            font-size: 0.92rem;
            font-weight: 600;
            word-break: break-word;
        }

        .soft-pill,
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .soft-pill {
            background: rgba(27, 132, 198, 0.08);
            color: var(--brand);
        }

        .status-pill {
            background: rgba(23, 33, 43, 0.06);
            color: var(--text);
        }

        .table-card {
            margin: 0 -18px;
            overflow-x: auto;
            padding: 0 18px;
        }

        table.table {
            width: 100%;
            min-width: 620px;
            margin: 0;
            color: var(--text);
        }

        .table > :not(caption) > * > * {
            padding: 0.85rem 0.5rem;
            border-color: var(--line);
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table thead th {
            color: var(--muted);
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f8fafb;
        }

        .list-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .booking-card {
            padding: 14px;
            background: var(--surface-bg);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
        }

        .accordion-card {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: #fff;
        }

        .accordion-toggle {
            width: 100%;
            border: 0;
            background: var(--brand);
            color: #fff;
            padding: 14px;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .accordion-toggle .text-muted,
        .accordion-toggle .booking-meta,
        .accordion-toggle .booking-summary {
            color: rgba(255, 255, 255, 0.92) !important;
        }

        .accordion-body {
            display: none;
            padding: 0 14px 14px;
            border-top: 1px solid var(--line);
        }

        .accordion-card.is-open .accordion-body {
            display: block;
        }

        .soft-accent-card {
            border-color: rgba(254, 81, 0, 0.22);
            box-shadow: 0 10px 22px rgba(254, 81, 0, 0.08), 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        .soft-accent-card .accordion-toggle {
            background: #fff;
            color: var(--text);
            border-bottom: 1px solid rgba(254, 81, 0, 0.14);
        }

        .soft-accent-card .accordion-toggle .text-muted,
        .soft-accent-card .accordion-toggle .booking-meta,
        .soft-accent-card .accordion-toggle .booking-summary {
            color: var(--muted) !important;
        }

        .soft-accent-card .booking-summary {
            color: var(--brand) !important;
        }

        .soft-accent-card .header-status-pill {
            background: rgba(254, 81, 0, 0.12);
            color: var(--brand);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(221, 228, 235, 0.7);
            font-size: 0.9rem;
        }

        .detail-row:last-child {
            border-bottom: 0;
        }

        .qty-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .qty-chip {
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            font-weight: 700;
        }

        .qty-chip.active {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .vehicle-option {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 14px;
            background: #fff;
        }

        .vehicle-option.is-selected {
            border-color: var(--brand);
            background: rgba(254, 81, 0, 0.06);
        }

        .summary-card {
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface-bg);
        }

        .search-input-wrap {
            position: relative;
        }

        .search-input-wrap .form-control {
            padding-left: 42px;
        }

        .search-input-icon {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.9rem;
        }

        .booking-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .booking-summary {
            color: var(--brand);
            font-size: 0.82rem;
            font-weight: 700;
            text-align: right;
            line-height: 1.35;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .summary-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .summary-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .header-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .compact-stack > * + * {
            margin-top: 8px;
        }

        .assign-row {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            background: var(--surface-bg);
        }

        .assign-row-top {
            display: grid;
            grid-template-columns: minmax(120px, 160px) minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .assign-name {
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .assign-input {
            min-height: 40px;
        }

        .assign-times {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .assign-times strong {
            color: var(--text);
            font-weight: 700;
        }

        .action-timer-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }

        .timer-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(17, 24, 39, 0.06);
            color: #111827;
        }

        .mini-stat {
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
        }

        .section-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .empty-state {
            padding: 28px 16px;
            text-align: center;
            color: var(--muted);
            background: var(--surface-bg);
            border: 1px dashed var(--line);
            border-radius: var(--radius-sm);
        }

        .form-label {
            margin-bottom: 6px;
            color: var(--text);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            min-height: 46px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            box-shadow: none;
            font-size: 17px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(254, 81, 0, 0.45);
            box-shadow: 0 0 0 3px rgba(254, 81, 0, 0.12);
        }

        .input-group-text {
            border: 1px solid var(--line);
            border-radius: 10px 0 0 10px;
            background: #fff;
            color: var(--brand);
        }

        .input-group .form-control {
            border-left: 0;
            border-radius: 0 10px 10px 0;
        }

        .btn {
            min-height: 38px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 17px;
            line-height: 1.2;
            padding: 7px 10px;
            box-shadow: none;
        }

        .btn-theme {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-theme:hover,
        .btn-theme:focus {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #fff;
        }

        .btn-light-theme {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--text);
        }

        .btn-light-theme:hover {
            background: #f8fafc;
            border-color: #c7d2de;
            color: var(--text);
        }

        .btn-outline-primary {
            border-radius: 10px;
        }

        .alert {
            border: 0;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .hero-card,
        .panel,
        .metric-card,
        .search-panel,
        .booking-card,
        .info-item,
        .form-control,
        .form-select,
        .btn,
        .header-action {
            transition: all 0.18s ease;
        }

        .panel:hover,
        .metric-card:hover {
            border-color: #d4dde6;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        }

        .table tbody tr:hover {
            background: #fafcfd;
        }

        .alert-success {
            background: var(--success-bg);
            color: #155d34;
        }

        .alert-danger {
            background: var(--danger-bg);
            color: #8a1c24;
        }

        .app-toast-stack {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(92vw, 360px);
            pointer-events: none;
        }

        .app-toast {
            padding: 12px 14px;
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #fff;
            color: var(--text);
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.4;
            transform: translateY(-6px);
            opacity: 0;
            transition: opacity 0.25s ease, transform 0.25s ease;
            pointer-events: auto;
        }

        .app-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .app-toast.is-success {
            background: var(--success-bg);
            color: #155d34;
        }

        .app-toast.is-error {
            background: var(--danger-bg);
            color: #8a1c24;
        }

        .time-indicator {
            font-weight: 700;
        }

        .time-ontrack {
            color: #2bff00;
        }

        .time-warning {
            color: #feda2c;
        }

        .time-overdue {
            color: #ff0000;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(27, 132, 198, 0.08);
            color: var(--brand);
            font-size: 1.2rem;
            box-shadow: none;
        }

        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border: 1px solid rgba(219, 227, 236, 0.9);
            border-radius: 14px;
            padding: 22px 18px;
            box-shadow: var(--shadow);
        }

        .scanner-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .scanner-field .form-control {
            min-width: 0;
        }

        .scanner-btn {
            width: 46px;
            min-width: 46px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .iot-status {
            color: var(--muted);
            border: 1px dashed rgba(15, 23, 42, 0.2);
            border-radius: 8px;
            padding: 6px 8px;
            background: #f8fafc;
        }

        .iot-status[data-state="connected"] {
            color: #15803d;
        }

        .iot-status[data-state="pending"] {
            color: #b45309;
        }

        .iot-status[data-state="error"] {
            color: #b91c1c;
        }

        .nearby-scooters-heading,
        .nearby-scooter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .nearby-scan-retry {
            width: 38px;
            min-width: 38px;
            height: 38px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nearby-scooter-list {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .nearby-scooter-row {
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 8px;
            background: #fff;
        }

        .nearby-scooter-empty {
            margin-top: 10px;
            color: var(--muted);
            font-size: 0.875rem;
        }

        .nearby-scooter-battery {
            min-width: 52px;
            text-align: right;
            font-weight: 700;
            color: #15803d;
        }

        .nearby-scooter-battery[data-level="medium"] {
            color: #b45309;
        }

        .nearby-scooter-battery[data-level="low"] {
            color: #b91c1c;
        }

        .scanner-modal {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.72);
        }

        .scanner-modal.is-open {
            display: flex;
        }

        .scanner-panel {
            width: min(100%, 420px);
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
        }

        .scanner-video-wrap {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            background: #0f172a;
            aspect-ratio: 4 / 3;
        }

        .scanner-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .scanner-frame {
            position: absolute;
            inset: 14% 12%;
            border: 2px solid rgba(255, 255, 255, 0.92);
            border-radius: 16px;
            box-shadow: 0 0 0 999px rgba(15, 23, 42, 0.18);
        }

        .auth-logo {
            width: 150px;
            max-width: 100%;
        }

        @media (max-width: 991.98px) {
            .metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .app-header-inner {
                padding: 12px 14px;
            }

            .page-body {
                padding: 16px 14px 24px;
            }

            .panel,
            .hero-card {
                padding: 16px;
            }

            .search-panel {
                padding: 12px;
            }

            .table-card {
                margin: 0 -16px;
                padding: 0 16px;
            }

            .assign-row-top {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .metric-grid,
            .info-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 1.15rem;
            }

            .metric-value {
                font-size: 1.2rem;
            }
        }

        @media (min-width: 1200px) {
            .app-shell {
                max-width: 1240px;
            }
        }
    </style>
</head>
<script src="{{ URL::asset('assets/js/scooter-iot-bridge.js') }}?v=20260620-2"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navigationEntry = performance.getEntriesByType('navigation')[0];
        const isHistoryTraversal = navigationEntry?.type === 'back_forward';
        const toastStack = document.createElement('div');
        toastStack.className = 'app-toast-stack';
        document.body.appendChild(toastStack);

        window.showAppToast = (message, type = 'success', delay = 0) => {
            const text = (message || '').trim();

            if (text === '') {
                return;
            }

            setTimeout(() => {
                const toast = document.createElement('div');
                toast.className = `app-toast is-${type === 'success' ? 'success' : 'error'}`;
                toast.textContent = text;
                toastStack.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.add('is-visible');
                });

                window.setTimeout(() => {
                    toast.classList.remove('is-visible');
                    window.setTimeout(() => toast.remove(), 260);
                }, 3000);
            }, delay);
        };

        const alerts = Array.from(document.querySelectorAll('.alert'))
            .filter((alert) => !alert.classList.contains('d-none') && !alert.id);

        alerts.forEach((alert, index) => {
            const text = (alert.textContent || '').trim();
            const type = alert.classList.contains('alert-success') ? 'success' : 'error';

            alert.remove();

            if (isHistoryTraversal) {
                return;
            }

            window.showAppToast(text, type, index * 80);
        });

        window.addEventListener('pageshow', (event) => {
            const backForwardRestore = event.persisted || performance.getEntriesByType('navigation')[0]?.type === 'back_forward';

            if (!backForwardRestore) {
                return;
            }

            document.querySelectorAll('.alert').forEach((alert) => alert.remove());
            document.querySelectorAll('.app-toast').forEach((toast) => toast.remove());
        });

        if (document.querySelector('[data-shared-scan]') && typeof window.openScanner !== 'function') {
            const scannerModal = document.createElement('div');
            scannerModal.className = 'scanner-modal';
            scannerModal.id = 'scannerModal';
            scannerModal.setAttribute('aria-hidden', 'true');
            scannerModal.innerHTML = `
                <div class="scanner-panel">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="panel-title mb-1">Scan Barcode</h2>
                            <div class="panel-subtitle mb-0">Point the camera at the barcode to fill the field.</div>
                        </div>
                        <button type="button" class="btn btn-light-theme scanner-btn" id="closeScannerBtn" aria-label="Close scanner">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="scanner-video-wrap">
                        <video id="scannerVideo" class="scanner-video" autoplay muted playsinline></video>
                        <div class="scanner-frame"></div>
                    </div>
                    <div class="stack-sm mt-3">
                        <div class="alert alert-danger d-none mb-0 py-2" id="scannerError"></div>
                        <button type="button" class="btn btn-light-theme w-100" id="cancelScannerBtn">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(scannerModal);

            const scannerVideo = document.getElementById('scannerVideo');
            const scannerError = document.getElementById('scannerError');
            const closeScannerBtn = document.getElementById('closeScannerBtn');
            const cancelScannerBtn = document.getElementById('cancelScannerBtn');
            let scannerStream = null;
            let scannerTargetInput = null;
            let scannerFrameId = null;
            let barcodeDetector = null;

            function showScannerError(message) {
                scannerError.textContent = message;
                scannerError.classList.remove('d-none');
            }

            function clearScannerError() {
                scannerError.textContent = '';
                scannerError.classList.add('d-none');
            }

            function stopScanner() {
                if (scannerFrameId) {
                    cancelAnimationFrame(scannerFrameId);
                    scannerFrameId = null;
                }

                if (scannerStream) {
                    scannerStream.getTracks().forEach((track) => track.stop());
                    scannerStream = null;
                }

                scannerVideo.srcObject = null;
                scannerModal.classList.remove('is-open');
                scannerModal.setAttribute('aria-hidden', 'true');
                scannerTargetInput = null;
                clearScannerError();
            }

            async function scanFrame() {
                if (!barcodeDetector || !scannerVideo || scannerVideo.readyState < 2) {
                    scannerFrameId = requestAnimationFrame(scanFrame);
                    return;
                }

                try {
                    const barcodes = await barcodeDetector.detect(scannerVideo);

                    if (barcodes.length > 0 && scannerTargetInput) {
                        const rawValue = (barcodes[0].rawValue || '').trim();

                        if (rawValue !== '') {
                            scannerTargetInput.value = window.ScooterIot?.normalizeScooterId
                                ? window.ScooterIot.normalizeScooterId(rawValue)
                                : rawValue;
                            scannerTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
                            window.dispatchEvent(new CustomEvent('scooter:qr-scanned', {
                                detail: {
                                    value: rawValue,
                                    input: scannerTargetInput
                                }
                            }));
                            stopScanner();
                            return;
                        }
                    }
                } catch (error) {
                    showScannerError('Unable to read barcode from camera.');
                }

                scannerFrameId = requestAnimationFrame(scanFrame);
            }

            window.openScanner = async function openScanner(targetInputId) {
                clearScannerError();

                if (!('BarcodeDetector' in window)) {
                    showScannerError('Barcode scanning is not supported on this device/browser.');
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                    return;
                }

                scannerTargetInput = document.getElementById(targetInputId);

                if (!scannerTargetInput) {
                    return;
                }

                try {
                    barcodeDetector = new BarcodeDetector({
                        formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code']
                    });

                    scannerStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            }
                        },
                        audio: false
                    });

                    scannerVideo.srcObject = scannerStream;
                    await scannerVideo.play();
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                    scannerFrameId = requestAnimationFrame(scanFrame);
                } catch (error) {
                    showScannerError('Camera access failed. Please allow camera permission and try again.');
                    scannerModal.classList.add('is-open');
                    scannerModal.setAttribute('aria-hidden', 'false');
                }
            }

            document.querySelectorAll('[data-shared-scan]').forEach((button) => {
                button.addEventListener('click', () => {
                    window.openScanner(button.dataset.targetInput);
                });
            });

            closeScannerBtn.addEventListener('click', stopScanner);
            cancelScannerBtn.addEventListener('click', stopScanner);
            scannerModal.addEventListener('click', (event) => {
                if (event.target === scannerModal) {
                    stopScanner();
                }
            });
        }
    });
</script>

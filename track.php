<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Shipment | Modern Logistics</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <!-- Bootstrap CSS for layout only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg-dark: #0f172a;
            --card-dark: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
        }

        /* Glassmorphism Classes */
        .glass-card {
            background: var(--card-dark);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .hero-section {
            padding: 80px 0 40px;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .track-input {
            width: 100%;
            padding: 20px 30px;
            padding-right: 120px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            outline: none !important;
        }

        .track-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .track-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            bottom: 10px;
            padding: 0 30px;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .track-btn:hover {
            transform: scale(1.05);
            background: #4f46e5;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding: 20px 0;
            list-style: none;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 31px;
            top: 30px;
            bottom: 30px;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), transparent);
        }

        .timeline-item {
            position: relative;
            padding-left: 70px;
            margin-bottom: 40px;
            animation: slideIn 0.5s ease forwards;
            opacity: 0;
        }

        @keyframes slideIn {
            from {
                transform: translateX(20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 24px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: var(--bg-dark);
            border: 3px solid var(--primary);
            border-radius: 50%;
            z-index: 2;
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .timeline-item.active::before {
            background: var(--primary);
            transform: scale(1.3);
        }

        .timeline-status {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: 4px;
        }

        .timeline-location {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timeline-time {
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
            margin-top: 5px;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 100px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        #resultArea {
            display: none;
            margin-top: 60px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .loader {
            display: none;
            margin: 40px auto;
            width: 48px;
            height: 48px;
            border: 5px solid #FFF;
            border-bottom-color: var(--primary);
            border-radius: 50%;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">Track Your Life's <br>Shipments</h1>
            <p class="text-muted mb-5">Real-time intelligence for every package anywhere in the world.</p>

            <div class="search-container">
                <input type="text" id="waybillInput" class="track-input" placeholder="Enter Waybill Number..."
                    autocomplete="off">
                <button id="trackBtn" class="track-btn">Track Now</button>
            </div>
        </section>

        <!-- Loader -->
        <div class="loader" id="loader"></div>

        <!-- Result Area -->
        <section id="resultArea">
            <div class="row g-4">
                <!-- Overview Card -->
                <div class="col-lg-4">
                    <div class="glass-card p-4 h-100 text-center">
                        <div class="mb-4">
                            <span class="status-badge" id="currentStatus">In Transit</span>
                        </div>
                        <h3 class="mb-2" id="waybillDisplay"></h3>
                        <p class="text-muted small" id="shippingMode"></p>

                        <hr class="my-4 opacity-10">

                        <div class="mb-4">
                            <p class="text-muted mb-1 fs-xs text-uppercase fw-bold">From</p>
                            <h5 id="originDisplay"></h5>
                        </div>

                        <div class="mb-0">
                            <p class="text-muted mb-1 fs-xs text-uppercase fw-bold">To</p>
                            <h5 id="destinationDisplay"></h5>
                        </div>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="col-lg-8">
                    <div class="glass-card p-4 p-md-5">
                        <h4 class="mb-5"><i class="ti ti-history me-2 text-primary"></i>Tracking History</h4>

                        <ul class="timeline" id="trackingTimeline">
                            <!-- Items dynamically added -->
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- No Result Area -->
        <div id="noMatch" class="text-center mt-5" style="display: none;">
            <div class="glass-card d-inline-block p-4">
                <i class="ti ti-package-off fs-1 text-muted mb-3 d-block"></i>
                <h5>Shipment Not Found</h5>
                <p class="text-muted small mb-0">Please check your waybill number and try again.</p>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {

            function performTrack() {
                const waybill = $('#waybillInput').val().trim();
                if (!waybill) return;

                $('#resultArea, #noMatch').fadeOut(200);
                $('#loader').fadeIn(200);

                $.get('api/public/track_shipment.php', { waybill: waybill }, function (res) {
                    $('#loader').fadeOut(200);

                    if (res.status === 'success') {
                        const b = res.data.booking;
                        const h = res.data.history;

                        $('#waybillDisplay').text(b.waybill_no);
                        $('#currentStatus').text(b.last_status);
                        $('#shippingMode').text(b.shipping_mode + ' Shipping');
                        $('#originDisplay').text(b.pickup_city);
                        $('#destinationDisplay').text(b.consignee_city);

                        let timelineHtml = '';
                        if (h && h.length > 0) {
                            h.forEach((item, index) => {
                                const activeClass = index === 0 ? 'active' : '';
                                timelineHtml += `
                                    <li class="timeline-item ${activeClass}" style="animation-delay: ${index * 0.1}s">
                                        <div class="timeline-status">${item.status}</div>
                                        <div class="timeline-location">
                                            <i class="ti ti-map-pin"></i> ${item.location || 'Location Pending'}
                                        </div>
                                        <div class="timeline-time">${formatDate(item.time)}</div>
                                        ${item.remarks ? `<p class="small text-muted mt-2">${item.remarks}</p>` : ''}
                                    </li>
                                `;
                            });
                        } else {
                            timelineHtml = `
                                <li class="timeline-item active">
                                    <div class="timeline-status">Booked</div>
                                    <div class="timeline-location"><i class="ti ti-building"></i> Origin Gateway</div>
                                    <div class="timeline-time">${formatDate(b.created_at)}</div>
                                    <p class="small text-muted mt-2">Shipment details captured at origin.</p>
                                </li>
                            `;
                        }

                        $('#trackingTimeline').html(timelineHtml);
                        $('#resultArea').fadeIn(500);
                    } else {
                        $('#noMatch').fadeIn(500);
                    }
                });
            }

            $('#trackBtn').click(performTrack);

            $('#waybillInput').keypress(function (e) {
                if (e.which == 13) performTrack();
            });

            function formatDate(dateStr) {
                const options = {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true
                };
                return new Date(dateStr).toLocaleString('en-US', options);
            }

            // Auto-track if waybill in URL
            const urlParams = new URLSearchParams(window.location.search);
            const wb = urlParams.get('waybill');
            if (wb) {
                $('#waybillInput').val(wb);
                performTrack();
            }
        });
    </script>
</body>

</html>
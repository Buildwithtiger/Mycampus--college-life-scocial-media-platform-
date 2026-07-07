<?php
require_once 'config.php';
if (!isLoggedIn()) redirect('login.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Scanner - MyCampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- html5-qrcode library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            background-color: #fafafa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding-bottom: 70px;
        }
        .navbar {
            background-color: white;
            border-bottom: 1px solid #dbdbdb;
        }
        .scanner-container {
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        #reader {
            width: 100%;
            background: #000;
        }
        .result-box {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #dbdbdb;
            word-break: break-all;
        }
        .btn-scan-again {
            background-color: #0095f6;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
        }
        .camera-select {
            margin-bottom: 15px;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: white;
            border-top: 1px solid #dbdbdb;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 100;
        }
        .bottom-nav a {
            color: #262626;
            font-size: 24px;
        }
        .bottom-nav a.active {
            color: #0095f6;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
            <span class="navbar-text">Scan QR Code</span>
            <div></div> <!-- placeholder for alignment -->
        </div>
    </nav>

    <div class="container mt-3">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Point your camera at a QR code. The result will be processed automatically.
        </div>

        <!-- Optional: camera selection dropdown (will be populated by JS) -->
        <div class="camera-select d-flex justify-content-between align-items-center">
            <span><i class="fas fa-camera"></i> Camera:</span>
            <select id="cameraSelect" class="form-select w-75"></select>
        </div>

        <!-- QR reader container -->
        <div class="scanner-container">
            <div id="reader" style="width:100%;"></div>
        </div>

        <!-- Scan result / action box -->
        <div id="resultContainer" class="result-box" style="display: none;">
            <h6><i class="fas fa-qrcode"></i> Scanned data:</h6>
            <p id="scannedData" class="mb-2"></p>
            <div id="actionButtons"></div>
            <button id="scanAgainBtn" class="btn-scan-again mt-2 w-100">Scan Another QR</button>
        </div>
    </div>

    <!-- Bottom Navigation (highlight scanner) -->
    <div class="bottom-nav">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="fas fa-plus-square"></i></a>
        <a href="scanner.php" class="active"><i class="fas fa-qrcode"></i></a>
        <a href="profile.php"><i class="far fa-user"></i></a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let html5QrCode;
        let currentCameraId = null;
        let isScanning = false;
        let cameras = [];

        async function initScanner(cameraId = null) {
            if (html5QrCode && isScanning) {
                await html5QrCode.stop();
                isScanning = false;
            }

            const readerElement = document.getElementById('reader');
            // Clear any previous instance
            if (html5QrCode) {
                try { html5QrCode.clear(); } catch(e) {}
            }

            html5QrCode = new Html5Qrcode("reader");

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true
            };

            try {
                if (cameraId) {
                    await html5QrCode.start(cameraId, config, onScanSuccess, onScanFailure);
                } else {
                    // Default to back camera if available
                    const devices = await Html5Qrcode.getCameras();
                    if (devices && devices.length) {
                        cameras = devices;
                        populateCameraSelect(devices);
                        const backCamera = devices.find(device => device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('environment'));
                        const selectedId = backCamera ? backCamera.id : devices[0].id;
                        currentCameraId = selectedId;
                        await html5QrCode.start(selectedId, config, onScanSuccess, onScanFailure);
                    } else {
                        showError("No camera found on this device.");
                        return;
                    }
                }
                isScanning = true;
                document.getElementById('resultContainer').style.display = 'none';
            } catch (err) {
                console.error(err);
                showError("Unable to start camera: " + err);
            }
        }

        function populateCameraSelect(devices) {
            const select = document.getElementById('cameraSelect');
            select.innerHTML = '';
            devices.forEach((device, idx) => {
                const option = document.createElement('option');
                option.value = device.id;
                option.text = device.label || `Camera ${idx+1}`;
                select.appendChild(option);
            });
            select.value = currentCameraId;
            select.onchange = async function() {
                if (isScanning) {
                    await html5QrCode.stop();
                    isScanning = false;
                }
                await initScanner(select.value);
            };
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;
            // Stop scanner after successful scan
            html5QrCode.stop();
            isScanning = false;
            
            // Display the scanned data
            document.getElementById('scannedData').innerText = decodedText;
            document.getElementById('resultContainer').style.display = 'block';
            
            // Send to server for processing
            $.ajax({
                url: 'scan_handler.php',
                type: 'POST',
                data: { scanned_data: decodedText },
                dataType: 'json',
                success: function(response) {
                    let actionHtml = '';
                    if (response.status === 'success') {
                        actionHtml = `<div class="alert alert-success mt-2">${response.message}</div>`;
                        if (response.redirect) {
                            setTimeout(() => { window.location.href = response.redirect; }, 1500);
                        } else if (response.action_html) {
                            actionHtml += response.action_html;
                        }
                    } else {
                        actionHtml = `<div class="alert alert-danger mt-2">${response.message}</div>`;
                    }
                    $('#actionButtons').html(actionHtml);
                },
                error: function() {
                    $('#actionButtons').html('<div class="alert alert-warning mt-2">Could not process scan. The data was: ' + decodedText + '</div>');
                }
            });
        }

        function onScanFailure(error) {
            // silent failure, scanning continues
        }

        function showError(msg) {
            const container = document.querySelector('.scanner-container');
            container.innerHTML = `<div class="alert alert-danger m-3">${msg}</div>`;
        }

        document.getElementById('scanAgainBtn').addEventListener('click', function() {
            document.getElementById('resultContainer').style.display = 'none';
            $('#actionButtons').empty();
            initScanner(currentCameraId);
        });

        // Initialize scanner when page loads
        window.addEventListener('load', () => {
            initScanner();
        });

        // Stop scanner when page is hidden (optional, good for battery)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && html5QrCode && isScanning) {
                html5QrCode.stop().catch(console.warn);
                isScanning = false;
            } else if (!document.hidden && !isScanning && !document.getElementById('resultContainer').style.display === 'block') {
                // Auto restart only if no result is showing
                if (document.getElementById('resultContainer').style.display !== 'block') {
                    initScanner(currentCameraId);
                }
            }
        });
    </script>
</body>
</html>
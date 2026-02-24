/**
 * QR Code Scanner for Visitor Checkout
 * Uses html5-qrcode library
 */

class QRScanner {
    constructor(containerId, onScanSuccess, onScanFailure) {
        this.containerId = containerId;
        this.onScanSuccess = onScanSuccess;
        this.onScanFailure = onScanFailure;
        this.scanner = null;
        this.isScanning = false;
    }

    /**
     * Initialize the QR scanner
     */
    async init() {
        try {
            // Check if html5-qrcode is available
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error('Html5Qrcode library not loaded');
            }

            this.scanner = new Html5Qrcode(this.containerId);

            // Get available cameras
            const cameras = await Html5Qrcode.getCameras();

            if (cameras && cameras.length > 0) {
                // Prefer back camera on mobile, otherwise use first camera
                const preferredCamera = cameras.find(cam =>
                    cam.label.toLowerCase().includes('back') ||
                    cam.label.toLowerCase().includes('rear')
                ) || cameras[0];

                return {
                    success: true,
                    cameras: cameras,
                    selectedCamera: preferredCamera
                };
            } else {
                throw new Error('No cameras found');
            }
        } catch (error) {
            console.error('QR Scanner init error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Start scanning
     */
    async start(cameraId = null) {
        if (!this.scanner) {
            await this.init();
        }

        try {
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            // Use provided camera or find one
            let targetCamera = cameraId;
            if (!targetCamera) {
                const cameras = await Html5Qrcode.getCameras();
                const preferredCamera = cameras.find(cam =>
                    cam.label.toLowerCase().includes('back') ||
                    cam.label.toLowerCase().includes('rear')
                ) || cameras[0];
                targetCamera = preferredCamera.id;
            }

            await this.scanner.start(
                targetCamera,
                config,
                (decodedText, decodedResult) => {
                    this.isScanning = true;
                    if (this.onScanSuccess) {
                        this.onScanSuccess(decodedText, decodedResult);
                    }
                },
                (errorMessage) => {
                    // QR not found in this frame - ignore
                    if (this.onScanFailure) {
                        this.onScanFailure(errorMessage);
                    }
                }
            );

            return { success: true };
        } catch (error) {
            console.error('QR Scanner start error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Stop scanning
     */
    async stop() {
        if (this.scanner && this.isScanning) {
            try {
                await this.scanner.stop();
                this.isScanning = false;
                return { success: true };
            } catch (error) {
                console.error('QR Scanner stop error:', error);
                return { success: false, error: error.message };
            }
        }
        return { success: true };
    }

    /**
     * Check if camera permission is granted
     */
    static async checkPermission() {
        try {
            const result = await navigator.permissions.query({ name: 'camera' });
            return result.state;
        } catch (error) {
            // Some browsers don't support camera permission query
            return 'prompt';
        }
    }

    /**
     * Request camera permission
     */
    static async requestPermission() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());
            return { success: true };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
}

/**
 * Extract token from QR code URL
 */
function extractTokenFromQR(qrData) {
    try {
        // If it's a URL, extract the token parameter
        if (qrData.includes('?')) {
            const url = new URL(qrData);
            return url.searchParams.get('token');
        }
        // Otherwise assume it's just the token
        return qrData.trim();
    } catch (error) {
        console.error('Error extracting token:', error);
        return qrData.trim();
    }
}

/**
 * Verify QR token with server
 */
async function verifyQRToken(token) {
    try {
        const response = await fetch(`api/verify-qr.php?token=${encodeURIComponent(token)}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error verifying token:', error);
        return { success: false, message: 'Network error' };
    }
}

/**
 * Process checkout with token
 */
async function processCheckout(token, method = 'qr_rescan') {
    try {
        const response = await fetch('api/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                qr_token: token,
                method: method
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error processing checkout:', error);
        return { success: false, message: 'Network error' };
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { QRScanner, extractTokenFromQR, verifyQRToken, processCheckout };
}

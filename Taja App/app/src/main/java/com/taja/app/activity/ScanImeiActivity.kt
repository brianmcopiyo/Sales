package com.taja.app.activity

import android.app.Activity
import android.Manifest
import android.content.pm.PackageManager
import android.os.Bundle
import android.util.Log
import android.widget.Button
import android.widget.TextView
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import com.taja.app.R
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleOwner
import androidx.lifecycle.LifecycleRegistry
import java.util.concurrent.Executors

class ScanImeiActivity : Activity(), LifecycleOwner {

    private val lifecycleRegistry = LifecycleRegistry(this)

    override val lifecycle: Lifecycle get() = lifecycleRegistry

    private lateinit var previewView: PreviewView
    private lateinit var statusView: TextView
    private lateinit var doneButton: Button

    private val imeis = linkedSetOf<String>()
    private val CAMERA_REQUEST_CODE = 2001
    private val REVIEW_REQUEST_CODE = 2002
    private val MIN_IMEIS_FOR_TAC_FILTER = 15

    /** Throttle: ignore same raw value for this many ms to avoid duplicate scans. */
    private var lastScannedRaw: String? = null
    private var lastScannedTime: Long = 0
    private val scanCooldownMs = 1500L

    private val barcodeScanner by lazy {
        val options = BarcodeScannerOptions.Builder()
            .setBarcodeFormats(Barcode.FORMAT_PDF417)
            .build()
        BarcodeScanning.getClient(options)
    }

    private val cameraExecutor = Executors.newSingleThreadExecutor()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_scan_imei)

        previewView = findViewById(R.id.preview_view)
        statusView = findViewById(R.id.scan_imei_status)
        doneButton = findViewById(R.id.scan_imei_done)

        doneButton.setOnClickListener {
            val effectiveList = effectiveImeiList()
            if (effectiveList.isEmpty()) {
                finish()
            } else {
                val reviewIntent = android.content.Intent(this, ReviewScannedImeisActivity::class.java)
                reviewIntent.putExtra("imeis", effectiveList.joinToString("\n"))
                startActivityForResult(reviewIntent, REVIEW_REQUEST_CODE)
            }
        }

        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            startCamera()
        } else {
            androidx.core.app.ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.CAMERA), CAMERA_REQUEST_CODE)
        }
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_CREATE)
    }

    override fun onStart() {
        super.onStart()
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_START)
    }

    override fun onResume() {
        super.onResume()
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_RESUME)
    }

    override fun onPause() {
        super.onPause()
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_PAUSE)
    }

    override fun onStop() {
        super.onStop()
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_STOP)
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == CAMERA_REQUEST_CODE) {
            if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                startCamera()
            } else {
                finish()
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        lifecycleRegistry.handleLifecycleEvent(Lifecycle.Event.ON_DESTROY)
        cameraExecutor.shutdown()
    }

    private fun startCamera() {
        val cameraProviderFuture = ProcessCameraProvider.getInstance(this)
        cameraProviderFuture.addListener({
            val cameraProvider = cameraProviderFuture.get()
            val preview = Preview.Builder().build().also {
                it.setSurfaceProvider(previewView.getSurfaceProvider())
            }
            val imageAnalysis = ImageAnalysis.Builder()
                .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                .build()
                .also {
                    it.setAnalyzer(cameraExecutor, Pdf417Analyzer { rawValue -> onBarcodeScanned(rawValue) })
                }
            val cameraSelector = CameraSelector.DEFAULT_BACK_CAMERA
            try {
                cameraProvider.unbindAll()
                cameraProvider.bindToLifecycle(
                    this,
                    cameraSelector,
                    preview,
                    imageAnalysis
                )
            } catch (e: Exception) {
                Log.e(TAG, "Camera bind failed", e)
            }
        }, ContextCompat.getMainExecutor(this))
    }

    private fun onBarcodeScanned(rawValue: String?) {
        if (rawValue.isNullOrBlank()) return
        // Throttle duplicate scans
        val now = System.currentTimeMillis()
        if (rawValue == lastScannedRaw && (now - lastScannedTime) < scanCooldownMs) return
        lastScannedRaw = rawValue
        lastScannedTime = now

        val newImeis = extractImeis(rawValue)
        if (newImeis.isEmpty()) return
        var added = false
        for (rawImei in newImeis) {
            val normalized = normalizeImei(rawImei)
            if (normalized.isNotEmpty() && imeis.add(normalized)) {
                added = true
            }
        }
        if (added) {
            runOnUiThread { updateScanStatus() }
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: android.content.Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == REVIEW_REQUEST_CODE) {
            if (resultCode == Activity.RESULT_OK) {
                val reviewed = data?.getStringExtra("reviewed_imeis") ?: ""
                val resultIntent = intent
                resultIntent.putExtra("scanned_imeis", reviewed)
                setResult(Activity.RESULT_OK, resultIntent)
            }
            finish()
        }
    }

    private fun extractImeis(raw: String): List<String> {
        val regex = Regex("\\b\\d{15}\\b")
        return regex.findAll(raw)
            .map { it.value }
            .filter { isValidImei(it) }
            .toList()
    }

    private fun normalizeImei(raw: String): String {
        val digits = raw.filter { it.isDigit() }
        return if (digits.length == 15 && isValidImei(digits)) digits else ""
    }

    private fun isValidImei(imei: String): Boolean {
        if (imei.length != 15 || !imei.all { it.isDigit() }) return false
        var sum = 0
        for (i in 0 until 14) {
            var n = imei[14 - i] - '0'
            if (i % 2 == 0) {
                n *= 2
                if (n > 9) n -= 9
            }
            sum += n
        }
        val checkDigit = (10 - (sum % 10)) % 10
        return checkDigit == (imei[14] - '0')
    }

    private fun effectiveImeiList(): List<String> {
        val list = imeis.toList()
        if (list.size < MIN_IMEIS_FOR_TAC_FILTER) return list
        return filterByMostCommonTac(list)
    }

    private fun updateScanStatus() {
        val list = imeis.toList()
        if (list.size < MIN_IMEIS_FOR_TAC_FILTER) {
            statusView.text = getString(R.string.scan_imei_status, list.size)
        } else {
            val filtered = filterByMostCommonTac(list)
            val removed = list.size - filtered.size
            statusView.text = if (removed > 0) {
                getString(R.string.scan_imei_status_filtered, filtered.size, removed)
            } else {
                getString(R.string.scan_imei_status, filtered.size)
            }
        }
    }

    private fun filterByMostCommonTac(imeis: List<String>): List<String> {
        if (imeis.size <= 1) return imeis
        val withTac = imeis.filter { it.length >= 8 }
        if (withTac.isEmpty()) return imeis
        val tacCounts = withTac.groupingBy { it.take(8) }.eachCount()
        val mostCommonTac = tacCounts.maxByOrNull { it.value }?.key ?: return imeis
        return withTac.filter { it.take(8) == mostCommonTac }
    }

    /** Analyzer that runs ML Kit PDF417 on each frame and forwards raw value to callback. */
    private inner class Pdf417Analyzer(
        private val onDetected: (String?) -> Unit
    ) : ImageAnalysis.Analyzer {

        @androidx.camera.core.ExperimentalGetImage
        override fun analyze(imageProxy: ImageProxy) {
            val mediaImage = imageProxy.image ?: run {
                imageProxy.close()
                return
            }
            val rotation = imageProxy.imageInfo.rotationDegrees
            val inputImage = InputImage.fromMediaImage(mediaImage, rotation)
            barcodeScanner.process(inputImage)
                .addOnSuccessListener { barcodes ->
                    val value = barcodes.firstOrNull()?.rawValue
                    if (!value.isNullOrBlank()) onDetected(value)
                }
                .addOnCompleteListener { imageProxy.close() }
        }
    }

    companion object {
        private const val TAG = "ScanImei"
    }
}

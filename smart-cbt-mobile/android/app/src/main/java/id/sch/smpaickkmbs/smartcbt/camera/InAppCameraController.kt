package id.sch.smpaickkmbs.smartcbt.camera

import android.Manifest
import android.annotation.SuppressLint
import android.app.Activity
import android.content.Context
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Matrix
import android.graphics.RectF
import android.graphics.SurfaceTexture
import android.hardware.camera2.CameraAccessException
import android.hardware.camera2.CameraCaptureSession
import android.hardware.camera2.CameraCharacteristics
import android.hardware.camera2.CameraDevice
import android.hardware.camera2.CameraManager
import android.hardware.camera2.CaptureRequest
import android.os.Build
import android.os.Handler
import android.os.HandlerThread
import android.os.Looper
import android.util.Base64
import android.util.Size
import android.view.Surface
import android.view.TextureView
import android.view.View
import android.widget.Button
import android.widget.ImageButton
import android.widget.ImageView
import android.widget.TextView
import androidx.core.content.ContextCompat
import java.io.ByteArrayOutputStream
import kotlin.math.abs
import kotlin.math.max
import kotlin.math.roundToInt

/**
 * Built-in Camera2 + TextureView overlay running directly inside MainActivity.
 * Never opens an external camera app so LockTask/Kiosk pinning stays intact
 * and photo capture works on both HTTPS and local HTTP testing servers.
 */
class InAppCameraController(
    private val activity: Activity,
    private val overlayRoot: View,
    private val textureView: TextureView,
    private val previewImage: ImageView,
    private val controlsLive: View,
    private val controlsPreview: View,
    private val btnCapture: ImageButton,
    private val btnRetake: Button,
    private val btnUsePhoto: Button,
    private val btnClose: Button,
    private val btnSwitch: Button,
    private val btnFlash: Button,
    private val tvStatus: TextView,
    private val onPhotoReady: (questionId: String, replaceAttachmentId: String, base64Jpeg: String, fileName: String) -> Unit,
    private val onCameraError: (questionId: String, message: String) -> Unit,
    private val onVisibilityChanged: (isOpen: Boolean) -> Unit
) {
    private val mainHandler = Handler(Looper.getMainLooper())
    private val cameraManager: CameraManager? = activity.getSystemService(Context.CAMERA_SERVICE) as? CameraManager

    private var backgroundThread: HandlerThread? = null
    private var backgroundHandler: Handler? = null
    private var cameraDevice: CameraDevice? = null
    private var captureSession: CameraCaptureSession? = null
    private var previewRequestBuilder: CaptureRequest.Builder? = null
    private var previewSize: Size = Size(1280, 960)

    private var currentLensFacing = CameraCharacteristics.LENS_FACING_BACK
    private var flashSupported = false
    private var flashEnabled = false
    private var isOpen = false

    private var activeQuestionId: String = ""
    private var activeReplaceId: String = ""
    private var capturedBitmap: Bitmap? = null

    init {
        btnClose.setOnClickListener { close() }
        btnCapture.setOnClickListener { captureCurrentFrame() }
        btnRetake.setOnClickListener { returnToLivePreview() }
        btnUsePhoto.setOnClickListener { confirmCapturedPhoto() }
        btnSwitch.setOnClickListener { switchLensFacing() }
        btnFlash.setOnClickListener { toggleFlash() }
        textureView.setOnClickListener { triggerAutoFocus() }

        textureView.surfaceTextureListener = object : TextureView.SurfaceTextureListener {
            override fun onSurfaceTextureAvailable(surface: SurfaceTexture, width: Int, height: Int) {
                if (isOpen && capturedBitmap == null) {
                    openSelectedCamera(width, height)
                }
            }

            override fun onSurfaceTextureSizeChanged(surface: SurfaceTexture, width: Int, height: Int) {
                configureTransform(width, height)
            }

            override fun onSurfaceTextureDestroyed(surface: SurfaceTexture): Boolean = true

            override fun onSurfaceTextureUpdated(surface: SurfaceTexture) = Unit
        }
    }

    fun isOverlayVisible(): Boolean = isOpen

    fun open(questionId: String, replaceAttachmentId: String) {
        activeQuestionId = questionId
        activeReplaceId = replaceAttachmentId
        flashEnabled = false
        clearCapturedBitmap()

        isOpen = true
        onVisibilityChanged(true)

        overlayRoot.visibility = View.VISIBLE
        previewImage.visibility = View.GONE
        textureView.visibility = View.VISIBLE
        controlsLive.visibility = View.VISIBLE
        controlsPreview.visibility = View.GONE
        tvStatus.text = "Arahkan kamera ke kertas jawaban, ketuk layar untuk fokus"
        updateFlashButtonUi()

        startBackgroundThread()
        if (textureView.isAvailable) {
            openSelectedCamera(textureView.width, textureView.height)
        }
    }

    fun onPause() {
        if (isOpen) {
            closeCameraHardware()
            stopBackgroundThread()
        }
    }

    fun onResume() {
        if (isOpen && capturedBitmap == null) {
            startBackgroundThread()
            if (textureView.isAvailable) {
                openSelectedCamera(textureView.width, textureView.height)
            }
        }
    }

    fun close() {
        isOpen = false
        clearCapturedBitmap()
        closeCameraHardware()
        stopBackgroundThread()
        overlayRoot.visibility = View.GONE
        onVisibilityChanged(false)
    }

    @SuppressLint("MissingPermission")
    private fun openSelectedCamera(viewWidth: Int, viewHeight: Int) {
        if (ContextCompat.checkSelfPermission(activity, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            close()
            onCameraError(activeQuestionId, "Izin kamera perangkat belum diaktifkan.")
            return
        }

        val manager = cameraManager ?: run {
            close()
            onCameraError(activeQuestionId, "Layanan kamera tidak tersedia di perangkat ini.")
            return
        }

        val cameraId = selectCameraId(currentLensFacing) ?: run {
            close()
            onCameraError(activeQuestionId, "Kamera tidak ditemukan pada perangkat ini.")
            return
        }

        closeCameraHardware()

        try {
            val characteristics = manager.getCameraCharacteristics(cameraId)
            flashSupported = characteristics.get(CameraCharacteristics.FLASH_INFO_AVAILABLE) == true
            mainHandler.post { updateFlashButtonUi() }

            val map = characteristics.get(CameraCharacteristics.SCALER_STREAM_CONFIGURATION_MAP)
            val choices = map?.getOutputSizes(SurfaceTexture::class.java) ?: arrayOf(Size(1280, 960))
            previewSize = chooseOptimalPreviewSize(choices)

            configureTransform(viewWidth, viewHeight)

            manager.openCamera(cameraId, object : CameraDevice.StateCallback() {
                override fun onOpened(camera: CameraDevice) {
                    if (!isOpen) {
                        camera.close()
                        return
                    }
                    cameraDevice = camera
                    createCameraPreviewSession()
                }

                override fun onDisconnected(camera: CameraDevice) {
                    camera.close()
                    cameraDevice = null
                }

                override fun onError(camera: CameraDevice, error: Int) {
                    camera.close()
                    cameraDevice = null
                    mainHandler.post {
                        if (isOpen) {
                            tvStatus.text = "Kamera sedang sibuk (kode $error). Ketuk Tutup lalu coba lagi."
                        }
                    }
                }
            }, backgroundHandler)
        } catch (e: CameraAccessException) {
            close()
            onCameraError(activeQuestionId, "Gagal mengakses kamera: ${e.localizedMessage ?: "error"}")
        } catch (e: SecurityException) {
            close()
            onCameraError(activeQuestionId, "Akses kamera ditolak sistem Android.")
        }
    }

    private fun selectCameraId(preferredFacing: Int): String? {
        val manager = cameraManager ?: return null
        return try {
            val ids = manager.cameraIdList
            ids.firstOrNull { id ->
                val facing = manager.getCameraCharacteristics(id).get(CameraCharacteristics.LENS_FACING)
                facing == preferredFacing
            } ?: ids.firstOrNull()
        } catch (_: Exception) {
            null
        }
    }

    private fun chooseOptimalPreviewSize(choices: Array<Size>): Size {
        val candidates = choices.filter { size ->
            max(size.width, size.height) <= MAX_EDGE_PX && size.width >= 640 && size.height >= 480
        }
        if (candidates.isEmpty()) {
            return choices.minByOrNull { abs(max(it.width, it.height) - 1280) } ?: Size(1280, 960)
        }
        // Prefer 4:3 resolution around 1280x960..1600x1200 for sharp essay paper capture
        return candidates.maxWithOrNull(
            compareBy<Size> {
                val ratio = max(it.width, it.height).toDouble() / it.width.coerceAtMost(it.height).coerceAtLeast(1)
                val closenessTo43 = 1.0 - abs(ratio - (4.0 / 3.0))
                (closenessTo43 * 1000).roundToInt()
            }.thenBy { it.width.toLong() * it.height.toLong() }
        ) ?: candidates.first()
    }

    private fun createCameraPreviewSession() {
        val device = cameraDevice ?: return
        val surfaceTexture = textureView.surfaceTexture ?: return
        surfaceTexture.setDefaultBufferSize(previewSize.width, previewSize.height)
        val previewSurface = Surface(surfaceTexture)

        try {
            val builder = device.createCaptureRequest(CameraDevice.TEMPLATE_PREVIEW).apply {
                addTarget(previewSurface)
                set(CaptureRequest.CONTROL_MODE, CaptureRequest.CONTROL_MODE_AUTO)
                set(CaptureRequest.CONTROL_AF_MODE, CaptureRequest.CONTROL_AF_MODE_CONTINUOUS_PICTURE)
                set(
                    CaptureRequest.FLASH_MODE,
                    if (flashEnabled && flashSupported) CaptureRequest.FLASH_MODE_TORCH else CaptureRequest.FLASH_MODE_OFF
                )
            }
            previewRequestBuilder = builder

            @Suppress("DEPRECATION")
            device.createCaptureSession(
                listOf(previewSurface),
                object : CameraCaptureSession.StateCallback() {
                    override fun onConfigured(session: CameraCaptureSession) {
                        if (cameraDevice == null || !isOpen) return
                        captureSession = session
                        updateRepeatingPreview()
                    }

                    override fun onConfigureFailed(session: CameraCaptureSession) {
                        mainHandler.post {
                            if (isOpen) {
                                tvStatus.text = "Gagal memulai pratinjau kamera."
                            }
                        }
                    }
                },
                backgroundHandler
            )
        } catch (_: Exception) {
            mainHandler.post {
                if (isOpen) {
                    tvStatus.text = "Kamera gagal dikonfigurasi."
                }
            }
        }
    }

    private fun updateRepeatingPreview() {
        val session = captureSession ?: return
        val builder = previewRequestBuilder ?: return
        try {
            builder.set(
                CaptureRequest.FLASH_MODE,
                if (flashEnabled && flashSupported) CaptureRequest.FLASH_MODE_TORCH else CaptureRequest.FLASH_MODE_OFF
            )
            session.setRepeatingRequest(builder.build(), null, backgroundHandler)
        } catch (_: Exception) {
            // Safe ignore if closing
        }
    }

    private fun triggerAutoFocus() {
        val session = captureSession ?: return
        val builder = previewRequestBuilder ?: return
        if (capturedBitmap != null) return
        try {
            builder.set(CaptureRequest.CONTROL_AF_TRIGGER, CaptureRequest.CONTROL_AF_TRIGGER_START)
            session.capture(builder.build(), null, backgroundHandler)
            builder.set(CaptureRequest.CONTROL_AF_TRIGGER, CaptureRequest.CONTROL_AF_TRIGGER_IDLE)
            tvStatus.text = "Memfokuskan..."
            mainHandler.postDelayed({
                if (isOpen && capturedBitmap == null) {
                    tvStatus.text = "Arahkan kamera ke kertas jawaban, ketuk layar untuk fokus"
                }
            }, 900)
        } catch (_: Exception) {
            // Ignore focus error on fixed-focus front cameras
        }
    }

    private fun switchLensFacing() {
        if (capturedBitmap != null) return
        currentLensFacing = if (currentLensFacing == CameraCharacteristics.LENS_FACING_BACK) {
            CameraCharacteristics.LENS_FACING_FRONT
        } else {
            CameraCharacteristics.LENS_FACING_BACK
        }
        flashEnabled = false
        if (textureView.isAvailable) {
            openSelectedCamera(textureView.width, textureView.height)
        }
    }

    private fun toggleFlash() {
        if (!flashSupported || capturedBitmap != null) return
        flashEnabled = !flashEnabled
        updateFlashButtonUi()
        updateRepeatingPreview()
    }

    private fun updateFlashButtonUi() {
        if (!flashSupported) {
            btnFlash.visibility = View.GONE
            return
        }
        btnFlash.visibility = View.VISIBLE
        btnFlash.text = if (flashEnabled) "Flash: On" else "Flash: Off"
    }

    private fun captureCurrentFrame() {
        if (!isOpen || !textureView.isAvailable || textureView.width <= 0 || textureView.height <= 0) {
            return
        }

        val viewW = textureView.width
        val viewH = textureView.height
        val maxView = max(viewW, viewH).coerceAtLeast(1)
        val scale = if (maxView < TARGET_CAPTURE_LONG_EDGE) {
            TARGET_CAPTURE_LONG_EDGE.toFloat() / maxView.toFloat()
        } else {
            MAX_EDGE_PX.toFloat() / maxView.toFloat()
        }.coerceAtMost(2.0f)

        val captureW = (viewW * scale).roundToInt().coerceIn(320, MAX_EDGE_PX)
        val captureH = (viewH * scale).roundToInt().coerceIn(320, MAX_EDGE_PX)

        val frame = textureView.getBitmap(captureW, captureH) ?: return
        clearCapturedBitmap()
        capturedBitmap = frame

        // Turn off torch while inspecting preview to save battery
        if (flashEnabled) {
            flashEnabled = false
            updateFlashButtonUi()
            updateRepeatingPreview()
        }

        previewImage.setImageBitmap(frame)
        previewImage.visibility = View.VISIBLE
        textureView.visibility = View.INVISIBLE
        controlsLive.visibility = View.GONE
        controlsPreview.visibility = View.VISIBLE
        tvStatus.text = "Periksa foto: pastikan tulisan terbaca jelas"
    }

    private fun returnToLivePreview() {
        clearCapturedBitmap()
        previewImage.setImageDrawable(null)
        previewImage.visibility = View.GONE
        textureView.visibility = View.VISIBLE
        controlsPreview.visibility = View.GONE
        controlsLive.visibility = View.VISIBLE
        tvStatus.text = "Arahkan kamera ke kertas jawaban, ketuk layar untuk fokus"
    }

    private fun confirmCapturedPhoto() {
        val bitmap = capturedBitmap ?: return
        val qId = activeQuestionId
        val replaceId = activeReplaceId
        val fileName = "kamera_jawaban_${System.currentTimeMillis()}.jpg"

        btnUsePhoto.isEnabled = false
        tvStatus.text = "Mengompresi & mengirim foto..."

        val handler = backgroundHandler
        if (handler == null) {
            encodeAndDeliver(bitmap, qId, replaceId, fileName)
        } else {
            handler.post { encodeAndDeliver(bitmap, qId, replaceId, fileName) }
        }
    }

    private fun encodeAndDeliver(bitmap: Bitmap, qId: String, replaceId: String, fileName: String) {
        val scaled = scaleDownIfNeeded(bitmap, MAX_EDGE_PX)
        val stream = ByteArrayOutputStream()
        scaled.compress(Bitmap.CompressFormat.JPEG, JPEG_QUALITY, stream)
        val jpegBytes = stream.toByteArray()
        if (scaled !== bitmap) {
            scaled.recycle()
        }
        val base64 = Base64.encodeToString(jpegBytes, Base64.NO_WRAP)

        mainHandler.post {
            btnUsePhoto.isEnabled = true
            close()
            onPhotoReady(qId, replaceId, base64, fileName)
        }
    }

    private fun scaleDownIfNeeded(source: Bitmap, maxEdge: Int): Bitmap {
        val maxSide = max(source.width, source.height)
        if (maxSide <= maxEdge) return source
        val ratio = maxEdge.toFloat() / maxSide.toFloat()
        val targetW = (source.width * ratio).roundToInt().coerceAtLeast(1)
        val targetH = (source.height * ratio).roundToInt().coerceAtLeast(1)
        return Bitmap.createScaledBitmap(source, targetW, targetH, true)
    }

    private fun configureTransform(viewWidth: Int, viewHeight: Int) {
        if (viewWidth <= 0 || viewHeight <= 0) return
        val rotation = getDisplayRotation()
        val matrix = Matrix()
        val viewRect = RectF(0f, 0f, viewWidth.toFloat(), viewHeight.toFloat())
        val bufferRect = RectF(0f, 0f, previewSize.height.toFloat(), previewSize.width.toFloat())
        val centerX = viewRect.centerX()
        val centerY = viewRect.centerY()

        if (Surface.ROTATION_90 == rotation || Surface.ROTATION_270 == rotation) {
            bufferRect.offset(centerX - bufferRect.centerX(), centerY - bufferRect.centerY())
            matrix.setRectToRect(viewRect, bufferRect, Matrix.ScaleToFit.FILL)
            val scale = max(
                viewHeight.toFloat() / previewSize.height.toFloat(),
                viewWidth.toFloat() / previewSize.width.toFloat()
            )
            matrix.postScale(scale, scale, centerX, centerY)
            matrix.postRotate((90 * (rotation - 2)).toFloat(), centerX, centerY)
        } else {
            val previewRatio = previewSize.height.toFloat() / previewSize.width.toFloat()
            val targetH = viewWidth.toFloat() / previewRatio
            val scaleY = targetH / viewHeight.toFloat()
            if (scaleY > 0f && abs(scaleY - 1f) > 0.02f) {
                matrix.setScale(1f, scaleY, centerX, centerY)
            }
            if (Surface.ROTATION_180 == rotation) {
                matrix.postRotate(180f, centerX, centerY)
            }
        }
        mainHandler.post { textureView.setTransform(matrix) }
    }

    @Suppress("DEPRECATION")
    private fun getDisplayRotation(): Int {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            activity.display?.rotation ?: Surface.ROTATION_0
        } else {
            activity.windowManager.defaultDisplay.rotation
        }
    }

    private fun clearCapturedBitmap() {
        capturedBitmap?.let {
            if (!it.isRecycled) it.recycle()
        }
        capturedBitmap = null
    }

    private fun closeCameraHardware() {
        try {
            captureSession?.close()
        } catch (_: Exception) {
        } finally {
            captureSession = null
        }

        try {
            cameraDevice?.close()
        } catch (_: Exception) {
        } finally {
            cameraDevice = null
        }
        previewRequestBuilder = null
    }

    private fun startBackgroundThread() {
        if (backgroundThread != null) return
        backgroundThread = HandlerThread("SmartCbtCameraThread").also {
            it.start()
            backgroundHandler = Handler(it.looper)
        }
    }

    private fun stopBackgroundThread() {
        backgroundThread?.quitSafely()
        try {
            backgroundThread?.join(500)
        } catch (_: InterruptedException) {
        } finally {
            backgroundThread = null
            backgroundHandler = null
        }
    }

    companion object {
        private const val MAX_EDGE_PX = 1600
        private const val TARGET_CAPTURE_LONG_EDGE = 1440
        private const val JPEG_QUALITY = 80
    }
}

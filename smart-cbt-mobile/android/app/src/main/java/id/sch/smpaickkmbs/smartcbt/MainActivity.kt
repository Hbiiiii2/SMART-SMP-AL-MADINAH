package id.sch.smpaickkmbs.smartcbt

import android.Manifest
import android.annotation.SuppressLint
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Color
import android.media.AudioManager
import android.media.ToneGenerator
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.BatteryManager
import android.os.Build
import android.os.Bundle
import android.os.SystemClock
import android.text.InputType
import android.view.View
import android.webkit.CookieManager
import android.webkit.PermissionRequest
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebResourceResponse
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import id.sch.smpaickkmbs.smartcbt.camera.InAppCameraController
import id.sch.smpaickkmbs.smartcbt.config.SchoolConfigManager
import id.sch.smpaickkmbs.smartcbt.databinding.ActivityMainBinding
import id.sch.smpaickkmbs.smartcbt.security.ExamLockController
import id.sch.smpaickkmbs.smartcbt.webview.SmartCbtJsBridge
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var configManager: SchoolConfigManager
    private lateinit var examLockController: ExamLockController
    private lateinit var cameraController: InAppCameraController

    private var currentUrl: String = ""
    private var pendingCameraAction: (() -> Unit)? = null
    private var pendingWebPermissionRequest: PermissionRequest? = null
    private var pendingFilePathCallback: ValueCallback<Array<Uri>>? = null
    private var isLaunchingInternalPicker = false
    private var isExambroDialogOpen = false
    private var lockTaskPromptedOnce = false

    private var titleTapCount = 0
    private var firstTitleTapMs = 0L

    private var connectivityManager: ConnectivityManager? = null
    private var networkCallback: ConnectivityManager.NetworkCallback? = null

    private val requestCameraPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        isLaunchingInternalPicker = false
        val webReq = pendingWebPermissionRequest
        pendingWebPermissionRequest = null
        if (webReq != null) {
            if (granted) {
                webReq.grant(arrayOf(PermissionRequest.RESOURCE_VIDEO_CAPTURE))
            } else {
                webReq.deny()
                Toast.makeText(this, "Izin kamera ditolak. Aktifkan di Pengaturan HP.", Toast.LENGTH_LONG).show()
            }
            return@registerForActivityResult
        }

        val action = pendingCameraAction
        pendingCameraAction = null
        if (granted) {
            action?.invoke()
        } else {
            Toast.makeText(this, "Izin kamera wajib diberikan untuk fitur foto jawaban / QR.", Toast.LENGTH_LONG).show()
        }
    }

    private val pickImageLauncher = registerForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        isLaunchingInternalPicker = false
        val callback = pendingFilePathCallback
        pendingFilePathCallback = null
        if (callback != null) {
            callback.onReceiveValue(if (uri != null) arrayOf(uri) else null)
        }
    }

    private val batteryReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            val level = intent?.getIntExtra(BatteryManager.EXTRA_LEVEL, -1) ?: -1
            val scale = intent?.getIntExtra(BatteryManager.EXTRA_SCALE, 100) ?: 100
            if (level >= 0 && scale > 0) {
                val pct = (level * 100) / scale
                binding.tvBattery.text = "$pct%"
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        applyEdgeToEdgeInsets()

        configManager = SchoolConfigManager(this)
        examLockController = ExamLockController()

        binding.tvAppTitle.text = configManager.getAppDisplayName()
        currentUrl = configManager.getBaseUrl()
        examLockController.clearClipboard(this)

        setupCameraController()
        updateSecurityBadgeAndWindow()
        setupToolbarActions()
        setupWebView()
        setupBackNavigationLock()
        registerBatteryAndNetworkMonitors()

        loadUrlSafely(configManager.getBaseUrl())
    }

    override fun onResume() {
        super.onResume()
        examLockController.applyWindowSecurityForUrl(
            this,
            currentUrl,
            configManager,
            ::cameraController.isInitialized && cameraController.isOverlayVisible()
        )
        checkMultiWindowRestriction()

        if (!lockTaskPromptedOnce) {
            lockTaskPromptedOnce = true
            examLockController.requestLockTask(this)
        }

        if (::cameraController.isInitialized) {
            cameraController.onResume()
        }
    }

    override fun onPause() {
        if (::cameraController.isInitialized) {
            cameraController.onPause()
        }
        super.onPause()
    }

    override fun onStop() {
        super.onStop()
        if (!isChangingConfigurations &&
            !isLaunchingInternalPicker &&
            !isFinishing &&
            examLockController.isActiveExamRoute(currentUrl, configManager)
        ) {
            playWarningBeep()
            runOnUiThread {
                if (!isFinishing && binding.layoutWarningOverlay.visibility != View.VISIBLE) {
                    binding.tvWarningTitle.text = "Terdeteksi Meninggalkan Halaman Ujian"
                    binding.tvWarningMessage.text =
                        "Anda terdeteksi keluar atau membuka aplikasi lain saat ujian berlangsung. Tetap berada di layar SMART-CBT sampai ujian selesai."
                    binding.btnWarningResume.visibility = View.VISIBLE
                    binding.layoutWarningOverlay.visibility = View.VISIBLE
                }
            }
        }
    }

    override fun onMultiWindowModeChanged(isInMultiWindowMode: Boolean, newConfig: android.content.res.Configuration) {
        super.onMultiWindowModeChanged(isInMultiWindowMode, newConfig)
        checkMultiWindowRestriction()
    }

    override fun onDestroy() {
        try {
            unregisterReceiver(batteryReceiver)
        } catch (_: Exception) {
        }
        try {
            networkCallback?.let { connectivityManager?.unregisterNetworkCallback(it) }
        } catch (_: Exception) {
        }
        if (::cameraController.isInitialized) {
            cameraController.close()
        }
        binding.webView.destroy()
        super.onDestroy()
    }

    private fun applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.rootLayout) { view, windowInsets ->
            val insets = windowInsets.getInsets(
                WindowInsetsCompat.Type.systemBars() or WindowInsetsCompat.Type.ime()
            )
            view.setPadding(insets.left, insets.top, insets.right, insets.bottom)
            windowInsets
        }
    }

    private fun setupCameraController() {
        cameraController = InAppCameraController(
            activity = this,
            overlayRoot = binding.layoutCameraOverlay,
            textureView = binding.cameraTextureView,
            previewImage = binding.cameraPreviewImage,
            controlsLive = binding.cameraControlsLive,
            controlsPreview = binding.cameraControlsPreview,
            btnCapture = binding.btnCapturePhoto,
            btnRetake = binding.btnRetakePhoto,
            btnUsePhoto = binding.btnUsePhoto,
            btnClose = binding.btnCameraClose,
            btnSwitch = binding.btnCameraSwitch,
            btnFlash = binding.btnCameraFlash,
            tvStatus = binding.tvCameraStatus,
            onPhotoReady = { questionId, replaceId, base64Jpeg, fileName ->
                if (questionId == FILE_CHOOSER_MODE_QUESTION_ID) {
                    deliverPhotoToFileChooser(base64Jpeg, fileName)
                } else {
                    deliverPhotoToWebBridge(questionId, replaceId, base64Jpeg, fileName)
                }
            },
            onCameraError = { questionId, message ->
                if (questionId == FILE_CHOOSER_MODE_QUESTION_ID) {
                    cancelPendingFileChooser()
                    Toast.makeText(this, message, Toast.LENGTH_LONG).show()
                } else {
                    deliverCameraErrorToWebBridge(questionId, message)
                }
            },
            onVisibilityChanged = { _ ->
                updateSecurityBadgeAndWindow()
            }
        )
    }

    private fun setupToolbarActions() {
        binding.btnRefresh.setOnClickListener {
            binding.layoutErrorOverlay.visibility = View.GONE
            if (binding.webView.url.isNullOrBlank()) {
                loadUrlSafely(configManager.getBaseUrl())
            } else {
                binding.webView.reload()
            }
        }

        binding.btnRetryLoad.setOnClickListener {
            binding.layoutErrorOverlay.visibility = View.GONE
            if (binding.webView.url.isNullOrBlank()) {
                loadUrlSafely(configManager.getBaseUrl())
            } else {
                binding.webView.reload()
            }
        }

        binding.btnExit.setOnClickListener { showExitPinDialog() }
        binding.btnWarningExit.setOnClickListener { showExitPinDialog() }

        binding.btnWarningResume.setOnClickListener {
            if (!isInMultiWindowMode) {
                binding.layoutWarningOverlay.visibility = View.GONE
                examLockController.requestLockTask(this)
            } else {
                Toast.makeText(this, "Keluar dari mode layar belah terlebih dahulu.", Toast.LENGTH_SHORT).show()
            }
        }

        // Hidden 7-tap gesture on SMART-CBT title for Operator/Admin domain settings
        binding.tvAppTitle.setOnClickListener {
            if (!configManager.isRuntimeDomainSwitchEnabled()) return@setOnClickListener
            val now = SystemClock.elapsedRealtime()
            if (now - firstTitleTapMs > 3000L) {
                firstTitleTapMs = now
                titleTapCount = 1
            } else {
                titleTapCount++
            }
            if (titleTapCount >= 7) {
                titleTapCount = 0
                firstTitleTapMs = 0L
                showAdminPinPromptDialog()
            }
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        val cookieManager = CookieManager.getInstance()
        cookieManager.setAcceptCookie(true)
        cookieManager.setAcceptThirdPartyCookies(binding.webView, true)

        binding.webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            allowContentAccess = true
            allowFileAccess = false
            mediaPlaybackRequiresUserGesture = false
            loadWithOverviewMode = true
            useWideViewPort = true
            builtInZoomControls = false
            displayZoomControls = false
            setSupportMultipleWindows(false)
            mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
            userAgentString = "${userAgentString} SmartCBT-Exambro/1.0 (Android)"
        }

        // Block long-press text copying/search menus on exam content while still permitting textarea focus
        binding.webView.setOnLongClickListener {
            val hit = binding.webView.hitTestResult
            hit.type != WebView.HitTestResult.EDIT_TEXT_TYPE
        }
        binding.webView.isHapticFeedbackEnabled = false

        val jsBridge = SmartCbtJsBridge(
            onOpenEssayCamera = { questionId, replaceId ->
                launchInAppCameraWithPermission {
                    cameraController.open(questionId, replaceId)
                }
            },
            onRouteChangedCallback = { routeOrUrl ->
                val resolved = if (routeOrUrl.startsWith("http", ignoreCase = true)) {
                    routeOrUrl
                } else {
                    configManager.getBaseUrl().trimEnd('/') + "/" + routeOrUrl.trimStart('/')
                }
                if (configManager.isHostAllowed(resolved)) {
                    currentUrl = resolved
                    updateSecurityBadgeAndWindow()
                }
            }
        )
        binding.webView.addJavascriptInterface(jsBridge, "SmartCbtNative")

        binding.webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                val targetUrl = request?.url?.toString() ?: return false
                if (configManager.isHostAllowed(targetUrl)) {
                    return false
                }
                val blockedHost = request.url?.host ?: targetUrl
                Toast.makeText(
                    this@MainActivity,
                    "Akses ke $blockedHost diblokir oleh SMART-CBT.",
                    Toast.LENGTH_SHORT
                ).show()
                return true
            }

            override fun shouldInterceptRequest(view: WebView?, request: WebResourceRequest?): WebResourceResponse? {
                val url = request?.url?.toString() ?: return super.shouldInterceptRequest(view, request)
                if (url.startsWith("data:", ignoreCase = true) ||
                    url.startsWith("blob:", ignoreCase = true) ||
                    configManager.isHostAllowed(url) ||
                    configManager.isSubresourceCdnAllowed(url)
                ) {
                    return super.shouldInterceptRequest(view, request)
                }
                // Block third-party trackers or unauthorized subresources quietly
                return WebResourceResponse("text/plain", "utf-8", null)
            }

            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                binding.layoutErrorOverlay.visibility = View.GONE
                if (!url.isNullOrBlank() && configManager.isHostAllowed(url)) {
                    currentUrl = url
                    updateSecurityBadgeAndWindow()
                }
            }

            override fun doUpdateVisitedHistory(view: WebView?, url: String?, isReload: Boolean) {
                super.doUpdateVisitedHistory(view, url, isReload)
                if (!url.isNullOrBlank() && configManager.isHostAllowed(url)) {
                    currentUrl = url
                    updateSecurityBadgeAndWindow()
                }
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                if (!url.isNullOrBlank() && configManager.isHostAllowed(url)) {
                    currentUrl = url
                    updateSecurityBadgeAndWindow()
                    injectRouteObserver(view)
                }
                CookieManager.getInstance().flush()
            }

            override fun onReceivedError(
                view: WebView?,
                request: WebResourceRequest?,
                error: WebResourceError?
            ) {
                super.onReceivedError(view, request, error)
                if (request?.isForMainFrame == true) {
                    binding.tvErrorMessage.text =
                        "Gagal memuat halaman ujian (${error?.description ?: "Koneksi bermasalah"}). Tekan Muat Ulang untuk mencoba kembali."
                    binding.layoutErrorOverlay.visibility = View.VISIBLE
                }
            }
        }

        binding.webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView?, newProgress: Int) {
                super.onProgressChanged(view, newProgress)
                if (newProgress in 1..99) {
                    binding.progressBar.visibility = View.VISIBLE
                    binding.progressBar.progress = newProgress
                } else {
                    binding.progressBar.visibility = View.GONE
                }
            }

            override fun onPermissionRequest(request: PermissionRequest?) {
                if (request == null) return
                val originUrl = request.origin?.toString()
                val wantsVideo = request.resources.contains(PermissionRequest.RESOURCE_VIDEO_CAPTURE)

                runOnUiThread {
                    if (wantsVideo && configManager.isHostAllowed(originUrl)) {
                        if (ContextCompat.checkSelfPermission(
                                this@MainActivity,
                                Manifest.permission.CAMERA
                            ) == PackageManager.PERMISSION_GRANTED
                        ) {
                            request.grant(arrayOf(PermissionRequest.RESOURCE_VIDEO_CAPTURE))
                        } else {
                            pendingWebPermissionRequest = request
                            isLaunchingInternalPicker = true
                            requestCameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                        }
                    } else {
                        request.deny()
                    }
                }
            }

            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                if (filePathCallback == null) return false
                cancelPendingFileChooser()
                pendingFilePathCallback = filePathCallback

                val isCapture = fileChooserParams?.isCaptureEnabled == true
                if (isCapture || !configManager.isGalleryAllowedDuringExam()) {
                    launchInAppCameraWithPermission {
                        cameraController.open(FILE_CHOOSER_MODE_QUESTION_ID, "")
                    }
                } else {
                    showInAppCameraOrGalleryDialog()
                }
                return true
            }
        }
    }

    private fun setupBackNavigationLock() {
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (::cameraController.isInitialized && cameraController.isOverlayVisible()) {
                    cameraController.close()
                    return
                }
                if (!examLockController.isActiveExamRoute(currentUrl, configManager) &&
                    binding.webView.canGoBack()
                ) {
                    binding.webView.goBack()
                    return
                }
                showExitPinDialog()
            }
        })
    }

    private fun loadUrlSafely(url: String) {
        val headers = mapOf("X-Smart-CBT-Client" to "android-exambro-${BuildConfig.VERSION_NAME}")
        binding.webView.loadUrl(url, headers)
    }

    private fun injectRouteObserver(view: WebView?) {
        val script = """
            (function() {
                if (window.__smartCbtRouteObserverInstalled) return;
                window.__smartCbtRouteObserverInstalled = true;
                var notify = function() {
                    if (window.SmartCbtNative && typeof window.SmartCbtNative.onRouteChanged === 'function') {
                        window.SmartCbtNative.onRouteChanged(window.location.href);
                    }
                };
                var origPush = history.pushState;
                history.pushState = function() {
                    var res = origPush.apply(this, arguments);
                    notify();
                    return res;
                };
                var origReplace = history.replaceState;
                history.replaceState = function() {
                    var res = origReplace.apply(this, arguments);
                    notify();
                    return res;
                };
                window.addEventListener('popstate', notify);
                notify();
            })();
        """.trimIndent()
        view?.evaluateJavascript(script, null)
    }

    private fun updateSecurityBadgeAndWindow() {
        val screenshotAllowed = examLockController.applyWindowSecurityForUrl(
            this,
            currentUrl,
            configManager,
            ::cameraController.isInitialized && cameraController.isOverlayVisible()
        )
        if (screenshotAllowed) {
            binding.tvSecurityBadge.text = getString(R.string.status_screenshot_allowed)
            binding.tvSecurityBadge.setBackgroundResource(R.drawable.bg_badge_allowed)
        } else {
            binding.tvSecurityBadge.text = getString(R.string.status_locked)
            binding.tvSecurityBadge.setBackgroundResource(R.drawable.bg_badge_locked)
        }
    }

    private fun launchInAppCameraWithPermission(action: () -> Unit) {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            action()
        } else {
            pendingCameraAction = action
            isLaunchingInternalPicker = true
            requestCameraPermissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    private fun showInAppCameraOrGalleryDialog() {
        AlertDialog.Builder(this)
            .setTitle("Pilih Sumber Foto Jawaban")
            .setItems(arrayOf("Kamera Aplikasi SMART-CBT", "Pilih dari Galeri / File")) { _, which ->
                if (which == 0) {
                    launchInAppCameraWithPermission {
                        cameraController.open(FILE_CHOOSER_MODE_QUESTION_ID, "")
                    }
                } else {
                    isLaunchingInternalPicker = true
                    pickImageLauncher.launch("image/*")
                }
            }
            .setOnCancelListener { cancelPendingFileChooser() }
            .show()
    }

    private fun cancelPendingFileChooser() {
        pendingFilePathCallback?.onReceiveValue(null)
        pendingFilePathCallback = null
    }

    private fun deliverPhotoToFileChooser(base64Jpeg: String, fileName: String) {
        val callback = pendingFilePathCallback ?: return
        pendingFilePathCallback = null
        try {
            val bytes = android.util.Base64.decode(base64Jpeg, android.util.Base64.NO_WRAP)
            val tempFile = File(cacheDir, fileName)
            FileOutputStream(tempFile).use { it.write(bytes) }
            callback.onReceiveValue(arrayOf(Uri.fromFile(tempFile)))
        } catch (_: Exception) {
            callback.onReceiveValue(null)
        }
    }

    private fun deliverPhotoToWebBridge(
        questionId: String,
        replaceAttachmentId: String,
        base64Jpeg: String,
        fileName: String
    ) {
        val qJson = JSONObject.quote(questionId)
        val b64Json = JSONObject.quote(base64Jpeg)
        val nameJson = JSONObject.quote(fileName)
        val replaceJson = JSONObject.quote(replaceAttachmentId)
        val js = "if (typeof window.__onSmartCbtNativePhoto === 'function') { window.__onSmartCbtNativePhoto($qJson, $b64Json, $nameJson, $replaceJson); }"
        binding.webView.evaluateJavascript(js, null)
    }

    private fun deliverCameraErrorToWebBridge(questionId: String, message: String) {
        val qJson = JSONObject.quote(questionId)
        val msgJson = JSONObject.quote(message)
        val js = "if (typeof window.__onSmartCbtNativeCameraError === 'function') { window.__onSmartCbtNativeCameraError($qJson, $msgJson); }"
        binding.webView.evaluateJavascript(js, null)
    }

    private fun showExitPinDialog() {
        if (isExambroDialogOpen) return
        val lockoutSec = examLockController.remainingExitLockoutSeconds()
        if (lockoutSec > 0) {
            Toast.makeText(
                this,
                "Terlalu banyak percobaan salah. Tunggu $lockoutSec detik.",
                Toast.LENGTH_SHORT
            ).show()
            return
        }

        val input = EditText(this).apply {
            hint = "Masukkan Kode Akses Guru"
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD
            setTextColor(Color.parseColor("#0F172A"))
            setHintTextColor(Color.parseColor("#64748B"))
        }
        val container = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(56, 24, 56, 8)
            addView(
                TextView(this@MainActivity).apply {
                    text = "Untuk keluar dari SMART-CBT, masukkan kode akses yang diberikan oleh guru/pengawas."
                    textSize = 13f
                    setTextColor(Color.parseColor("#334155"))
                }
            )
            addView(input)
        }

        isExambroDialogOpen = true
        val dialog = AlertDialog.Builder(this)
            .setTitle("Keluar dari SMART-CBT")
            .setView(container)
            .setPositiveButton("Keluar", null)
            .setNegativeButton("Batal") { d, _ -> d.dismiss() }
            .setOnDismissListener { isExambroDialogOpen = false }
            .create()

        dialog.setOnShowListener {
            dialog.getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
                val waitSec = examLockController.remainingExitLockoutSeconds()
                if (waitSec > 0) {
                    Toast.makeText(this, "Tunggu $waitSec detik sebelum mencoba lagi.", Toast.LENGTH_SHORT).show()
                    return@setOnClickListener
                }
                val entered = input.text?.toString().orEmpty()
                if (configManager.verifyExitPin(entered)) {
                    examLockController.recordExitPinResult(true)
                    dialog.dismiss()
                    examLockController.releaseLockTask(this)
                    examLockController.clearClipboard(this)
                    finishAndRemoveTask()
                } else {
                    val cooldown = examLockController.recordExitPinResult(false)
                    if (cooldown > 0) {
                        Toast.makeText(
                            this,
                            "Kode salah 3x. Dikunci sementara selama $cooldown detik.",
                            Toast.LENGTH_SHORT
                        ).show()
                    } else {
                        input.error = "Kode akses salah"
                    }
                }
            }
        }
        dialog.show()
    }

    private fun showAdminPinPromptDialog() {
        if (isExambroDialogOpen) return
        val input = EditText(this).apply {
            hint = "Admin Setup PIN"
            inputType = InputType.TYPE_CLASS_NUMBER or InputType.TYPE_NUMBER_VARIATION_PASSWORD
            setTextColor(Color.parseColor("#0F172A"))
            setHintTextColor(Color.parseColor("#64748B"))
        }
        val container = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(56, 24, 56, 8)
            addView(
                TextView(this@MainActivity).apply {
                    text = "Menu Operator Tersembunyi — masukkan Admin PIN untuk mengubah domain server atau kode akses keluar."
                    textSize = 13f
                    setTextColor(Color.parseColor("#334155"))
                }
            )
            addView(input)
        }

        isExambroDialogOpen = true
        AlertDialog.Builder(this)
            .setTitle("Verifikasi Operator")
            .setView(container)
            .setPositiveButton("Lanjut") { d, _ ->
                if (configManager.verifyAdminPin(input.text?.toString().orEmpty())) {
                    d.dismiss()
                    showAdminDomainSettingsDialog()
                } else {
                    Toast.makeText(this, "Admin PIN salah.", Toast.LENGTH_SHORT).show()
                }
            }
            .setNegativeButton("Batal") { d, _ -> d.dismiss() }
            .setOnDismissListener { isExambroDialogOpen = false }
            .show()
    }

    private fun showAdminDomainSettingsDialog() {
        val urlInput = EditText(this).apply {
            setText(configManager.getBaseUrl())
            hint = "https://ujian.sekolah.sch.id"
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
            setTextColor(Color.parseColor("#0F172A"))
            setHintTextColor(Color.parseColor("#64748B"))
        }
        val exitPinInput = EditText(this).apply {
            hint = "Kode Keluar Guru Baru (kosongkan jika tetap)"
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD
            setTextColor(Color.parseColor("#0F172A"))
            setHintTextColor(Color.parseColor("#64748B"))
        }
        val container = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(56, 24, 56, 8)
            addView(TextView(this@MainActivity).apply {
                text = "URL Domain Server Ujian:"
                textSize = 12f
                setTextColor(Color.parseColor("#334155"))
            })
            addView(urlInput)
            addView(TextView(this@MainActivity).apply {
                text = "Ganti Kode Akses Keluar Guru (opsional):"
                textSize = 12f
                setTextColor(Color.parseColor("#334155"))
                setPadding(0, 16, 0, 0)
            })
            addView(exitPinInput)
        }

        isExambroDialogOpen = true
        AlertDialog.Builder(this)
            .setTitle("Pengaturan Domain Sekolah")
            .setView(container)
            .setPositiveButton("Simpan & Muat") { d, _ ->
                val newUrl = urlInput.text?.toString()?.trim().orEmpty()
                if (newUrl.isBlank()) {
                    Toast.makeText(this, "URL domain tidak boleh kosong.", Toast.LENGTH_SHORT).show()
                    return@setPositiveButton
                }
                val newExitPin = exitPinInput.text?.toString()?.trim()?.takeIf { it.isNotBlank() }
                configManager.updateRuntimeConfig(newUrl, newExitPin)
                currentUrl = configManager.getBaseUrl()
                updateSecurityBadgeAndWindow()
                loadUrlSafely(currentUrl)
                Toast.makeText(this, "Domain diperbarui ke $currentUrl", Toast.LENGTH_SHORT).show()
                d.dismiss()
            }
            .setNeutralButton("Reset Default") { d, _ ->
                configManager.resetToDefaults()
                currentUrl = configManager.getBaseUrl()
                updateSecurityBadgeAndWindow()
                loadUrlSafely(currentUrl)
                Toast.makeText(this, "Kembali ke default ${BuildConfig.DEFAULT_BASE_URL}", Toast.LENGTH_SHORT).show()
                d.dismiss()
            }
            .setNegativeButton("Batal") { d, _ -> d.dismiss() }
            .setOnDismissListener { isExambroDialogOpen = false }
            .show()
    }

    private fun checkMultiWindowRestriction() {
        if (isInMultiWindowMode) {
            playWarningBeep()
            binding.tvWarningTitle.text = "Mode Layar Belah Diblokir"
            binding.tvWarningMessage.text =
                "Split-Screen atau Jendela Mengambang tidak diizinkan saat menggunakan SMART-CBT. Tutup layar belah untuk melanjutkan."
            binding.btnWarningResume.visibility = View.VISIBLE
            binding.layoutWarningOverlay.visibility = View.VISIBLE
        }
    }

    private fun registerBatteryAndNetworkMonitors() {
        try {
            val bm = getSystemService(Context.BATTERY_SERVICE) as? BatteryManager
            val cap = bm?.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY) ?: -1
            if (cap in 0..100) {
                binding.tvBattery.text = "$cap%"
            }
        } catch (_: Exception) {
        }

        val filter = IntentFilter(Intent.ACTION_BATTERY_CHANGED)
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                registerReceiver(batteryReceiver, filter, RECEIVER_NOT_EXPORTED)
            } else {
                @Suppress("DEPRECATION")
                registerReceiver(batteryReceiver, filter)
            }
        } catch (_: Exception) {
            // Ignore receiver restriction on custom ROMs
        }

        connectivityManager = getSystemService(Context.CONNECTIVITY_SERVICE) as? ConnectivityManager
        updateNetworkIndicator(isNetworkOnline())

        val callback = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                runOnUiThread { updateNetworkIndicator(true) }
            }

            override fun onLost(network: Network) {
                runOnUiThread { updateNetworkIndicator(isNetworkOnline()) }
            }
        }
        networkCallback = callback
        try {
            connectivityManager?.registerDefaultNetworkCallback(callback)
        } catch (_: Exception) {
            // Safe ignore if network monitor restricted
        }
    }

    private fun isNetworkOnline(): Boolean {
        val cm = connectivityManager ?: return false
        val active = cm.activeNetwork ?: return false
        val caps = cm.getNetworkCapabilities(active) ?: return false
        return caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    private fun updateNetworkIndicator(online: Boolean) {
        binding.viewNetworkDot.setBackgroundResource(
            if (online) R.drawable.bg_dot_online else R.drawable.bg_dot_offline
        )
    }

    private fun playWarningBeep() {
        try {
            val tone = ToneGenerator(AudioManager.STREAM_NOTIFICATION, 85)
            tone.startTone(ToneGenerator.TONE_PROP_BEEP2, 350)
            binding.rootLayout.postDelayed({ tone.release() }, 450)
        } catch (_: Exception) {
            // Ignore audio errors on muted profiles
        }
    }

    companion object {
        private const val FILE_CHOOSER_MODE_QUESTION_ID = "__file_chooser__"
    }
}

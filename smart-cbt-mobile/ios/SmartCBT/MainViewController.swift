import AudioToolbox
import Network
import UIKit
import WebKit

/// Wraps WKWebView inside an iOS secure-text layer when screenshots are blocked
/// and toggles protection off automatically on the exam completion receipt page.
final class SecureWebContainerView: UIView {

    private final class PassThroughSecureTextField: UITextField {
        override var canBecomeFirstResponder: Bool { false }
    }

    private let secureTextField: PassThroughSecureTextField = {
        let tf = PassThroughSecureTextField()
        tf.isSecureTextEntry = true
        tf.isUserInteractionEnabled = true
        tf.backgroundColor = .clear
        tf.borderStyle = .none
        return tf
    }()

    private weak var secureCanvas: UIView?
    let contentHolder = UIView()

    override init(frame: CGRect) {
        super.init(frame: frame)
        setupSecureHierarchy()
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) has not been implemented")
    }

    private func setupSecureHierarchy() {
        secureTextField.translatesAutoresizingMaskIntoConstraints = false
        addSubview(secureTextField)
        NSLayoutConstraint.activate([
            secureTextField.topAnchor.constraint(equalTo: topAnchor),
            secureTextField.leadingAnchor.constraint(equalTo: leadingAnchor),
            secureTextField.trailingAnchor.constraint(equalTo: trailingAnchor),
            secureTextField.bottomAnchor.constraint(equalTo: bottomAnchor)
        ])

        let canvas = secureTextField.subviews.first ?? self
        secureCanvas = canvas
        canvas.isUserInteractionEnabled = true

        contentHolder.translatesAutoresizingMaskIntoConstraints = false
        canvas.addSubview(contentHolder)
        NSLayoutConstraint.activate([
            contentHolder.topAnchor.constraint(equalTo: canvas.topAnchor),
            contentHolder.leadingAnchor.constraint(equalTo: canvas.leadingAnchor),
            contentHolder.trailingAnchor.constraint(equalTo: canvas.trailingAnchor),
            contentHolder.bottomAnchor.constraint(equalTo: canvas.bottomAnchor)
        ])
    }

    func setScreenshotBlocked(_ blocked: Bool) {
        if secureTextField.isSecureTextEntry != blocked {
            secureTextField.isSecureTextEntry = blocked
            secureTextField.setNeedsLayout()
            secureTextField.layoutIfNeeded()
        }
    }
}

final class MainViewController: UIViewController {

    private let configManager = SchoolConfigManager()
    private let examLockController = ExamLockController()

    private var currentUrl: String = ""
    private var inAppCameraOpen = false
    private var isOpeningInternalModal = false
    private var isExamUnlockedByProctor = false

    private var titleTapCount = 0
    private var firstTitleTapTime: TimeInterval = 0

    private var pathMonitor: NWPathMonitor?
    private let networkQueue = DispatchQueue(label: "id.sch.smpaickkmb.smartcbt.network")

    // MARK: - Views

    private let topBar: UIView = {
        let v = UIView()
        v.backgroundColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 1) // #0F172A
        return v
    }()

    private let appTitleLabel: UILabel = {
        let lbl = UILabel()
        lbl.text = "SMART-CBT"
        lbl.textColor = .white
        lbl.font = .systemFont(ofSize: 14, weight: .bold)
        lbl.isUserInteractionEnabled = true
        return lbl
    }()

    private let securityBadgeLabel: UILabel = {
        let lbl = UILabel()
        lbl.text = "Terkunci"
        lbl.textColor = .white
        lbl.font = .systemFont(ofSize: 11, weight: .bold)
        lbl.textAlignment = .center
        lbl.layer.cornerRadius = 11
        lbl.layer.masksToBounds = true
        lbl.layer.borderWidth = 1
        return lbl
    }()

    private let networkDotView: UIView = {
        let v = UIView()
        v.layer.cornerRadius = 4
        v.backgroundColor = UIColor(red: 0.06, green: 0.73, blue: 0.51, alpha: 1)
        return v
    }()

    private let batteryLabel: UILabel = {
        let lbl = UILabel()
        lbl.text = "--%"
        lbl.textColor = UIColor(red: 0.80, green: 0.84, blue: 0.88, alpha: 1)
        lbl.font = .systemFont(ofSize: 11, weight: .bold)
        return lbl
    }()

    private lazy var refreshButton: UIButton = {
        let btn = UIButton(type: .system)
        btn.setTitle("Muat", for: .normal)
        btn.setTitleColor(UIColor(red: 0.58, green: 0.77, blue: 0.99, alpha: 1), for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 12, weight: .bold)
        btn.addTarget(self, action: #selector(handleReloadTapped), for: .touchUpInside)
        return btn
    }()

    private lazy var exitButton: UIButton = {
        let btn = UIButton(type: .system)
        btn.setTitle("Keluar", for: .normal)
        btn.setTitleColor(.white, for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 12, weight: .bold)
        btn.backgroundColor = UIColor(red: 0.88, green: 0.11, blue: 0.28, alpha: 1)
        btn.layer.cornerRadius = 8
        btn.contentEdgeInsets = UIEdgeInsets(top: 6, left: 10, bottom: 6, right: 10)
        btn.addTarget(self, action: #selector(handleExitTapped), for: .touchUpInside)
        return btn
    }()

    private let secureContainer = SecureWebContainerView()
    private var webView: WKWebView!

    private let progressView: UIProgressView = {
        let pv = UIProgressView(progressViewStyle: .bar)
        pv.progressTintColor = UIColor(red: 0.15, green: 0.39, blue: 0.92, alpha: 1)
        pv.isHidden = true
        return pv
    }()

    private let errorOverlayView: UIView = {
        let v = UIView()
        v.backgroundColor = UIColor(red: 0.97, green: 0.98, blue: 0.99, alpha: 1)
        v.isHidden = true
        return v
    }()

    private let errorMessageLabel: UILabel = {
        let lbl = UILabel()
        lbl.textColor = UIColor(red: 0.39, green: 0.45, blue: 0.55, alpha: 1)
        lbl.font = .systemFont(ofSize: 14)
        lbl.textAlignment = .center
        lbl.numberOfLines = 0
        lbl.text = "Tidak dapat terhubung ke server ujian. Pastikan Wi-Fi atau data seluler aktif lalu coba lagi."
        return lbl
    }()

    private let warningOverlayView: UIView = {
        let v = UIView()
        v.backgroundColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 0.95)
        v.isHidden = true
        return v
    }()

    private let warningTitleLabel: UILabel = {
        let lbl = UILabel()
        lbl.text = "Peringatan Keamanan Ujian"
        lbl.textColor = UIColor(red: 0.96, green: 0.62, blue: 0.04, alpha: 1)
        lbl.font = .systemFont(ofSize: 20, weight: .bold)
        lbl.textAlignment = .center
        lbl.numberOfLines = 0
        return lbl
    }()

    private let warningMessageLabel: UILabel = {
        let lbl = UILabel()
        lbl.textColor = .white
        lbl.font = .systemFont(ofSize: 14)
        lbl.textAlignment = .center
        lbl.numberOfLines = 0
        return lbl
    }()

    private lazy var warningResumeButton: UIButton = {
        let btn = UIButton(type: .system)
        btn.setTitle("Lanjutkan Ujian", for: .normal)
        btn.setTitleColor(.white, for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 15, weight: .bold)
        btn.backgroundColor = UIColor(red: 0.15, green: 0.39, blue: 0.92, alpha: 1)
        btn.layer.cornerRadius = 10
        btn.addTarget(self, action: #selector(handleWarningResumeTapped), for: .touchUpInside)
        return btn
    }()

    private lazy var warningExitButton: UIButton = {
        let btn = UIButton(type: .system)
        btn.setTitle("Keluar (Kode Akses Guru)", for: .normal)
        btn.setTitleColor(.white, for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 15, weight: .bold)
        btn.backgroundColor = UIColor(red: 0.88, green: 0.11, blue: 0.28, alpha: 1)
        btn.layer.cornerRadius = 10
        btn.addTarget(self, action: #selector(handleExitTapped), for: .touchUpInside)
        return btn
    }()

    private var kvoProgressObservation: NSKeyValueObservation?
    private var kvoUrlObservation: NSKeyValueObservation?

    override var preferredStatusBarStyle: UIStatusBarStyle { .lightContent }

    override func viewDidLoad() {
        super.viewDidLoad()
        view.backgroundColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 1)

        appTitleLabel.text = configManager.getAppDisplayName()
        currentUrl = configManager.getBaseUrl()
        examLockController.clearClipboard()

        setupUI()
        setupWebView()
        setupObservers()
        updateSecurityBadgeAndWindow()

        examLockController.beginAssessmentLockdown()
        loadUrlSafely(configManager.getBaseUrl())
    }

    deinit {
        pathMonitor?.cancel()
        NotificationCenter.default.removeObserver(self)
        kvoProgressObservation?.invalidate()
        kvoUrlObservation?.invalidate()
    }

    // MARK: - Setup UI

    private func setupUI() {
        topBar.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(topBar)

        let titleTap = UITapGestureRecognizer(target: self, action: #selector(handleAppTitleTapped))
        appTitleLabel.addGestureRecognizer(titleTap)

        securityBadgeLabel.setContentHuggingPriority(.required, for: .horizontal)
        securityBadgeLabel.translatesAutoresizingMaskIntoConstraints = false

        let leftStack = UIStackView(arrangedSubviews: [appTitleLabel, securityBadgeLabel])
        leftStack.axis = .horizontal
        leftStack.spacing = 8
        leftStack.alignment = .center

        networkDotView.translatesAutoresizingMaskIntoConstraints = false
        NSLayoutConstraint.activate([
            networkDotView.widthAnchor.constraint(equalToConstant: 8),
            networkDotView.heightAnchor.constraint(equalToConstant: 8)
        ])

        let rightStack = UIStackView(arrangedSubviews: [networkDotView, batteryLabel, refreshButton, exitButton])
        rightStack.axis = .horizontal
        rightStack.spacing = 8
        rightStack.alignment = .center

        let barStack = UIStackView(arrangedSubviews: [leftStack, UIView(), rightStack])
        barStack.axis = .horizontal
        barStack.alignment = .center
        barStack.translatesAutoresizingMaskIntoConstraints = false
        topBar.addSubview(barStack)

        secureContainer.translatesAutoresizingMaskIntoConstraints = false
        secureContainer.backgroundColor = UIColor(red: 0.97, green: 0.98, blue: 0.99, alpha: 1)
        view.addSubview(secureContainer)

        progressView.translatesAutoresizingMaskIntoConstraints = false
        secureContainer.addSubview(progressView)

        setupErrorOverlay()
        setupWarningOverlay()

        let safe = view.safeAreaLayoutGuide
        NSLayoutConstraint.activate([
            topBar.topAnchor.constraint(equalTo: view.topAnchor),
            topBar.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            topBar.trailingAnchor.constraint(equalTo: view.trailingAnchor),

            barStack.topAnchor.constraint(equalTo: safe.topAnchor, constant: 4),
            barStack.leadingAnchor.constraint(equalTo: safe.leadingAnchor, constant: 12),
            barStack.trailingAnchor.constraint(equalTo: safe.trailingAnchor, constant: -10),
            barStack.bottomAnchor.constraint(equalTo: topBar.bottomAnchor, constant: -6),
            barStack.heightAnchor.constraint(greaterThanOrEqualToConstant: 40),

            securityBadgeLabel.widthAnchor.constraint(greaterThanOrEqualToConstant: 66),
            securityBadgeLabel.heightAnchor.constraint(equalToConstant: 22),

            secureContainer.topAnchor.constraint(equalTo: topBar.bottomAnchor),
            secureContainer.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            secureContainer.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            secureContainer.bottomAnchor.constraint(equalTo: view.bottomAnchor),

            progressView.topAnchor.constraint(equalTo: secureContainer.topAnchor),
            progressView.leadingAnchor.constraint(equalTo: secureContainer.leadingAnchor),
            progressView.trailingAnchor.constraint(equalTo: secureContainer.trailingAnchor),
            progressView.heightAnchor.constraint(equalToConstant: 3)
        ])
    }

    private func setupErrorOverlay() {
        errorOverlayView.translatesAutoresizingMaskIntoConstraints = false
        secureContainer.addSubview(errorOverlayView)

        let titleLbl = UILabel()
        titleLbl.text = "Koneksi Terputus"
        titleLbl.textColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 1)
        titleLbl.font = .systemFont(ofSize: 20, weight: .bold)
        titleLbl.textAlignment = .center

        let retryBtn = UIButton(type: .system)
        retryBtn.setTitle("Muat Ulang", for: .normal)
        retryBtn.setTitleColor(.white, for: .normal)
        retryBtn.titleLabel?.font = .systemFont(ofSize: 15, weight: .bold)
        retryBtn.backgroundColor = UIColor(red: 0.15, green: 0.39, blue: 0.92, alpha: 1)
        retryBtn.layer.cornerRadius = 10
        retryBtn.contentEdgeInsets = UIEdgeInsets(top: 12, left: 24, bottom: 12, right: 24)
        retryBtn.addTarget(self, action: #selector(handleReloadTapped), for: .touchUpInside)

        let stack = UIStackView(arrangedSubviews: [titleLbl, errorMessageLabel, retryBtn])
        stack.axis = .vertical
        stack.spacing = 14
        stack.alignment = .center
        stack.translatesAutoresizingMaskIntoConstraints = false
        errorOverlayView.addSubview(stack)

        NSLayoutConstraint.activate([
            errorOverlayView.topAnchor.constraint(equalTo: secureContainer.topAnchor),
            errorOverlayView.leadingAnchor.constraint(equalTo: secureContainer.leadingAnchor),
            errorOverlayView.trailingAnchor.constraint(equalTo: secureContainer.trailingAnchor),
            errorOverlayView.bottomAnchor.constraint(equalTo: secureContainer.bottomAnchor),

            stack.centerYAnchor.constraint(equalTo: errorOverlayView.centerYAnchor),
            stack.leadingAnchor.constraint(equalTo: errorOverlayView.leadingAnchor, constant: 24),
            stack.trailingAnchor.constraint(equalTo: errorOverlayView.trailingAnchor, constant: -24)
        ])
    }

    private func setupWarningOverlay() {
        warningOverlayView.translatesAutoresizingMaskIntoConstraints = false
        secureContainer.addSubview(warningOverlayView)

        let stack = UIStackView(arrangedSubviews: [
            warningTitleLabel,
            warningMessageLabel,
            warningResumeButton,
            warningExitButton
        ])
        stack.axis = .vertical
        stack.spacing = 12
        stack.alignment = .fill
        stack.translatesAutoresizingMaskIntoConstraints = false
        warningOverlayView.addSubview(stack)

        NSLayoutConstraint.activate([
            warningOverlayView.topAnchor.constraint(equalTo: secureContainer.topAnchor),
            warningOverlayView.leadingAnchor.constraint(equalTo: secureContainer.leadingAnchor),
            warningOverlayView.trailingAnchor.constraint(equalTo: secureContainer.trailingAnchor),
            warningOverlayView.bottomAnchor.constraint(equalTo: secureContainer.bottomAnchor),

            stack.centerYAnchor.constraint(equalTo: warningOverlayView.centerYAnchor),
            stack.leadingAnchor.constraint(equalTo: warningOverlayView.leadingAnchor, constant: 28),
            stack.trailingAnchor.constraint(equalTo: warningOverlayView.trailingAnchor, constant: -28),

            warningResumeButton.heightAnchor.constraint(equalToConstant: 48),
            warningExitButton.heightAnchor.constraint(equalToConstant: 48)
        ])
    }

    // MARK: - Setup WKWebView

    private func setupWebView() {
        let userContent = WKUserContentController()
        let jsBridge = SmartCbtJsBridge(
            appVersion: configManager.getAppVersion(),
            onOpenEssayCamera: { [weak self] questionId, replaceAttachmentId in
                self?.presentInAppCamera(questionId: questionId, replaceAttachmentId: replaceAttachmentId)
            },
            onRouteChanged: { [weak self] urlOrPath in
                self?.handleJsRouteChanged(urlOrPath)
            }
        )

        userContent.add(jsBridge, name: SmartCbtJsBridge.handlerName)
        userContent.addUserScript(jsBridge.makeBootstrapUserScript())
        userContent.addUserScript(jsBridge.makeCalloutDisableUserScript())

        let config = WKWebViewConfiguration()
        config.userContentController = userContent
        config.websiteDataStore = .default()
        config.allowsInlineMediaPlayback = true
        config.mediaTypesRequiringUserActionForPlayback = []

        webView = WKWebView(frame: .zero, configuration: config)
        webView.navigationDelegate = self
        webView.uiDelegate = self
        webView.allowsBackForwardNavigationGestures = false
        webView.customUserAgent = "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile SmartCBT-Exambro/1.0 (iOS)"
        webView.translatesAutoresizingMaskIntoConstraints = false

        secureContainer.contentHolder.addSubview(webView)
        NSLayoutConstraint.activate([
            webView.topAnchor.constraint(equalTo: secureContainer.contentHolder.topAnchor),
            webView.leadingAnchor.constraint(equalTo: secureContainer.contentHolder.leadingAnchor),
            webView.trailingAnchor.constraint(equalTo: secureContainer.contentHolder.trailingAnchor),
            webView.bottomAnchor.constraint(equalTo: secureContainer.contentHolder.bottomAnchor)
        ])

        kvoProgressObservation = webView.observe(\.estimatedProgress, options: [.new]) { [weak self] webView, _ in
            guard let self else { return }
            let progress = Float(webView.estimatedProgress)
            self.progressView.isHidden = progress >= 1.0 || progress <= 0.0
            self.progressView.setProgress(progress, animated: true)
        }

        kvoUrlObservation = webView.observe(\.url, options: [.new]) { [weak self] webView, _ in
            guard let self, let urlStr = webView.url?.absoluteString, self.configManager.isHostAllowed(urlStr) else {
                return
            }
            self.currentUrl = urlStr
            self.updateSecurityBadgeAndWindow()
        }
    }

    // MARK: - Observers (Battery, Network, Screen Recording)

    private func setupObservers() {
        UIDevice.current.isBatteryMonitoringEnabled = true
        updateBatteryLabel()
        NotificationCenter.default.addObserver(
            self,
            selector: #selector(updateBatteryLabel),
            name: UIDevice.batteryLevelDidChangeNotification,
            object: nil
        )

        NotificationCenter.default.addObserver(
            self,
            selector: #selector(handleScreenCaptureChanged),
            name: UIScreen.capturedDidChangeNotification,
            object: nil
        )

        NotificationCenter.default.addObserver(
            self,
            selector: #selector(handleUserTookScreenshot),
            name: UIApplication.userDidTakeScreenshotNotification,
            object: nil
        )

        let monitor = NWPathMonitor()
        monitor.pathUpdateHandler = { [weak self] path in
            let online = path.status == .satisfied
            DispatchQueue.main.async {
                self?.networkDotView.backgroundColor = online
                    ? UIColor(red: 0.06, green: 0.73, blue: 0.51, alpha: 1)
                    : UIColor(red: 0.94, green: 0.27, blue: 0.27, alpha: 1)
            }
        }
        pathMonitor = monitor
        monitor.start(queue: networkQueue)
    }

    @objc private func updateBatteryLabel() {
        let level = UIDevice.current.batteryLevel
        if level >= 0 {
            batteryLabel.text = "\(Int((level * 100).rounded()))%"
        } else {
            batteryLabel.text = "--%"
        }
    }

    @objc private func handleScreenCaptureChanged() {
        checkScreenCaptureProtection()
    }

    @objc private func handleUserTookScreenshot() {
        let allowScreenshot = examLockController.shouldAllowScreenshot(
            forUrl: currentUrl,
            config: configManager,
            inAppCameraOpen: inAppCameraOpen
        )
        if !allowScreenshot {
            playWarningAlertSound()
            showTransientToast("Screenshot diblokir pada halaman soal. Hanya diizinkan di halaman Ujian Selesai.")
        }
    }

    // MARK: - App Lifecycle Handlers

    func handleAppWillResignActive() {
        guard !isOpeningInternalModal,
              !inAppCameraOpen,
              !isExamUnlockedByProctor,
              examLockController.isActiveExamRoute(currentUrl, config: configManager) else {
            return
        }
        playWarningAlertSound()
        warningTitleLabel.text = "Terdeteksi Meninggalkan Halaman Ujian"
        warningMessageLabel.text =
            "Anda terdeteksi berpindah aplikasi atau membuka Control Center / notifikasi saat ujian berlangsung. Tetap berada di SMART-CBT hingga ujian selesai."
        warningResumeButton.isHidden = false
        warningOverlayView.isHidden = false
    }

    func handleAppDidBecomeActive() {
        updateSecurityBadgeAndWindow()
        checkScreenCaptureProtection()
    }

    // MARK: - Navigation & Security State

    private func loadUrlSafely(_ urlString: String) {
        guard let url = URL(string: urlString) else { return }
        var request = URLRequest(url: url)
        request.setValue("ios-exambro-\(configManager.getAppVersion())", forHTTPHeaderField: "X-Smart-CBT-Client")
        webView.load(request)
    }

    private func handleJsRouteChanged(_ urlOrPath: String) {
        let resolved: String
        if urlOrPath.lowercased().hasPrefix("http") {
            resolved = urlOrPath
        } else {
            let base = configManager.getBaseUrl().trimmingCharacters(in: CharacterSet(charactersIn: "/"))
            let path = urlOrPath.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
            resolved = "\(base)/\(path)"
        }
        if configManager.isHostAllowed(resolved) {
            currentUrl = resolved
            updateSecurityBadgeAndWindow()
        }
    }

    private func updateSecurityBadgeAndWindow() {
        let screenshotAllowed = examLockController.shouldAllowScreenshot(
            forUrl: currentUrl,
            config: configManager,
            inAppCameraOpen: inAppCameraOpen
        )

        secureContainer.setScreenshotBlocked(!screenshotAllowed)

        if isExamUnlockedByProctor {
            securityBadgeLabel.text = "Bebas Keluar"
            securityBadgeLabel.backgroundColor = UIColor(red: 0.02, green: 0.37, blue: 0.27, alpha: 1)
            securityBadgeLabel.layer.borderColor = UIColor(red: 0.06, green: 0.73, blue: 0.51, alpha: 1).cgColor
        } else if screenshotAllowed {
            securityBadgeLabel.text = "Screenshot OK"
            securityBadgeLabel.backgroundColor = UIColor(red: 0.02, green: 0.37, blue: 0.27, alpha: 1)
            securityBadgeLabel.layer.borderColor = UIColor(red: 0.06, green: 0.73, blue: 0.51, alpha: 1).cgColor
        } else {
            securityBadgeLabel.text = "Terkunci"
            securityBadgeLabel.backgroundColor = UIColor(red: 0.12, green: 0.23, blue: 0.54, alpha: 1)
            securityBadgeLabel.layer.borderColor = UIColor(red: 0.23, green: 0.51, blue: 0.96, alpha: 1).cgColor
        }

        checkScreenCaptureProtection()
    }

    private func checkScreenCaptureProtection() {
        let allowScreenshot = examLockController.shouldAllowScreenshot(
            forUrl: currentUrl,
            config: configManager,
            inAppCameraOpen: inAppCameraOpen
        )
        if !allowScreenshot && examLockController.isScreenBeingCaptured() && !isExamUnlockedByProctor {
            playWarningAlertSound()
            warningTitleLabel.text = "Rekaman Layar / AirPlay Diblokir"
            warningMessageLabel.text =
                "Matikan Screen Recording atau AirPlay Mirroring terlebih dahulu sebelum melanjutkan ujian."
            warningResumeButton.isHidden = false
            warningOverlayView.isHidden = false
        }
    }

    // MARK: - In-App Camera Bridge

    private func presentInAppCamera(questionId: String, replaceAttachmentId: String) {
        inAppCameraOpen = true
        isOpeningInternalModal = true
        updateSecurityBadgeAndWindow()

        let cameraVC = InAppCameraViewController(
            questionId: questionId,
            replaceAttachmentId: replaceAttachmentId,
            onPhotoCaptured: { [weak self] qId, replaceId, base64Jpeg, fileName in
                guard let self else { return }
                self.inAppCameraOpen = false
                self.isOpeningInternalModal = false
                self.updateSecurityBadgeAndWindow()
                self.deliverPhotoToWeb(questionId: qId, replaceId: replaceId, base64: base64Jpeg, fileName: fileName)
            },
            onCameraError: { [weak self] qId, message in
                guard let self else { return }
                self.inAppCameraOpen = false
                self.isOpeningInternalModal = false
                self.updateSecurityBadgeAndWindow()
                self.deliverCameraErrorToWeb(questionId: qId, message: message)
            }
        )
        present(cameraVC, animated: true)
    }

    private func deliverPhotoToWeb(questionId: String, replaceId: String, base64: String, fileName: String) {
        let qLiteral = jsonQuote(questionId)
        let b64Literal = jsonQuote(base64)
        let nameLiteral = jsonQuote(fileName)
        let replaceLiteral = jsonQuote(replaceId)
        let js = """
        if (typeof window.__onSmartCbtNativePhoto === 'function') {
            window.__onSmartCbtNativePhoto(\(qLiteral), \(b64Literal), \(nameLiteral), \(replaceLiteral));
        }
        """
        webView.evaluateJavaScript(js, completionHandler: nil)
    }

    private func deliverCameraErrorToWeb(questionId: String, message: String) {
        let qLiteral = jsonQuote(questionId)
        let msgLiteral = jsonQuote(message)
        let js = """
        if (typeof window.__onSmartCbtNativeCameraError === 'function') {
            window.__onSmartCbtNativeCameraError(\(qLiteral), \(msgLiteral));
        }
        """
        webView.evaluateJavaScript(js, completionHandler: nil)
    }

    private func jsonQuote(_ value: String) -> String {
        guard let data = try? JSONSerialization.data(withJSONObject: [value], options: []),
              let jsonArray = String(data: data, encoding: .utf8),
              jsonArray.count >= 2 else {
            return "\"\""
        }
        let start = jsonArray.index(after: jsonArray.startIndex)
        let end = jsonArray.index(before: jsonArray.endIndex)
        return String(jsonArray[start..<end])
    }

    // MARK: - Actions & Dialogs

    @objc private func handleReloadTapped() {
        errorOverlayView.isHidden = true
        if webView.url == nil {
            loadUrlSafely(configManager.getBaseUrl())
        } else {
            webView.reload()
        }
    }

    @objc private func handleWarningResumeTapped() {
        if examLockController.isScreenBeingCaptured() {
            showTransientToast("Matikan Screen Recording / Mirroring terlebih dahulu.")
            return
        }
        warningOverlayView.isHidden = true
    }

    @objc private func handleExitTapped() {
        let lockout = examLockController.remainingExitLockoutSeconds()
        if lockout > 0 {
            showTransientToast("Terlalu banyak percobaan salah. Tunggu \(lockout) detik.")
            return
        }

        let alert = UIAlertController(
            title: "Keluar dari SMART-CBT",
            message: "Masukkan kode akses yang diberikan oleh guru/pengawas untuk mengakhiri mode kunci ujian.",
            preferredStyle: .alert
        )
        alert.addTextField { tf in
            tf.placeholder = "Masukkan Kode Akses Guru"
            tf.isSecureTextEntry = true
        }
        alert.addAction(UIAlertAction(title: "Batal", style: .cancel))
        alert.addAction(UIAlertAction(title: "Verifikasi", style: .destructive) { [weak self] _ in
            guard let self else { return }
            let remaining = self.examLockController.remainingExitLockoutSeconds()
            if remaining > 0 {
                self.showTransientToast("Tunggu \(remaining) detik sebelum mencoba lagi.")
                return
            }

            let entered = alert.textFields?.first?.text ?? ""
            if self.configManager.verifyExitPin(entered) {
                self.examLockController.recordExitPinResult(success: true)
                self.unlockExamSessionByProctor()
            } else {
                let cooldown = self.examLockController.recordExitPinResult(success: false)
                if cooldown > 0 {
                    self.showTransientToast("Kode salah 3x. Dikunci sementara selama \(cooldown) detik.")
                } else {
                    self.showTransientToast("Kode akses keluar salah.")
                }
            }
        })
        present(alert, animated: true)
    }

    private func unlockExamSessionByProctor() {
        isExamUnlockedByProctor = true
        examLockController.endAssessmentLockdown()
        examLockController.clearClipboard()
        updateSecurityBadgeAndWindow()

        let doneAlert = UIAlertController(
            title: "Kunci Ujian Dinonaktifkan",
            message: "Sesi penguncian SMART-CBT telah dibuka oleh pengawas. Anda sekarang dapat menekan tombol Home / swipe ke atas untuk keluar dari aplikasi.",
            preferredStyle: .alert
        )
        doneAlert.addAction(UIAlertAction(title: "Kembali Kunci Ujian", style: .default) { [weak self] _ in
            guard let self else { return }
            self.isExamUnlockedByProctor = false
            self.examLockController.beginAssessmentLockdown()
            self.updateSecurityBadgeAndWindow()
        })
        present(doneAlert, animated: true)
    }

    @objc private func handleAppTitleTapped() {
        guard configManager.isRuntimeDomainSwitchEnabled() else { return }
        let now = Date().timeIntervalSince1970
        if now - firstTitleTapTime > 3.0 {
            firstTitleTapTime = now
            titleTapCount = 1
        } else {
            titleTapCount += 1
        }
        if titleTapCount >= 7 {
            titleTapCount = 0
            firstTitleTapTime = 0
            showAdminPinPromptDialog()
        }
    }

    private func showAdminPinPromptDialog() {
        let alert = UIAlertController(
            title: "Verifikasi Operator",
            message: "Menu Operator Tersembunyi — masukkan Admin PIN untuk mengubah domain server atau kode keluar.",
            preferredStyle: .alert
        )
        alert.addTextField { tf in
            tf.placeholder = "Admin Setup PIN"
            tf.keyboardType = .numberPad
            tf.isSecureTextEntry = true
        }
        alert.addAction(UIAlertAction(title: "Batal", style: .cancel))
        alert.addAction(UIAlertAction(title: "Lanjut", style: .default) { [weak self] _ in
            guard let self else { return }
            let pin = alert.textFields?.first?.text ?? ""
            if self.configManager.verifyAdminPin(pin) {
                self.showAdminDomainSettingsDialog()
            } else {
                self.showTransientToast("Admin PIN salah.")
            }
        })
        present(alert, animated: true)
    }

    private func showAdminDomainSettingsDialog() {
        let alert = UIAlertController(
            title: "Pengaturan Domain Sekolah",
            message: "Atur URL server ujian untuk sekolah lain atau testing lokal.",
            preferredStyle: .alert
        )
        alert.addTextField { [weak self] tf in
            tf.text = self?.configManager.getBaseUrl()
            tf.placeholder = "https://ujian.sekolah.sch.id"
            tf.keyboardType = .URL
            tf.autocapitalizationType = .none
        }
        alert.addTextField { tf in
            tf.placeholder = "Kode Keluar Guru Baru (opsional)"
            tf.isSecureTextEntry = true
        }
        alert.addAction(UIAlertAction(title: "Batal", style: .cancel))
        alert.addAction(UIAlertAction(title: "Reset Default", style: .destructive) { [weak self] _ in
            guard let self else { return }
            self.configManager.resetToDefaults()
            self.currentUrl = self.configManager.getBaseUrl()
            self.updateSecurityBadgeAndWindow()
            self.loadUrlSafely(self.currentUrl)
            self.showTransientToast("Kembali ke default \(self.currentUrl)")
        })
        alert.addAction(UIAlertAction(title: "Simpan & Muat", style: .default) { [weak self] _ in
            guard let self else { return }
            let newUrl = alert.textFields?.first?.text?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            guard !newUrl.isEmpty else {
                self.showTransientToast("URL domain tidak boleh kosong.")
                return
            }
            let newPin = alert.textFields?.last?.text?.trimmingCharacters(in: .whitespacesAndNewlines)
            self.configManager.updateRuntimeConfig(newBaseUrl: newUrl, newExitPin: newPin)
            self.currentUrl = self.configManager.getBaseUrl()
            self.updateSecurityBadgeAndWindow()
            self.loadUrlSafely(self.currentUrl)
            self.showTransientToast("Domain diperbarui ke \(self.currentUrl)")
        })
        present(alert, animated: true)
    }

    private func playWarningAlertSound() {
        AudioServicesPlaySystemSound(kSystemSoundID_Vibrate)
        AudioServicesPlaySystemSound(1053)
    }

    private func showTransientToast(_ message: String) {
        let toast = UILabel()
        toast.text = "  \(message)  "
        toast.textColor = .white
        toast.backgroundColor = UIColor(white: 0.1, alpha: 0.88)
        toast.font = .systemFont(ofSize: 12, weight: .semibold)
        toast.textAlignment = .center
        toast.numberOfLines = 2
        toast.layer.cornerRadius = 10
        toast.layer.masksToBounds = true
        toast.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(toast)

        NSLayoutConstraint.activate([
            toast.centerXAnchor.constraint(equalTo: view.centerXAnchor),
            toast.bottomAnchor.constraint(equalTo: view.safeAreaLayoutGuide.bottomAnchor, constant: -24),
            toast.leadingAnchor.constraint(greaterThanOrEqualTo: view.leadingAnchor, constant: 24),
            toast.trailingAnchor.constraint(lessThanOrEqualTo: view.trailingAnchor, constant: -24),
            toast.heightAnchor.constraint(greaterThanOrEqualToConstant: 36)
        ])

        UIView.animate(withDuration: 0.25, delay: 2.3, options: []) {
            toast.alpha = 0
        } completion: { _ in
            toast.removeFromSuperview()
        }
    }
}

// MARK: - WKNavigationDelegate

extension MainViewController: WKNavigationDelegate {
    func webView(
        _ webView: WKWebView,
        decidePolicyFor navigationAction: WKNavigationAction,
        decisionHandler: @escaping (WKNavigationActionPolicy) -> Void
    ) {
        let targetUrl = navigationAction.request.url?.absoluteString
        if configManager.isHostAllowed(targetUrl) {
            decisionHandler(.allow)
            return
        }
        if navigationAction.targetFrame?.isMainFrame == false && configManager.isSubresourceCdnAllowed(targetUrl) {
            decisionHandler(.allow)
            return
        }
        let blockedHost = navigationAction.request.url?.host ?? (targetUrl ?? "eksternal")
        showTransientToast("Akses ke \(blockedHost) diblokir oleh SMART-CBT.")
        decisionHandler(.cancel)
    }

    func webView(_ webView: WKWebView, didStartProvisionalNavigation navigation: WKNavigation!) {
        errorOverlayView.isHidden = true
        if let urlStr = webView.url?.absoluteString, configManager.isHostAllowed(urlStr) {
            currentUrl = urlStr
            updateSecurityBadgeAndWindow()
        }
    }

    func webView(_ webView: WKWebView, didFinish navigation: WKNavigation!) {
        if let urlStr = webView.url?.absoluteString, configManager.isHostAllowed(urlStr) {
            currentUrl = urlStr
            updateSecurityBadgeAndWindow()
        }
    }

    func webView(
        _ webView: WKWebView,
        didFailProvisionalNavigation navigation: WKNavigation!,
        withError error: Error
    ) {
        let nsErr = error as NSError
        if nsErr.code == NSURLErrorCancelled { return }
        errorMessageLabel.text =
            "Gagal memuat halaman ujian (\(error.localizedDescription)). Tekan Muat Ulang untuk mencoba kembali."
        errorOverlayView.isHidden = false
    }

    func webView(_ webView: WKWebView, didFail navigation: WKNavigation!, withError error: Error) {
        let nsErr = error as NSError
        if nsErr.code == NSURLErrorCancelled { return }
        errorMessageLabel.text =
            "Gagal memuat halaman ujian (\(error.localizedDescription)). Tekan Muat Ulang untuk mencoba kembali."
        errorOverlayView.isHidden = false
    }
}

// MARK: - WKUIDelegate (WebRTC Camera Auto-Grant for Scan QR & Embedded Web Camera)

extension MainViewController: WKUIDelegate {
    @available(iOS 15.0, *)
    func webView(
        _ webView: WKWebView,
        requestMediaCapturePermissionFor origin: WKSecurityOrigin,
        initiatedByFrame frame: WKFrameInfo,
        type: WKMediaCaptureType,
        decisionHandler: @escaping (WKPermissionDecision) -> Void
    ) {
        let host = origin.host.lowercased()
        let isAllowed = configManager.getAllowedHosts().contains(host)
        if isAllowed && (type == .camera || type == .cameraAndMicrophone) {
            decisionHandler(.grant)
        } else {
            decisionHandler(.deny)
        }
    }

    func webView(
        _ webView: WKWebView,
        createWebViewWith configuration: WKWebViewConfiguration,
        for navigationAction: WKNavigationAction,
        windowFeatures: WKWindowFeatures
    ) -> WKWebView? {
        if let urlStr = navigationAction.request.url?.absoluteString,
           configManager.isHostAllowed(urlStr),
           let url = navigationAction.request.url {
            webView.load(URLRequest(url: url))
        }
        return nil
    }
}

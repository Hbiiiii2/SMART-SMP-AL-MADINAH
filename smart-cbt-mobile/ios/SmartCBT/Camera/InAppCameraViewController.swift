import AVFoundation
import UIKit

/// Built-in AVFoundation in-app camera controller for essay photo capture.
/// Runs completely inside the app container without invoking external apps.
final class InAppCameraViewController: UIViewController {

    private let questionId: String
    private let replaceAttachmentId: String
    private let onPhotoCaptured: (_ questionId: String, _ replaceAttachmentId: String, _ base64Jpeg: String, _ fileName: String) -> Void
    private let onCameraError: (_ questionId: String, _ message: String) -> Void

    private let captureSession = AVCaptureSession()
    private let sessionQueue = DispatchQueue(label: "id.sch.smpaickkmb.smartcbt.camera")
    private let photoOutput = AVCapturePhotoOutput()

    private var previewLayer: AVCaptureVideoPreviewLayer?
    private var currentPosition: AVCaptureDevice.Position = .back
    private var flashEnabled = false
    private var capturedImage: UIImage?
    private var isConfigured = false

    // MARK: - UI Elements

    private let previewContainer = UIView()
    private let capturedImageView: UIImageView = {
        let iv = UIImageView()
        iv.contentMode = .scaleAspectFit
        iv.backgroundColor = .black
        iv.isHidden = true
        return iv
    }()

    private let statusLabel: UILabel = {
        let lbl = UILabel()
        lbl.text = "Arahkan kamera ke kertas jawaban, ketuk layar untuk fokus"
        lbl.textColor = UIColor(white: 0.9, alpha: 1)
        lbl.font = .systemFont(ofSize: 12, weight: .medium)
        lbl.textAlignment = .center
        lbl.numberOfLines = 2
        return lbl
    }()

    private lazy var flashButton: UIButton = makeTopButton(title: "Flash: Off", color: .systemYellow, action: #selector(toggleFlash))
    private lazy var switchButton: UIButton = makeTopButton(title: "Balik", color: UIColor(red: 0.58, green: 0.77, blue: 0.99, alpha: 1), action: #selector(switchCamera))
    private lazy var closeButton: UIButton = makeTopButton(title: "Tutup", color: .white, action: #selector(closeCamera))

    private let liveControlsStack = UIStackView()
    private let previewControlsStack = UIStackView()

    private let shutterButton: UIButton = {
        let btn = UIButton(type: .system)
        btn.backgroundColor = .white
        btn.layer.cornerRadius = 36
        btn.layer.borderWidth = 4
        btn.layer.borderColor = UIColor.systemBlue.cgColor
        return btn
    }()

    private lazy var retakeButton: UIButton = makeActionButton(
        title: "Ulangi",
        backgroundColor: UIColor(red: 0.20, green: 0.25, blue: 0.33, alpha: 1),
        action: #selector(retakePhoto)
    )

    private lazy var usePhotoButton: UIButton = makeActionButton(
        title: "Gunakan Foto",
        backgroundColor: UIColor(red: 0.02, green: 0.59, blue: 0.41, alpha: 1),
        action: #selector(confirmPhoto)
    )

    init(
        questionId: String,
        replaceAttachmentId: String,
        onPhotoCaptured: @escaping (_ questionId: String, _ replaceAttachmentId: String, _ base64Jpeg: String, _ fileName: String) -> Void,
        onCameraError: @escaping (_ questionId: String, _ message: String) -> Void
    ) {
        self.questionId = questionId
        self.replaceAttachmentId = replaceAttachmentId
        self.onPhotoCaptured = onPhotoCaptured
        self.onCameraError = onCameraError
        super.init(nibName: nil, bundle: nil)
        modalPresentationStyle = .fullScreen
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) has not been implemented")
    }

    override func viewDidLoad() {
        super.viewDidLoad()
        view.backgroundColor = .black
        setupLayout()
        checkPermissionAndStartCamera()
    }

    override func viewDidLayoutSubviews() {
        super.viewDidLayoutSubviews()
        previewLayer?.frame = previewContainer.bounds
        updatePreviewOrientation()
    }

    override func viewWillDisappear(_ animated: Bool) {
        super.viewWillDisappear(animated)
        sessionQueue.async { [weak self] in
            guard let self, self.captureSession.isRunning else { return }
            self.captureSession.stopRunning()
        }
    }

    // MARK: - Layout

    private func setupLayout() {
        previewContainer.backgroundColor = .black
        previewContainer.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(previewContainer)

        capturedImageView.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(capturedImageView)

        let tapFocus = UITapGestureRecognizer(target: self, action: #selector(handleTapToFocus(_:)))
        previewContainer.addGestureRecognizer(tapFocus)

        // Top Bar
        let topBar = UIView()
        topBar.backgroundColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 0.78)
        topBar.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(topBar)

        let titleLabel = UILabel()
        titleLabel.text = "Foto Jawaban Essay"
        titleLabel.textColor = .white
        titleLabel.font = .systemFont(ofSize: 15, weight: .bold)

        let topStack = UIStackView(arrangedSubviews: [titleLabel, UIView(), flashButton, switchButton, closeButton])
        topStack.axis = .horizontal
        topStack.spacing = 8
        topStack.alignment = .center
        topStack.translatesAutoresizingMaskIntoConstraints = false
        topBar.addSubview(topStack)

        // Bottom Bar
        let bottomBar = UIView()
        bottomBar.backgroundColor = UIColor(red: 0.06, green: 0.09, blue: 0.16, alpha: 0.85)
        bottomBar.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(bottomBar)

        shutterButton.translatesAutoresizingMaskIntoConstraints = false
        shutterButton.addTarget(self, action: #selector(capturePhoto), for: .touchUpInside)

        liveControlsStack.axis = .horizontal
        liveControlsStack.alignment = .center
        liveControlsStack.distribution = .equalCentering
        liveControlsStack.addArrangedSubview(shutterButton)

        previewControlsStack.axis = .horizontal
        previewControlsStack.spacing = 12
        previewControlsStack.distribution = .fillEqually
        previewControlsStack.addArrangedSubview(retakeButton)
        previewControlsStack.addArrangedSubview(usePhotoButton)
        previewControlsStack.isHidden = true

        let bottomStack = UIStackView(arrangedSubviews: [statusLabel, liveControlsStack, previewControlsStack])
        bottomStack.axis = .vertical
        bottomStack.spacing = 12
        bottomStack.alignment = .fill
        bottomStack.translatesAutoresizingMaskIntoConstraints = false
        bottomBar.addSubview(bottomStack)

        let safe = view.safeAreaLayoutGuide
        NSLayoutConstraint.activate([
            previewContainer.topAnchor.constraint(equalTo: view.topAnchor),
            previewContainer.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            previewContainer.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            previewContainer.bottomAnchor.constraint(equalTo: view.bottomAnchor),

            capturedImageView.topAnchor.constraint(equalTo: previewContainer.topAnchor),
            capturedImageView.leadingAnchor.constraint(equalTo: previewContainer.leadingAnchor),
            capturedImageView.trailingAnchor.constraint(equalTo: previewContainer.trailingAnchor),
            capturedImageView.bottomAnchor.constraint(equalTo: previewContainer.bottomAnchor),

            topBar.topAnchor.constraint(equalTo: view.topAnchor),
            topBar.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            topBar.trailingAnchor.constraint(equalTo: view.trailingAnchor),

            topStack.topAnchor.constraint(equalTo: safe.topAnchor, constant: 8),
            topStack.leadingAnchor.constraint(equalTo: safe.leadingAnchor, constant: 12),
            topStack.trailingAnchor.constraint(equalTo: safe.trailingAnchor, constant: -12),
            topStack.bottomAnchor.constraint(equalTo: topBar.bottomAnchor, constant: -10),

            bottomBar.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            bottomBar.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            bottomBar.bottomAnchor.constraint(equalTo: view.bottomAnchor),

            bottomStack.topAnchor.constraint(equalTo: bottomBar.topAnchor, constant: 14),
            bottomStack.leadingAnchor.constraint(equalTo: safe.leadingAnchor, constant: 16),
            bottomStack.trailingAnchor.constraint(equalTo: safe.trailingAnchor, constant: -16),
            bottomStack.bottomAnchor.constraint(equalTo: safe.bottomAnchor, constant: -14),

            shutterButton.widthAnchor.constraint(equalToConstant: 72),
            shutterButton.heightAnchor.constraint(equalToConstant: 72),
            retakeButton.heightAnchor.constraint(equalToConstant: 48),
            usePhotoButton.heightAnchor.constraint(equalToConstant: 48)
        ])
    }

    private func makeTopButton(title: String, color: UIColor, action: Selector) -> UIButton {
        let btn = UIButton(type: .system)
        btn.setTitle(title, for: .normal)
        btn.setTitleColor(color, for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 13, weight: .bold)
        btn.addTarget(self, action: action, for: .touchUpInside)
        return btn
    }

    private func makeActionButton(title: String, backgroundColor: UIColor, action: Selector) -> UIButton {
        let btn = UIButton(type: .system)
        btn.setTitle(title, for: .normal)
        btn.setTitleColor(.white, for: .normal)
        btn.titleLabel?.font = .systemFont(ofSize: 15, weight: .bold)
        btn.backgroundColor = backgroundColor
        btn.layer.cornerRadius = 10
        btn.addTarget(self, action: action, for: .touchUpInside)
        return btn
    }

    // MARK: - Camera Setup

    private func checkPermissionAndStartCamera() {
        switch AVCaptureDevice.authorizationStatus(for: .video) {
        case .authorized:
            configureAndStartSession()
        case .notDetermined:
            AVCaptureDevice.requestAccess(for: .video) { [weak self] granted in
                DispatchQueue.main.async {
                    guard let self else { return }
                    if granted {
                        self.configureAndStartSession()
                    } else {
                        self.dismiss(animated: true) {
                            self.onCameraError(self.questionId, "Izin kamera ditolak. Aktifkan izin kamera untuk SMART-CBT di Pengaturan iOS.")
                        }
                    }
                }
            }
        default:
            dismiss(animated: true) {
                self.onCameraError(self.questionId, "Akses kamera belum diizinkan di Pengaturan iOS.")
            }
        }
    }

    private func configureAndStartSession() {
        sessionQueue.async { [weak self] in
            guard let self else { return }
            self.captureSession.beginConfiguration()
            self.captureSession.sessionPreset = .photo

            for input in self.captureSession.inputs {
                self.captureSession.removeInput(input)
            }

            guard let device = self.bestCaptureDevice(position: self.currentPosition),
                  let input = try? AVCaptureDeviceInput(device: device),
                  self.captureSession.canAddInput(input) else {
                self.captureSession.commitConfiguration()
                DispatchQueue.main.async {
                    self.dismiss(animated: true) {
                        self.onCameraError(self.questionId, "Kamera tidak tersedia pada perangkat ini.")
                    }
                }
                return
            }
            self.captureSession.addInput(input)

            if !self.isConfigured, self.captureSession.canAddOutput(self.photoOutput) {
                self.captureSession.addOutput(self.photoOutput)
                self.isConfigured = true
            }
            self.captureSession.commitConfiguration()

            let hasFlash = device.hasFlash || device.hasTorch
            DispatchQueue.main.async {
                self.flashButton.isHidden = !hasFlash
                self.attachPreviewLayerIfNeeded()
                self.updatePreviewOrientation()
            }

            if !self.captureSession.isRunning {
                self.captureSession.startRunning()
            }
        }
    }

    private func bestCaptureDevice(position: AVCaptureDevice.Position) -> AVCaptureDevice? {
        if let camera = AVCaptureDevice.default(.builtInWideAngleCamera, for: .video, position: position) {
            return camera
        }
        return AVCaptureDevice.default(for: .video)
    }

    private func attachPreviewLayerIfNeeded() {
        if previewLayer == nil {
            let layer = AVCaptureVideoPreviewLayer(session: captureSession)
            layer.videoGravity = .resizeAspect
            layer.frame = previewContainer.bounds
            previewContainer.layer.addSublayer(layer)
            previewLayer = layer
        }
    }

    private func updatePreviewOrientation() {
        guard let connection = previewLayer?.connection, connection.isVideoOrientationSupported else { return }
        let orientation = view.window?.windowScene?.interfaceOrientation ?? .portrait
        switch orientation {
        case .landscapeLeft:
            connection.videoOrientation = .landscapeLeft
        case .landscapeRight:
            connection.videoOrientation = .landscapeRight
        case .portraitUpsideDown:
            connection.videoOrientation = .portraitUpsideDown
        default:
            connection.videoOrientation = .portrait
        }
    }

    // MARK: - Actions

    @objc private func toggleFlash() {
        flashEnabled.toggle()
        flashButton.setTitle(flashEnabled ? "Flash: On" : "Flash: Off", for: .normal)
    }

    @objc private func switchCamera() {
        guard capturedImage == nil else { return }
        currentPosition = (currentPosition == .back) ? .front : .back
        flashEnabled = false
        flashButton.setTitle("Flash: Off", for: .normal)
        configureAndStartSession()
    }

    @objc private func closeCamera() {
        dismiss(animated: true)
    }

    @objc private func handleTapToFocus(_ recognizer: UITapGestureRecognizer) {
        guard capturedImage == nil, let previewLayer else { return }
        let point = recognizer.location(in: previewContainer)
        let devicePoint = previewLayer.captureDevicePointConverted(fromLayerPoint: point)

        sessionQueue.async { [weak self] in
            guard let self,
                  let input = self.captureSession.inputs.first as? AVCaptureDeviceInput else { return }
            let device = input.device
            do {
                try device.lockForConfiguration()
                if device.isFocusPointOfInterestSupported {
                    device.focusPointOfInterest = devicePoint
                    device.focusMode = .continuousAutoFocus
                }
                if device.isExposurePointOfInterestSupported {
                    device.exposurePointOfInterest = devicePoint
                    device.exposureMode = .continuousAutoExposure
                }
                device.unlockForConfiguration()
            } catch {
                // Ignore focus lock errors
            }
        }
    }

    @objc private func capturePhoto() {
        let settings = AVCapturePhotoSettings()
        if flashEnabled,
           photoOutput.supportedFlashModes.contains(.on) {
            settings.flashMode = .on
        } else if photoOutput.supportedFlashModes.contains(.off) {
            settings.flashMode = .off
        }
        shutterButton.isEnabled = false
        photoOutput.capturePhoto(with: settings, delegate: self)
    }

    @objc private func retakePhoto() {
        capturedImage = nil
        capturedImageView.image = nil
        capturedImageView.isHidden = true
        previewControlsStack.isHidden = true
        liveControlsStack.isHidden = false
        shutterButton.isEnabled = true
        statusLabel.text = "Arahkan kamera ke kertas jawaban, ketuk layar untuk fokus"
    }

    @objc private func confirmPhoto() {
        guard let image = capturedImage else { return }
        usePhotoButton.isEnabled = false
        statusLabel.text = "Mengompresi & mengirim foto..."

        DispatchQueue.global(qos: .userInitiated).async { [weak self] in
            guard let self else { return }
            let resized = self.scaleDownIfNeeded(image, maxEdge: Self.maxEdgePx)
            guard let jpegData = resized.jpegData(compressionQuality: Self.jpegQuality) else {
                DispatchQueue.main.async {
                    self.usePhotoButton.isEnabled = true
                    self.dismiss(animated: true) {
                        self.onCameraError(self.questionId, "Gagal mengompresi foto jawaban.")
                    }
                }
                return
            }

            let base64 = jpegData.base64EncodedString()
            let fileName = "kamera_jawaban_\(Int(Date().timeIntervalSince1970 * 1000)).jpg"

            DispatchQueue.main.async {
                self.usePhotoButton.isEnabled = true
                self.dismiss(animated: true) {
                    self.onPhotoCaptured(self.questionId, self.replaceAttachmentId, base64, fileName)
                }
            }
        }
    }

    private func scaleDownIfNeeded(_ image: UIImage, maxEdge: CGFloat) -> UIImage {
        let size = image.size
        let maxSide = max(size.width, size.height)
        let ratio = maxSide > maxEdge ? (maxEdge / maxSide) : 1.0
        let targetSize = CGSize(
            width: max(1, (size.width * ratio).rounded()),
            height: max(1, (size.height * ratio).rounded())
        )

        let format = UIGraphicsImageRendererFormat.default()
        format.scale = 1.0
        format.opaque = true
        return UIGraphicsImageRenderer(size: targetSize, format: format).image { _ in
            UIColor.white.setFill()
            UIRectFill(CGRect(origin: .zero, size: targetSize))
            image.draw(in: CGRect(origin: .zero, size: targetSize))
        }
    }

    private enum SelfConst {
        static let maxEdgePx: CGFloat = 1600
        static let jpegQuality: CGFloat = 0.80
    }

    private static let maxEdgePx = SelfConst.maxEdgePx
    private static let jpegQuality = SelfConst.jpegQuality
}

extension InAppCameraViewController: AVCapturePhotoCaptureDelegate {
    func photoOutput(
        _ output: AVCapturePhotoOutput,
        didFinishProcessingPhoto photo: AVCapturePhoto,
        error: Error?
    ) {
        DispatchQueue.main.async { [weak self] in
            guard let self else { return }
            self.shutterButton.isEnabled = true
            if let error {
                self.statusLabel.text = "Gagal mengambil foto: \(error.localizedDescription)"
                return
            }
            guard let data = photo.fileDataRepresentation(),
                  let image = UIImage(data: data) else {
                self.statusLabel.text = "Data foto tidak valid, coba jepret ulang."
                return
            }

            self.capturedImage = image
            self.capturedImageView.image = image
            self.capturedImageView.isHidden = false
            self.liveControlsStack.isHidden = true
            self.previewControlsStack.isHidden = false
            self.statusLabel.text = "Periksa foto: pastikan tulisan terbaca jelas"
        }
    }
}

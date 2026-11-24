import Foundation

#if canImport(UIKit)
import UIKit
#endif

#if os(iOS) && canImport(AutomaticAssessmentConfiguration)
import AutomaticAssessmentConfiguration
#endif

/// Handles route-aware screenshot protection, Proctor Exit PIN cooldown,
/// clipboard sanitation, and Apple Automatic Assessment Configuration (AAC).
public final class ExamLockController: NSObject {

    public static let maxAttemptsBeforeCooldown = 3
    public static let cooldownSeconds = 5

    private var failedExitAttempts = 0
    private var exitLockoutUntil: Date?

    #if os(iOS) && canImport(AutomaticAssessmentConfiguration)
    private var assessmentSession: AEAssessmentSession?
    public var onAssessmentStateChanged: ((_ active: Bool, _ errorMessage: String?) -> Void)?
    #endif

    // MARK: - Route Classification (Shared Rules with Android)

    public func isCompletionPath(_ path: String) -> Bool {
        path.range(
            of: "^/siswa/ujian/\\d+/hasil/?$",
            options: [.regularExpression, .caseInsensitive]
        ) != nil
    }

    public func isActiveExamPath(_ path: String) -> Bool {
        path.range(
            of: "^/siswa/ujian/\\d+/(kerjakan|token)/?$",
            options: [.regularExpression, .caseInsensitive]
        ) != nil
    }

    public func isCompletionResultRoute(_ urlString: String?, config: SchoolConfigManager) -> Bool {
        guard config.isHostAllowed(urlString),
              let raw = urlString,
              let path = URL(string: raw)?.path else {
            return false
        }
        return isCompletionPath(path)
    }

    public func isActiveExamRoute(_ urlString: String?, config: SchoolConfigManager) -> Bool {
        guard config.isHostAllowed(urlString),
              let raw = urlString,
              let path = URL(string: raw)?.path else {
            return false
        }
        return isActiveExamPath(path)
    }

    public func shouldAllowScreenshot(
        forUrl urlString: String?,
        config: SchoolConfigManager,
        inAppCameraOpen: Bool
    ) -> Bool {
        !inAppCameraOpen && isCompletionResultRoute(urlString, config: config)
    }

    // MARK: - Exit PIN Cooldown

    public func remainingExitLockoutSeconds(now: Date = Date()) -> Int {
        guard let until = exitLockoutUntil else { return 0 }
        let diff = until.timeIntervalSince(now)
        if diff <= 0 { return 0 }
        return max(1, Int(ceil(diff)))
    }

    @discardableResult
    public func recordExitPinResult(success: Bool, now: Date = Date()) -> Int {
        if success {
            failedExitAttempts = 0
            exitLockoutUntil = nil
            return 0
        }
        failedExitAttempts += 1
        if failedExitAttempts >= Self.maxAttemptsBeforeCooldown {
            failedExitAttempts = 0
            exitLockoutUntil = now.addingTimeInterval(TimeInterval(Self.cooldownSeconds))
            return Self.cooldownSeconds
        }
        return 0
    }

    #if canImport(UIKit)
    public func clearClipboard() {
        UIPasteboard.general.items = []
    }

    public func isGuidedAccessActive() -> Bool {
        UIAccessibility.isGuidedAccessEnabled
    }

    public func isScreenBeingCaptured() -> Bool {
        UIScreen.main.isCaptured
    }
    #endif

    // MARK: - Automatic Assessment Session (iOS/iPadOS Lockdown)

    public func beginAssessmentLockdown() {
        #if os(iOS) && canImport(AutomaticAssessmentConfiguration)
        if assessmentSession?.isActive == true { return }
        let config = AEAssessmentConfiguration()
        let session = AEAssessmentSession(configuration: config)
        session.delegate = self
        assessmentSession = session
        session.begin()
        #endif
    }

    public func endAssessmentLockdown() {
        #if os(iOS) && canImport(AutomaticAssessmentConfiguration)
        if assessmentSession?.isActive == true {
            assessmentSession?.end()
        }
        assessmentSession = nil
        #endif
    }

    public func isAssessmentSessionActive() -> Bool {
        #if os(iOS) && canImport(AutomaticAssessmentConfiguration)
        return assessmentSession?.isActive == true
        #else
        return false
        #endif
    }
}

#if os(iOS) && canImport(AutomaticAssessmentConfiguration)
extension ExamLockController: AEAssessmentSessionDelegate {
    public func assessmentSessionDidBegin(_ session: AEAssessmentSession) {
        onAssessmentStateChanged?(true, nil)
    }

    public func assessmentSession(_ session: AEAssessmentSession, failedToBeginWithError error: Error) {
        // Occurs safely when running without Apple's Assessment entitlement during development;
        // Guided Access & in-app guards continue operating normally.
        onAssessmentStateChanged?(false, error.localizedDescription)
    }

    public func assessmentSession(_ session: AEAssessmentSession, wasInterruptedWithError error: Error) {
        onAssessmentStateChanged?(false, error.localizedDescription)
    }

    public func assessmentSessionDidEnd(_ session: AEAssessmentSession) {
        onAssessmentStateChanged?(false, nil)
    }
}
#endif

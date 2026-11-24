import XCTest
@testable import SmartCBTCore

final class ExamLockAndConfigTests: XCTestCase {

    func testCompletionResultPathAllowsScreenshotWhileExamWorkBlocksIt() {
        let lock = ExamLockController()
        let config = SchoolConfigManager(keychainAccountPrefix: "test")

        XCTAssertTrue(lock.isCompletionPath("/siswa/ujian/1120/hasil"))
        XCTAssertTrue(lock.isCompletionPath("/siswa/ujian/42/hasil/"))
        XCTAssertFalse(lock.isCompletionPath("/siswa/ujian/1120/kerjakan"))
        XCTAssertFalse(lock.isCompletionPath("/siswa/dashboard"))

        XCTAssertTrue(lock.isActiveExamPath("/siswa/ujian/1120/kerjakan"))
        XCTAssertTrue(lock.isActiveExamPath("/siswa/ujian/1120/token"))
        XCTAssertFalse(lock.isActiveExamPath("/siswa/ujian/1120/hasil"))

        XCTAssertTrue(
            lock.shouldAllowScreenshot(
                forUrl: "https://ujian.smpaickkmb.sch.id/siswa/ujian/1120/hasil",
                config: config,
                inAppCameraOpen: false
            )
        )
        XCTAssertFalse(
            lock.shouldAllowScreenshot(
                forUrl: "https://ujian.smpaickkmb.sch.id/siswa/ujian/1120/hasil",
                config: config,
                inAppCameraOpen: true
            )
        )
        XCTAssertFalse(
            lock.shouldAllowScreenshot(
                forUrl: "https://ujian.smpaickkmb.sch.id/siswa/ujian/1120/kerjakan",
                config: config,
                inAppCameraOpen: false
            )
        )
    }

    func testDomainWhitelistAndNormalizationWorkForDefaultAndOverrideDomains() {
        let config = SchoolConfigManager(keychainAccountPrefix: "test")
        XCTAssertEqual(config.getBaseUrl(), "https://ujian.smpaickkmb.sch.id")

        XCTAssertEqual(
            SchoolConfigManager.normalizeUrl("ujian.sekolahbaru.sch.id/"),
            "https://ujian.sekolahbaru.sch.id"
        )
        XCTAssertEqual(
            SchoolConfigManager.normalizeUrl("http://192.168.1.50:8001/"),
            "http://192.168.1.50:8001"
        )

        let allowed: Set<String> = ["ujian.smpaickkmb.sch.id"]
        XCTAssertTrue(
            SchoolConfigManager.isUrlHostAllowed(
                "https://ujian.smpaickkmb.sch.id/siswa/ujian/1/kerjakan",
                allowedHosts: allowed
            )
        )
        XCTAssertFalse(SchoolConfigManager.isUrlHostAllowed("https://google.com", allowedHosts: allowed))
        XCTAssertFalse(SchoolConfigManager.isUrlHostAllowed("tel:08123456789", allowedHosts: allowed))
    }

    func testProctorExitPinLockoutTriggersAfterThreeFailures() {
        let lock = ExamLockController()
        let baseDate = Date(timeIntervalSince1970: 1_700_000_000)

        XCTAssertEqual(lock.recordExitPinResult(success: false, now: baseDate), 0)
        XCTAssertEqual(lock.recordExitPinResult(success: false, now: baseDate), 0)
        XCTAssertEqual(lock.recordExitPinResult(success: false, now: baseDate), 5)
        XCTAssertGreaterThan(lock.remainingExitLockoutSeconds(now: baseDate.addingTimeInterval(1)), 0)
        XCTAssertEqual(lock.remainingExitLockoutSeconds(now: baseDate.addingTimeInterval(6)), 0)

        XCTAssertEqual(lock.recordExitPinResult(success: true, now: baseDate), 0)
        XCTAssertEqual(lock.remainingExitLockoutSeconds(now: baseDate), 0)
    }

    func testDefaultPinsMatchExpectedSha256Hashes() {
        let config = SchoolConfigManager(keychainAccountPrefix: "test")
        XCTAssertTrue(config.verifyExitPin("123456"))
        XCTAssertFalse(config.verifyExitPin("000000"))
        XCTAssertTrue(config.verifyAdminPin("998877"))
        XCTAssertFalse(config.verifyAdminPin("111111"))
    }
}

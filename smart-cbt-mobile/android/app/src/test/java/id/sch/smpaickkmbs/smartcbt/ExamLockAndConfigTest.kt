package id.sch.smpaickkmbs.smartcbt

import id.sch.smpaickkmbs.smartcbt.config.SchoolConfigManager
import id.sch.smpaickkmbs.smartcbt.security.ExamLockController
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ExamLockAndConfigTest {

    @Test
    fun completionResultPathAllowsScreenshotWhileExamWorkBlocksIt() {
        val lock = ExamLockController()

        assertTrue(lock.isCompletionPath("/siswa/ujian/1120/hasil"))
        assertTrue(lock.isCompletionPath("/siswa/ujian/42/hasil/"))
        assertFalse(lock.isCompletionPath("/siswa/ujian/1120/kerjakan"))
        assertFalse(lock.isCompletionPath("/siswa/dashboard"))

        assertTrue(lock.isActiveExamPath("/siswa/ujian/1120/kerjakan"))
        assertTrue(lock.isActiveExamPath("/siswa/ujian/1120/token"))
        assertFalse(lock.isActiveExamPath("/siswa/ujian/1120/hasil"))
    }

    @Test
    fun domainWhitelistAndNormalizationWorkForDefaultAndOverrideDomains() {
        assertEquals("https://ujian.smpaickkmb.sch.id", BuildConfig.DEFAULT_BASE_URL)
        assertEquals(
            "https://ujian.sekolahbaru.sch.id",
            SchoolConfigManager.normalizeUrl("ujian.sekolahbaru.sch.id/", BuildConfig.DEFAULT_BASE_URL)
        )
        assertEquals(
            "http://192.168.1.50:8001",
            SchoolConfigManager.normalizeUrl("http://192.168.1.50:8001/", BuildConfig.DEFAULT_BASE_URL)
        )

        val allowed = setOf("ujian.smpaickkmb.sch.id")
        assertTrue(SchoolConfigManager.isUrlHostAllowed("https://ujian.smpaickkmb.sch.id/siswa/ujian/1/kerjakan", allowed))
        assertFalse(SchoolConfigManager.isUrlHostAllowed("https://google.com", allowed))
        assertFalse(SchoolConfigManager.isUrlHostAllowed("intent://scan/#Intent", allowed))
    }

    @Test
    fun proctorExitPinLockoutTriggersAfterThreeFailures() {
        val lock = ExamLockController()
        assertEquals(0, lock.recordExitPinResult(false))
        assertEquals(0, lock.recordExitPinResult(false))
        val cooldown = lock.recordExitPinResult(false)
        assertEquals(5, cooldown)
        assertEquals(0, lock.recordExitPinResult(true))
    }

    @Test
    fun buildConfigHasHashedPinsInsteadOfPlaintext() {
        assertEquals(64, BuildConfig.DEFAULT_EXIT_PIN_HASH.length)
        assertEquals(64, BuildConfig.DEFAULT_ADMIN_PIN_HASH.length)
        assertEquals(SchoolConfigManager.computeSha256Hex("123456"), BuildConfig.DEFAULT_EXIT_PIN_HASH)
        assertEquals(SchoolConfigManager.computeSha256Hex("998877"), BuildConfig.DEFAULT_ADMIN_PIN_HASH)
    }
}

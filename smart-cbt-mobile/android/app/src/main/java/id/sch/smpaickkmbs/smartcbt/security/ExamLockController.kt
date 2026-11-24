package id.sch.smpaickkmbs.smartcbt.security

import android.app.Activity
import android.app.ActivityManager
import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.net.Uri
import android.os.Build
import android.os.SystemClock
import android.view.WindowManager
import id.sch.smpaickkmbs.smartcbt.config.SchoolConfigManager
import kotlin.math.ceil

/**
 * Handles Kiosk/LockTask pinning, route-aware FLAG_SECURE screenshot policy,
 * clipboard sanitation, and brute-force cooldown on Proctor Exit PIN attempts.
 */
class ExamLockController {

    private val resultRouteRegex = Regex("^/siswa/ujian/\\d+/hasil/?$", RegexOption.IGNORE_CASE)
    private val activeExamRouteRegex = Regex("^/siswa/ujian/\\d+/(kerjakan|token)/?$", RegexOption.IGNORE_CASE)

    private var failedExitAttempts = 0
    private var exitLockoutUntilElapsedMs = 0L

    fun requestLockTask(activity: Activity) {
        try {
            if (!isLockTaskActive(activity)) {
                activity.startLockTask()
            }
        } catch (_: Exception) {
            // Some budget OEM ROMs disable screen pinning in Settings; handled via focus/back guards.
        }
    }

    fun releaseLockTask(activity: Activity) {
        try {
            if (isLockTaskActive(activity)) {
                activity.stopLockTask()
            }
        } catch (_: Exception) {
            // Safe no-op if already unpinned.
        }
    }

    fun isLockTaskActive(activity: Activity): Boolean {
        val am = activity.getSystemService(Context.ACTIVITY_SERVICE) as? ActivityManager ?: return false
        return am.lockTaskModeState != ActivityManager.LOCK_TASK_MODE_NONE
    }

    fun isCompletionResultRoute(url: String?, config: SchoolConfigManager): Boolean {
        if (url.isNullOrBlank() || !config.isHostAllowed(url)) return false
        return isCompletionPath(extractPath(url))
    }

    fun isActiveExamRoute(url: String?, config: SchoolConfigManager): Boolean {
        if (url.isNullOrBlank() || !config.isHostAllowed(url)) return false
        return isActiveExamPath(extractPath(url))
    }

    fun isCompletionPath(path: String): Boolean = resultRouteRegex.matches(path)

    fun isActiveExamPath(path: String): Boolean = activeExamRouteRegex.matches(path)

    private fun extractPath(url: String): String {
        return try {
            java.net.URI(url).path.orEmpty()
        } catch (_: Exception) {
            try {
                Uri.parse(url).path.orEmpty()
            } catch (_: Exception) {
                ""
            }
        }
    }

    /**
     * Applies FLAG_SECURE everywhere EXCEPT on /siswa/ujian/{id}/hasil
     * when the in-app camera overlay is closed, allowing completion receipt screenshots.
     *
     * @return true if screenshot is currently allowed, false if blocked.
     */
    fun applyWindowSecurityForUrl(
        activity: Activity,
        url: String?,
        config: SchoolConfigManager,
        inAppCameraOpen: Boolean
    ): Boolean {
        val allowScreenshot = !inAppCameraOpen && isCompletionResultRoute(url, config)
        if (allowScreenshot) {
            activity.window.clearFlags(WindowManager.LayoutParams.FLAG_SECURE)
        } else {
            activity.window.setFlags(
                WindowManager.LayoutParams.FLAG_SECURE,
                WindowManager.LayoutParams.FLAG_SECURE
            )
        }
        return allowScreenshot
    }

    fun clearClipboard(context: Context) {
        try {
            val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as? ClipboardManager ?: return
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                clipboard.clearPrimaryClip()
            } else {
                clipboard.setPrimaryClip(ClipData.newPlainText("", ""))
            }
        } catch (_: Exception) {
            // Ignore clipboard errors on restricted profiles.
        }
    }

    fun remainingExitLockoutSeconds(): Int {
        val now = SystemClock.elapsedRealtime()
        if (now >= exitLockoutUntilElapsedMs) return 0
        return ceil((exitLockoutUntilElapsedMs - now) / 1000.0).toInt().coerceAtLeast(1)
    }

    fun recordExitPinResult(success: Boolean): Int {
        if (success) {
            failedExitAttempts = 0
            exitLockoutUntilElapsedMs = 0L
            return 0
        }
        failedExitAttempts++
        if (failedExitAttempts >= MAX_ATTEMPTS_BEFORE_COOLDOWN) {
            exitLockoutUntilElapsedMs = SystemClock.elapsedRealtime() + COOLDOWN_MILLIS
            failedExitAttempts = 0
            return (COOLDOWN_MILLIS / 1000L).toInt()
        }
        return 0
    }

    companion object {
        private const val MAX_ATTEMPTS_BEFORE_COOLDOWN = 3
        private const val COOLDOWN_MILLIS = 5_000L
    }
}

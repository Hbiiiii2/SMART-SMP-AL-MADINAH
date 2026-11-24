package id.sch.smpaickkmbs.smartcbt.webview

import android.os.Handler
import android.os.Looper
import android.webkit.JavascriptInterface
import id.sch.smpaickkmbs.smartcbt.BuildConfig

/**
 * Shared JavaScript Bridge contract exposed as window.SmartCbtNative.
 * Matches the PRD contract shared between Android (Phase 1) and iOS (Phase 2).
 */
class SmartCbtJsBridge(
    private val onOpenEssayCamera: (questionId: String, replaceAttachmentId: String) -> Unit,
    private val onRouteChangedCallback: (urlOrPath: String) -> Unit
) {
    private val mainHandler = Handler(Looper.getMainLooper())

    @JavascriptInterface
    fun openEssayCamera(questionId: String?, replaceAttachmentId: String?) {
        val qId = questionId?.trim().orEmpty()
        val replaceId = replaceAttachmentId?.trim().orEmpty()
        if (qId.isEmpty()) return
        mainHandler.post { onOpenEssayCamera(qId, replaceId) }
    }

    @JavascriptInterface
    fun onRouteChanged(urlOrPath: String?) {
        val route = urlOrPath?.trim().orEmpty()
        if (route.isEmpty()) return
        mainHandler.post { onRouteChangedCallback(route) }
    }

    @JavascriptInterface
    fun getAppVersion(): String = "android-${BuildConfig.VERSION_NAME}"

    @JavascriptInterface
    fun isNativeExambro(): Boolean = true
}

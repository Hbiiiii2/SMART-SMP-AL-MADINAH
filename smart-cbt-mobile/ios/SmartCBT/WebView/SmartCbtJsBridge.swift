import Foundation
import WebKit

/// Implements the shared window.SmartCbtNative JS Bridge contract
/// used by smart-cbt/resources/views/siswa/exams/work.blade.php.
final class SmartCbtJsBridge: NSObject, WKScriptMessageHandler {

    static let handlerName = "SmartCbtNativeBridge"

    private let appVersion: String
    private let onOpenEssayCamera: (_ questionId: String, _ replaceAttachmentId: String) -> Void
    private let onRouteChanged: (_ urlOrPath: String) -> Void

    init(
        appVersion: String,
        onOpenEssayCamera: @escaping (_ questionId: String, _ replaceAttachmentId: String) -> Void,
        onRouteChanged: @escaping (_ urlOrPath: String) -> Void
    ) {
        self.appVersion = appVersion
        self.onOpenEssayCamera = onOpenEssayCamera
        self.onRouteChanged = onRouteChanged
    }

    func makeBootstrapUserScript() -> WKUserScript {
        let escapedVersion = appVersion
            .replacingOccurrences(of: "\\", with: "\\\\")
            .replacingOccurrences(of: "\"", with: "\\\"")

        let source = """
        (function() {
            if (!window.SmartCbtNative) {
                window.SmartCbtNative = {
                    openEssayCamera: function(questionId, replaceAttachmentId) {
                        window.webkit.messageHandlers.\(Self.handlerName).postMessage({
                            action: 'openEssayCamera',
                            questionId: String(questionId || ''),
                            replaceAttachmentId: String(replaceAttachmentId || '')
                        });
                    },
                    onRouteChanged: function(urlOrPath) {
                        window.webkit.messageHandlers.\(Self.handlerName).postMessage({
                            action: 'onRouteChanged',
                            urlOrPath: String(urlOrPath || window.location.href || '')
                        });
                    },
                    getAppVersion: function() {
                        return "ios-\(escapedVersion)";
                    },
                    isNativeExambro: function() {
                        return true;
                    }
                };
            }

            if (!window.__smartCbtRouteObserverInstalled) {
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
            }
        })();
        """

        return WKUserScript(source: source, injectionTime: .atDocumentStart, forMainFrameOnly: false)
    }

    func makeCalloutDisableUserScript() -> WKUserScript {
        let source = """
        (function() {
            var style = document.createElement('style');
            style.textContent = `
                body, p, div, span, h1, h2, h3, label {
                    -webkit-touch-callout: none;
                    -webkit-user-select: none;
                    user-select: none;
                }
                textarea, input, [contenteditable="true"] {
                    -webkit-touch-callout: default !important;
                    -webkit-user-select: text !important;
                    user-select: text !important;
                }
            `;
            document.addEventListener('DOMContentLoaded', function() {
                document.head && document.head.appendChild(style);
            });
        })();
        """
        return WKUserScript(source: source, injectionTime: .atDocumentStart, forMainFrameOnly: true)
    }

    func userContentController(_ userContentController: WKUserContentController, didReceive message: WKScriptMessage) {
        guard message.name == Self.handlerName,
              let body = message.body as? [String: Any],
              let action = body["action"] as? String else {
            return
        }

        switch action {
        case "openEssayCamera":
            let qId = (body["questionId"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            let replaceId = (body["replaceAttachmentId"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            if !qId.isEmpty {
                DispatchQueue.main.async {
                    self.onOpenEssayCamera(qId, replaceId)
                }
            }
        case "onRouteChanged":
            let url = (body["urlOrPath"] as? String)?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            if !url.isEmpty {
                DispatchQueue.main.async {
                    self.onRouteChanged(url)
                }
            }
        default:
            break
        }
    }
}

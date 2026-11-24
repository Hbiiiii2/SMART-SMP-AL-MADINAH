-dontobfuscate
-keep class id.sch.smpaickkmbs.smartcbt.** { *; }
-keepattributes JavascriptInterface
-keepclassmembers class id.sch.smpaickkmbs.smartcbt.webview.SmartCbtJsBridge {
    @android.webkit.JavascriptInterface <methods>;
}
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}

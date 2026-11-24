import CryptoKit
import Foundation
import Security

/// Manages build-time school configuration (Info.plist / xcconfig) and
/// runtime overrides persisted securely in the iOS Keychain.
public final class SchoolConfigManager {

    private enum Keys {
        static let service = "id.sch.smpaickkmb.smartcbt.config"
        static let baseUrl = "override_base_url"
        static let allowedHosts = "override_allowed_hosts"
        static let exitPinHash = "override_exit_pin_hash"
    }

    public static let defaultFallbackBaseUrl = "https://ujian.smpaickkmb.sch.id"
    public static let defaultFallbackAllowedHosts = "ujian.smpaickkmb.sch.id"
    public static let defaultFallbackExitPinHash =
        "8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92"
    public static let defaultFallbackAdminPinHash =
        "c7e9988d0ac79f466bf33b7bc5a8e31ebad2fe1f6e17c78919f5717acce44230"

    private static let trustedStaticCdnHosts: Set<String> = [
        "cdnjs.cloudflare.com",
        "unpkg.com",
        "fonts.googleapis.com",
        "fonts.gstatic.com",
        "cdn.jsdelivr.net"
    ]

    private let bundle: Bundle
    private let keychainAccountPrefix: String

    public init(bundle: Bundle = .main, keychainAccountPrefix: String = "") {
        self.bundle = bundle
        self.keychainAccountPrefix = keychainAccountPrefix
    }

    public func getAppDisplayName() -> String {
        infoString("SmartCbtAppDisplayName") ?? "SMART-CBT"
    }

    public func getAppVersion() -> String {
        infoString("CFBundleShortVersionString") ?? "1.0.0"
    }

    public func isRuntimeDomainSwitchEnabled() -> Bool {
        let raw = infoString("SmartCbtEnableRuntimeDomainSwitch")?.lowercased() ?? "yes"
        return raw == "yes" || raw == "true" || raw == "1"
    }

    public func isGalleryAllowedDuringExam() -> Bool {
        let raw = infoString("SmartCbtAllowGalleryDuringExam")?.lowercased() ?? "yes"
        return raw == "yes" || raw == "true" || raw == "1"
    }

    public func getBaseUrl() -> String {
        let overrideUrl = isRuntimeDomainSwitchEnabled() ? readKeychainString(Keys.baseUrl) : nil
        let raw = (overrideUrl?.isEmpty == false ? overrideUrl : infoString("SmartCbtDefaultBaseUrl"))
            ?? Self.defaultFallbackBaseUrl
        return Self.normalizeUrl(raw, fallbackUrl: Self.defaultFallbackBaseUrl)
    }

    public func getAllowedHosts() -> Set<String> {
        var hosts = Set<String>()
        if let activeHost = Self.extractHost(getBaseUrl()) {
            hosts.insert(activeHost)
        }

        let overrideHosts = isRuntimeDomainSwitchEnabled() ? readKeychainString(Keys.allowedHosts) : nil
        let rawList = (overrideHosts?.isEmpty == false ? overrideHosts : infoString("SmartCbtAllowedHosts"))
            ?? Self.defaultFallbackAllowedHosts

        for entry in rawList.split(separator: ",") {
            let trimmed = entry.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
            if !trimmed.isEmpty {
                hosts.insert(trimmed)
            }
        }
        return hosts
    }

    public func isHostAllowed(_ urlString: String?) -> Bool {
        Self.isUrlHostAllowed(urlString, allowedHosts: getAllowedHosts())
    }

    public func isSubresourceCdnAllowed(_ urlString: String?) -> Bool {
        guard let host = Self.extractHost(urlString) else { return false }
        return Self.trustedStaticCdnHosts.contains(host)
    }

    public func isUsingCustomOverride() -> Bool {
        guard isRuntimeDomainSwitchEnabled() else { return false }
        guard let val = readKeychainString(Keys.baseUrl) else { return false }
        return !val.isEmpty
    }

    public func verifyExitPin(_ input: String) -> Bool {
        let normalized = input.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !normalized.isEmpty else { return false }

        let expectedHash = readKeychainString(Keys.exitPinHash)
            ?? infoString("SmartCbtDefaultExitPinHash")
            ?? Self.defaultFallbackExitPinHash

        return Self.constantTimeEquals(Self.computeSha256Hex(normalized), expectedHash.lowercased())
    }

    public func verifyAdminPin(_ input: String) -> Bool {
        let normalized = input.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !normalized.isEmpty else { return false }

        let expectedHash = infoString("SmartCbtDefaultAdminPinHash")
            ?? Self.defaultFallbackAdminPinHash

        return Self.constantTimeEquals(Self.computeSha256Hex(normalized), expectedHash.lowercased())
    }

    public func updateRuntimeConfig(newBaseUrl: String, newExitPin: String?) {
        let normalizedUrl = Self.normalizeUrl(newBaseUrl, fallbackUrl: Self.defaultFallbackBaseUrl)
        writeKeychainString(normalizedUrl, forKey: Keys.baseUrl)

        if let host = Self.extractHost(normalizedUrl) {
            writeKeychainString(host, forKey: Keys.allowedHosts)
        }

        if let pin = newExitPin?.trimmingCharacters(in: .whitespacesAndNewlines), !pin.isEmpty {
            writeKeychainString(Self.computeSha256Hex(pin), forKey: Keys.exitPinHash)
        }
    }

    public func resetToDefaults() {
        deleteKeychainItem(Keys.baseUrl)
        deleteKeychainItem(Keys.allowedHosts)
        deleteKeychainItem(Keys.exitPinHash)
    }

    // MARK: - Pure Helpers (Unit Testable)

    public static func normalizeUrl(_ rawUrl: String, fallbackUrl: String = defaultFallbackBaseUrl) -> String {
        let trimmed = rawUrl
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .trimmingCharacters(in: CharacterSet(charactersIn: "/"))

        if trimmed.isEmpty {
            return fallbackUrl.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        }

        let lower = trimmed.lowercased()
        if lower.hasPrefix("http://") || lower.hasPrefix("https://") {
            return trimmed
        }
        return "https://\(trimmed)"
    }

    public static func extractHost(_ urlString: String?) -> String? {
        guard let raw = urlString?.trimmingCharacters(in: .whitespacesAndNewlines),
              !raw.isEmpty,
              let url = URL(string: raw),
              let host = url.host?.lowercased(),
              !host.isEmpty else {
            return nil
        }
        return host
    }

    public static func isUrlHostAllowed(_ urlString: String?, allowedHosts: Set<String>) -> Bool {
        guard let raw = urlString?.trimmingCharacters(in: .whitespacesAndNewlines), !raw.isEmpty else {
            return false
        }
        if raw == "about:blank" { return true }
        guard let url = URL(string: raw),
              let scheme = url.scheme?.lowercased(),
              scheme == "https" || scheme == "http",
              let host = url.host?.lowercased() else {
            return false
        }
        return allowedHosts.contains(host)
    }

    public static func computeSha256Hex(_ value: String) -> String {
        let digest = SHA256.hash(data: Data(value.utf8))
        return digest.map { String(format: "%02x", $0) }.joined()
    }

    public static func constantTimeEquals(_ a: String, _ b: String) -> Bool {
        let bytesA = Array(a.utf8)
        let bytesB = Array(b.utf8)
        guard bytesA.count == bytesB.count else { return false }
        var diff: UInt8 = 0
        for i in 0..<bytesA.count {
            diff |= bytesA[i] ^ bytesB[i]
        }
        return diff == 0
    }

    // MARK: - Info.plist & Keychain

    private func infoString(_ key: String) -> String? {
        guard let val = bundle.object(forInfoDictionaryKey: key) as? String else {
            return nil
        }
        let cleaned = val.trimmingCharacters(in: .whitespacesAndNewlines)
        if cleaned.isEmpty || cleaned.hasPrefix("$(") {
            return nil
        }
        return cleaned
    }

    private func scopedKey(_ key: String) -> String {
        keychainAccountPrefix.isEmpty ? key : "\(keychainAccountPrefix).\(key)"
    }

    private func writeKeychainString(_ value: String, forKey key: String) {
        let account = scopedKey(key)
        guard let data = value.data(using: .utf8) else { return }

        let deleteQuery: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: Keys.service,
            kSecAttrAccount as String: account
        ]
        SecItemDelete(deleteQuery as CFDictionary)

        let addQuery: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: Keys.service,
            kSecAttrAccount as String: account,
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        ]
        SecItemAdd(addQuery as CFDictionary, nil)
    }

    private func readKeychainString(_ key: String) -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: Keys.service,
            kSecAttrAccount as String: scopedKey(key),
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]

        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)
        guard status == errSecSuccess, let data = result as? Data else {
            return nil
        }
        return String(data: data, encoding: .utf8)
    }

    private func deleteKeychainItem(_ key: String) {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: Keys.service,
            kSecAttrAccount as String: scopedKey(key)
        ]
        SecItemDelete(query as CFDictionary)
    }
}

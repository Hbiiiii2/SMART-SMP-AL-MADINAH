package id.sch.smpaickkmbs.smartcbt.config

import android.content.Context
import android.net.Uri
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import id.sch.smpaickkmbs.smartcbt.BuildConfig
import java.security.KeyStore
import java.security.MessageDigest
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/**
 * Manages build-time school configuration (BuildConfig) and runtime
 * encrypted overrides stored with Android Keystore AES/GCM/NoPadding.
 */
class SchoolConfigManager(context: Context) {

    private val prefs = context.applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    fun getAppDisplayName(): String = BuildConfig.APP_DISPLAY_NAME

    fun isRuntimeDomainSwitchEnabled(): Boolean = BuildConfig.ENABLE_RUNTIME_DOMAIN_SWITCH

    fun isGalleryAllowedDuringExam(): Boolean = BuildConfig.ALLOW_GALLERY_DURING_EXAM

    fun getBaseUrl(): String {
        val override = if (isRuntimeDomainSwitchEnabled()) decrypt(KEY_OVERRIDE_BASE_URL) else null
        val raw = override?.takeIf { it.isNotBlank() } ?: BuildConfig.DEFAULT_BASE_URL
        return normalizeBaseUrl(raw)
    }

    fun getAllowedHosts(): Set<String> {
        val hosts = linkedSetOf<String>()

        Uri.parse(getBaseUrl()).host?.lowercase()?.takeIf { it.isNotBlank() }?.let {
            hosts.add(it)
        }

        val overrideHosts = if (isRuntimeDomainSwitchEnabled()) decrypt(KEY_OVERRIDE_ALLOWED_HOSTS) else null
        val source = overrideHosts?.takeIf { it.isNotBlank() } ?: BuildConfig.ALLOWED_HOSTS
        source.split(',')
            .map { it.trim().lowercase() }
            .filter { it.isNotBlank() }
            .forEach { hosts.add(it) }

        return hosts
    }

    fun isHostAllowed(url: String?): Boolean {
        if (url.isNullOrBlank()) return false
        if (url == "about:blank") return true
        return isUrlHostAllowed(url, getAllowedHosts())
    }

    fun isSubresourceCdnAllowed(url: String?): Boolean {
        if (url.isNullOrBlank()) return false
        val host = extractHost(url) ?: return false
        return TRUSTED_STATIC_CDN_HOSTS.contains(host)
    }

    fun isUsingCustomOverride(): Boolean =
        isRuntimeDomainSwitchEnabled() && !decrypt(KEY_OVERRIDE_BASE_URL).isNullOrBlank()

    fun verifyExitPin(input: String): Boolean {
        val normalized = input.trim()
        if (normalized.isEmpty()) return false
        val expectedHash = decrypt(KEY_OVERRIDE_EXIT_PIN_HASH)?.takeIf { it.isNotBlank() }
            ?: BuildConfig.DEFAULT_EXIT_PIN_HASH
        return constantTimeHexEquals(sha256Hex(normalized), expectedHash)
    }

    fun verifyAdminPin(input: String): Boolean {
        val normalized = input.trim()
        if (normalized.isEmpty()) return false
        return constantTimeHexEquals(sha256Hex(normalized), BuildConfig.DEFAULT_ADMIN_PIN_HASH)
    }

    fun updateRuntimeConfig(newBaseUrl: String, newExitPin: String?) {
        val normalizedUrl = normalizeBaseUrl(newBaseUrl)
        val parsedHost = Uri.parse(normalizedUrl).host?.lowercase().orEmpty()
        val editor = prefs.edit()
        editor.putString(KEY_OVERRIDE_BASE_URL, encrypt(normalizedUrl))
        if (parsedHost.isNotBlank()) {
            editor.putString(KEY_OVERRIDE_ALLOWED_HOSTS, encrypt(parsedHost))
        }
        if (!newExitPin.isNullOrBlank()) {
            editor.putString(KEY_OVERRIDE_EXIT_PIN_HASH, encrypt(sha256Hex(newExitPin.trim())))
        }
        editor.apply()
    }

    fun resetToDefaults() {
        prefs.edit()
            .remove(KEY_OVERRIDE_BASE_URL)
            .remove(KEY_OVERRIDE_ALLOWED_HOSTS)
            .remove(KEY_OVERRIDE_EXIT_PIN_HASH)
            .apply()
    }

    fun normalizeBaseUrl(rawUrl: String): String =
        normalizeUrl(rawUrl, BuildConfig.DEFAULT_BASE_URL)

    private fun sha256Hex(value: String): String = computeSha256Hex(value)

    private fun constantTimeHexEquals(a: String, b: String): Boolean {
        return MessageDigest.isEqual(
            a.toByteArray(Charsets.UTF_8),
            b.toByteArray(Charsets.UTF_8)
        )
    }

    private fun getOrCreateAesKey(): SecretKey {
        val keyStore = KeyStore.getInstance(ANDROID_KEYSTORE).apply { load(null) }
        val existing = keyStore.getKey(KEYSTORE_ALIAS, null) as? SecretKey
        if (existing != null) return existing

        val generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, ANDROID_KEYSTORE)
        val spec = KeyGenParameterSpec.Builder(
            KEYSTORE_ALIAS,
            KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
        )
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build()
        generator.init(spec)
        return generator.generateKey()
    }

    private fun encrypt(plainText: String): String {
        val cipher = Cipher.getInstance(AES_GCM_TRANSFORMATION)
        cipher.init(Cipher.ENCRYPT_MODE, getOrCreateAesKey())
        val iv = cipher.iv
        val cipherBytes = cipher.doFinal(plainText.toByteArray(Charsets.UTF_8))
        val combined = ByteArray(iv.size + cipherBytes.size)
        System.arraycopy(iv, 0, combined, 0, iv.size)
        System.arraycopy(cipherBytes, 0, combined, iv.size, cipherBytes.size)
        return Base64.encodeToString(combined, Base64.NO_WRAP)
    }

    private fun decrypt(key: String): String? {
        val encoded = prefs.getString(key, null) ?: return null
        return try {
            val combined = Base64.decode(encoded, Base64.NO_WRAP)
            if (combined.size <= GCM_IV_LENGTH) return null
            val iv = combined.copyOfRange(0, GCM_IV_LENGTH)
            val cipherBytes = combined.copyOfRange(GCM_IV_LENGTH, combined.size)
            val cipher = Cipher.getInstance(AES_GCM_TRANSFORMATION)
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateAesKey(), GCMParameterSpec(128, iv))
            String(cipher.doFinal(cipherBytes), Charsets.UTF_8)
        } catch (_: Exception) {
            null
        }
    }

    companion object {
        private const val PREFS_NAME = "smart_cbt_secure_prefs"
        private const val ANDROID_KEYSTORE = "AndroidKeyStore"
        private const val KEYSTORE_ALIAS = "smart_cbt_config_aes_v1"
        private const val AES_GCM_TRANSFORMATION = "AES/GCM/NoPadding"
        private const val GCM_IV_LENGTH = 12

        private const val KEY_OVERRIDE_BASE_URL = "override_base_url"
        private const val KEY_OVERRIDE_ALLOWED_HOSTS = "override_allowed_hosts"
        private const val KEY_OVERRIDE_EXIT_PIN_HASH = "override_exit_pin_hash"

        private val TRUSTED_STATIC_CDN_HOSTS = setOf(
            "cdnjs.cloudflare.com",
            "unpkg.com",
            "fonts.googleapis.com",
            "fonts.gstatic.com",
            "cdn.jsdelivr.net"
        )

        fun normalizeUrl(rawUrl: String, fallbackUrl: String): String {
            val trimmed = rawUrl.trim().trimEnd('/')
            if (trimmed.isEmpty()) return fallbackUrl.trimEnd('/')
            return if (trimmed.startsWith("http://", ignoreCase = true) ||
                trimmed.startsWith("https://", ignoreCase = true)
            ) {
                trimmed
            } else {
                "https://$trimmed"
            }
        }

        fun extractHost(url: String?): String? {
            if (url.isNullOrBlank()) return null
            return try {
                val javaHost = java.net.URI(url).host?.lowercase()?.takeIf { it.isNotBlank() }
                javaHost ?: Uri.parse(url).host?.lowercase()?.takeIf { it.isNotBlank() }
            } catch (_: Exception) {
                try {
                    Uri.parse(url).host?.lowercase()?.takeIf { it.isNotBlank() }
                } catch (_: Exception) {
                    null
                }
            }
        }

        fun isUrlHostAllowed(url: String?, allowedHosts: Set<String>): Boolean {
            if (url.isNullOrBlank()) return false
            if (url == "about:blank") return true
            val lower = url.trim().lowercase()
            if (!lower.startsWith("https://") && !lower.startsWith("http://")) return false
            val host = extractHost(url) ?: return false
            return allowedHosts.contains(host)
        }

        fun computeSha256Hex(value: String): String {
            val bytes = MessageDigest.getInstance("SHA-256").digest(value.toByteArray(Charsets.UTF_8))
            return bytes.joinToString("") { "%02x".format(it) }
        }
    }
}

import java.security.MessageDigest
import java.util.Properties

plugins {
    id("com.android.application")
}

val schoolProps = Properties().apply {
    val file = rootProject.file("school.properties")
    if (file.exists()) {
        file.inputStream().use { load(it) }
    }
}

val keystoreProps = Properties().apply {
    val file = rootProject.file("keystore.properties")
    if (file.exists()) {
        file.inputStream().use { load(it) }
    }
}

fun prop(key: String, default: String): String =
    (System.getenv("SMARTCBT_$key") ?: schoolProps.getProperty(key) ?: default).trim()

fun sha256Hex(raw: String): String {
    val digest = MessageDigest.getInstance("SHA-256").digest(raw.toByteArray(Charsets.UTF_8))
    return digest.joinToString("") { "%02x".format(it) }
}

fun escapeBuildConfig(value: String): String =
    "\"" + value.replace("\\", "\\\\").replace("\"", "\\\"") + "\""

val appName = prop("APP_NAME", "SMART-CBT")
val appId = prop("APPLICATION_ID", "id.sch.smpaickkmbs.smartcbt")
val defaultBaseUrl = prop("DEFAULT_BASE_URL", "https://ujian.smpaickkmb.sch.id")
val allowedHosts = prop("ALLOWED_HOSTS", "ujian.smpaickkmb.sch.id")
val defaultExitPinHash = sha256Hex(prop("DEFAULT_EXIT_PIN", "123456"))
val defaultAdminPinHash = sha256Hex(prop("DEFAULT_ADMIN_PIN", "998877"))
val enableRuntimeDomainSwitch = prop("ENABLE_RUNTIME_DOMAIN_SWITCH", "true").toBooleanStrictOrNull() ?: true
val allowGalleryDuringExam = prop("ALLOW_GALLERY_DURING_EXAM", "true").toBooleanStrictOrNull() ?: true

android {
    namespace = "id.sch.smpaickkmbs.smartcbt"
    compileSdk = 35
    buildToolsVersion = "36.0.0"

    defaultConfig {
        applicationId = appId
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"

        resValue("string", "app_name", appName)

        buildConfigField("String", "APP_DISPLAY_NAME", escapeBuildConfig(appName))
        buildConfigField("String", "DEFAULT_BASE_URL", escapeBuildConfig(defaultBaseUrl))
        buildConfigField("String", "ALLOWED_HOSTS", escapeBuildConfig(allowedHosts))
        buildConfigField("String", "DEFAULT_EXIT_PIN_HASH", escapeBuildConfig(defaultExitPinHash))
        buildConfigField("String", "DEFAULT_ADMIN_PIN_HASH", escapeBuildConfig(defaultAdminPinHash))
        buildConfigField("boolean", "ENABLE_RUNTIME_DOMAIN_SWITCH", enableRuntimeDomainSwitch.toString())
        buildConfigField("boolean", "ALLOW_GALLERY_DURING_EXAM", allowGalleryDuringExam.toString())
    }

    signingConfigs {
        create("release") {
            val ksPath = System.getenv("SMARTCBT_KEYSTORE_FILE") ?: keystoreProps.getProperty("storeFile")
            val ksPass = System.getenv("SMARTCBT_KEYSTORE_PASSWORD") ?: keystoreProps.getProperty("storePassword")
            val ksAlias = System.getenv("SMARTCBT_KEY_ALIAS") ?: keystoreProps.getProperty("keyAlias")
            val ksKeyPass = System.getenv("SMARTCBT_KEY_PASSWORD") ?: keystoreProps.getProperty("keyPassword")
            if (!ksPath.isNullOrBlank() && !ksPass.isNullOrBlank() && !ksAlias.isNullOrBlank() && !ksKeyPass.isNullOrBlank()) {
                storeFile = rootProject.file(ksPath)
                storePassword = ksPass
                keyAlias = ksAlias
                keyPassword = ksKeyPass
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            val releaseConfig = signingConfigs.getByName("release")
            if (releaseConfig.storeFile?.exists() == true) {
                signingConfig = releaseConfig
            }
        }
        debug {
            isMinifyEnabled = false
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        buildConfig = true
        viewBinding = true
        resValues = true
    }

    testOptions {
        unitTests.isReturnDefaultValues = true
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("com.google.android.material:material:1.12.0")
    implementation("androidx.activity:activity-ktx:1.9.0")
    implementation("androidx.webkit:webkit:1.12.1")
    testImplementation("junit:junit:4.13.2")
}

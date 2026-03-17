plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

val localProperties = java.util.Properties().apply {
    rootProject.file("local.properties").takeIf { it.exists() }?.reader(Charsets.UTF_8)?.use { load(it) }
}
val mapsApiKey = localProperties.getProperty("MAPS_API_KEY", "") ?: project.findProperty("MAPS_API_KEY")?.toString().orEmpty()

android {
    namespace = "com.tajacore.outletmap"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.tajacore.outletmap"
        minSdk = 26
        targetSdk = 34
        versionCode = 1
        versionName = "1.0.0"
        manifestPlaceholders["MAPS_API_KEY"] = mapsApiKey
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }
    buildFeatures {
        viewBinding = true
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.12.0")
    implementation("androidx.appcompat:appcompat:1.6.1")
    implementation("com.google.android.material:material:1.11.0")
    implementation("androidx.constraintlayout:constraintlayout:2.1.4")

    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.6.2")
    implementation("androidx.activity:activity-ktx:1.8.2")

    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    implementation("com.google.android.gms:play-services-maps:18.2.0")
    implementation("com.google.android.gms:play-services-location:21.0.1")

    implementation("androidx.datastore:datastore-preferences:1.0.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
}

# Add project specific ProGuard rules here.
# By default, the flags in this file are appended to flags specified
# in the SDK. You can edit this file to include flags for Retrofit, Gson, etc.
-keepattributes Signature
-keepattributes *Annotation*
-keep class com.tajacore.distribution.data.api.dto.** { *; }
-dontwarn okhttp3.**
-dontwarn retrofit2.**

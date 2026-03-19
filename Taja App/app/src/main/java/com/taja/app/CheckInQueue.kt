package com.taja.app

import android.content.Context
import android.content.SharedPreferences
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.Build
import org.json.JSONArray
import org.json.JSONObject
import java.util.UUID

/**
 * Persists pending check-ins when offline and provides them for sync.
 * Uses SharedPreferences; each item: client_id, outlet_id, lat, lng, notes, photo_base64?, check_in_at (ISO-8601).
 */
class CheckInQueue(context: Context) {

    private val prefs: SharedPreferences = context.applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    fun add(outletId: String, lat: Double, lng: Double, notes: String?, photoBase64: String? = null): String {
        val clientId = UUID.randomUUID().toString()
        val checkInAt = java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", java.util.Locale.US).apply {
            timeZone = java.util.TimeZone.getTimeZone("UTC")
        }.format(java.util.Date())
        val item = JSONObject().apply {
            put(KEY_CLIENT_ID, clientId)
            put(KEY_OUTLET_ID, outletId)
            put(KEY_LAT, lat)
            put(KEY_LNG, lng)
            put(KEY_NOTES, notes ?: "")
            if (!photoBase64.isNullOrBlank()) put(KEY_PHOTO_BASE64, photoBase64)
            put(KEY_CHECK_IN_AT, checkInAt)
        }
        val arr = JSONArray(prefs.getString(KEY_ITEMS, "[]") ?: "[]")
        arr.put(item)
        prefs.edit().putString(KEY_ITEMS, arr.toString()).apply()
        return clientId
    }

    fun getAll(): List<ApiClient.SyncCheckInItem> {
        val json = prefs.getString(KEY_ITEMS, "[]") ?: "[]"
        val arr = JSONArray(json)
        return (0 until arr.length()).map { i ->
            val o = arr.getJSONObject(i)
            ApiClient.SyncCheckInItem(
                clientId = o.getString(KEY_CLIENT_ID),
                outletId = o.getString(KEY_OUTLET_ID),
                lat = o.getDouble(KEY_LAT),
                lng = o.getDouble(KEY_LNG),
                notes = o.optString(KEY_NOTES, "").takeIf { it.isNotBlank() },
                photoBase64 = o.optString(KEY_PHOTO_BASE64, "").takeIf { it.isNotBlank() },
                checkInAt = o.getString(KEY_CHECK_IN_AT)
            )
        }
    }

    fun removeByClientIds(clientIds: Set<String>) {
        if (clientIds.isEmpty()) return
        val arr = JSONArray(prefs.getString(KEY_ITEMS, "[]") ?: "[]")
        val newArr = JSONArray()
        for (i in 0 until arr.length()) {
            val o = arr.getJSONObject(i)
            if (!clientIds.contains(o.optString(KEY_CLIENT_ID, ""))) newArr.put(o)
        }
        prefs.edit().putString(KEY_ITEMS, newArr.toString()).apply()
    }

    fun size(): Int = getAll().size

    companion object {
        private const val PREFS_NAME = "taja_checkin_queue"
        private const val KEY_ITEMS = "items"
        private const val KEY_CLIENT_ID = "client_id"
        private const val KEY_OUTLET_ID = "outlet_id"
        private const val KEY_LAT = "lat"
        private const val KEY_LNG = "lng"
        private const val KEY_NOTES = "notes"
        private const val KEY_PHOTO_BASE64 = "photo_base64"
        private const val KEY_CHECK_IN_AT = "check_in_at"

        @JvmStatic
        fun isNetworkAvailable(context: Context): Boolean {
            val cm = context.applicationContext.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
            return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                val net = cm.activeNetwork ?: return false
                val caps = cm.getNetworkCapabilities(net) ?: return false
                caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
                    caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
            } else {
                @Suppress("DEPRECATION")
                cm.activeNetworkInfo?.isConnected == true
            }
        }
    }
}

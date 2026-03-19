package com.taja.app

import android.content.Context
import android.content.SharedPreferences

/**
 * Stores auth token and optional base URL for API calls.
 */
class SessionManager(context: Context) {

    private val prefs: SharedPreferences = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(value) = prefs.edit().putString(KEY_TOKEN, value).apply()

    var isLoggedIn: Boolean
        get() = !token.isNullOrBlank()
        private set(_) {}

    var userName: String?
        get() = prefs.getString(KEY_USER_NAME, null)
        set(value) = prefs.edit().putString(KEY_USER_NAME, value).apply()

    var branchName: String?
        get() = prefs.getString(KEY_BRANCH_NAME, null)
        set(value) = prefs.edit().putString(KEY_BRANCH_NAME, value).apply()

    fun logout() {
        prefs.edit()
            .remove(KEY_TOKEN)
            .remove(KEY_USER_NAME)
            .remove(KEY_BRANCH_NAME)
            .apply()
    }

    companion object {
        private const val PREFS_NAME = "taja_app_session"
        private const val KEY_TOKEN = "auth_token"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_BRANCH_NAME = "branch_name"
    }
}

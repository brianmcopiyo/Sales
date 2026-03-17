package com.tajacore.distribution.data

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.tajacore.distribution.BuildConfig
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.dataStore: DataStore<androidx.datastore.preferences.core.Preferences> by preferencesDataStore(name = "auth")

class AuthRepository(private val context: Context) {

    companion object {
        private val TOKEN = stringPreferencesKey("token")
    }

    suspend fun saveToken(token: String) {
        context.dataStore.edit { it[TOKEN] = token }
    }

    suspend fun getToken(): String? = context.dataStore.data.map { it[TOKEN] }.first()

    suspend fun clearToken() {
        context.dataStore.edit { it.remove(TOKEN) }
    }

    /** API base URL from app config (BuildConfig.API_BASE_URL). Set via gradle property API_BASE_URL. */
    fun getBaseUrl(): String = BuildConfig.API_BASE_URL
}

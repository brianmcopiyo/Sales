package com.tajacore.outletmap.data

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.dataStore: DataStore<androidx.datastore.preferences.core.Preferences> by preferencesDataStore(name = "auth")

class AuthRepository(private val context: Context) {

    companion object {
        private val TOKEN = stringPreferencesKey("token")
        private val BASE_URL = stringPreferencesKey("base_url")
    }

    suspend fun saveToken(token: String) {
        context.dataStore.edit { it[TOKEN] = token }
    }

    suspend fun getToken(): String? = context.dataStore.data.map { it[TOKEN] }.first()

    suspend fun clearToken() {
        context.dataStore.edit { it.remove(TOKEN) }
    }

    suspend fun saveBaseUrl(url: String) {
        context.dataStore.edit { it[BASE_URL] = url }
    }

    suspend fun getBaseUrl(): String? = context.dataStore.data.map { it[BASE_URL] }.first()
}

package com.tajacore.outletmap.ui.login

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.lifecycle.lifecycleScope
import com.tajacore.outletmap.BuildConfig
import com.tajacore.outletmap.data.AuthRepository
import com.tajacore.outletmap.data.api.RetrofitModule
import com.tajacore.outletmap.data.api.dto.LoginRequest
import com.tajacore.outletmap.ui.map.MapActivity
import com.tajacore.outletmap.ui.theme.TajaCoreOutletMapTheme
import kotlinx.coroutines.launch

class LoginActivity : ComponentActivity() {

    private lateinit var authRepo: AuthRepository

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        authRepo = AuthRepository(this)

        setContent {
            TajaCoreOutletMapTheme {
                LoginScreen(
                    onLoginSuccess = {
                        startActivity(
                            Intent(this@LoginActivity, MapActivity::class.java).apply {
                                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                            }
                        )
                        finish()
                    },
                    onLogin = { login, password, onResult ->
                        lifecycleScope.launch {
                            try {
                                val api = RetrofitModule.apiService(BuildConfig.API_BASE_URL)
                                val response = api.login(LoginRequest(login, password))
                                if (response.isSuccessful) {
                                    response.body()?.let { authRepo.saveToken(it.token) }
                                    runOnUiThread { onResult(null) }
                                } else {
                                    runOnUiThread { onResult(Exception("Login failed")) }
                                }
                            } catch (e: Exception) {
                                runOnUiThread { onResult(e) }
                            }
                        }
                    }
                )
            }
        }
    }
}

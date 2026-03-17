package com.tajacore.distribution.ui.login

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.tajacore.distribution.BuildConfig
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.LoginRequest
import com.tajacore.distribution.databinding.ActivityLoginBinding
import com.tajacore.distribution.ui.main.MainActivity
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private lateinit var authRepo: AuthRepository

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)
        authRepo = AuthRepository(this)

        binding.loginButton.setOnClickListener { doLogin() }
    }

    private fun doLogin() {
        val login = binding.loginField.text?.toString()?.trim() ?: ""
        val password = binding.passwordField.text?.toString() ?: ""
        if (login.isBlank() || password.isBlank()) {
            Toast.makeText(this, "Enter email/phone and password", Toast.LENGTH_SHORT).show()
            return
        }
        binding.loginButton.isEnabled = false
        lifecycleScope.launch {
            try {
                val api = RetrofitModule.apiService(BuildConfig.API_BASE_URL)
                val response = api.login(LoginRequest(login, password))
                if (response.isSuccessful) {
                    val body = response.body()!!
                    authRepo.saveToken(body.token)
                    startActivity(Intent(this@LoginActivity, MainActivity::class.java).apply { flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK })
                    finish()
                } else {
                    val msg = response.errorBody()?.string() ?: "Login failed"
                    runOnUiThread { Toast.makeText(this@LoginActivity, msg, Toast.LENGTH_LONG).show() }
                }
            } catch (e: Exception) {
                runOnUiThread { Toast.makeText(this@LoginActivity, e.message ?: "Network error", Toast.LENGTH_LONG).show() }
            }
            runOnUiThread { binding.loginButton.isEnabled = true }
        }
    }
}

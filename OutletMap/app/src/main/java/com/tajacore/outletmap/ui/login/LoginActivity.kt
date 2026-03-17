package com.tajacore.outletmap.ui.login

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.tajacore.outletmap.data.AuthRepository
import com.tajacore.outletmap.data.api.RetrofitModule
import com.tajacore.outletmap.data.api.dto.LoginRequest
import com.tajacore.outletmap.databinding.ActivityLoginBinding
import com.tajacore.outletmap.ui.map.MapActivity
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
        val baseUrl = (binding.baseUrlField.text?.toString()?.trim()?.takeIf { it.isNotBlank() }
            ?: "http://10.0.2.2:8000/api/").let { if (it.endsWith("/")) it else "$it/" }
        lifecycleScope.launch {
            try {
                val api = RetrofitModule.apiService(baseUrl)
                val response = api.login(LoginRequest(login, password))
                if (response.isSuccessful) {
                    val body = response.body()!!
                    authRepo.saveToken(body.token)
                    authRepo.saveBaseUrl(baseUrl)
                    startActivity(Intent(this@LoginActivity, MapActivity::class.java).apply {
                        flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                    })
                    finish()
                } else {
                    runOnUiThread {
                        Toast.makeText(this@LoginActivity, "Login failed", Toast.LENGTH_LONG).show()
                    }
                }
            } catch (e: Exception) {
                runOnUiThread {
                    Toast.makeText(this@LoginActivity, e.message ?: "Network error", Toast.LENGTH_LONG).show()
                }
            }
            runOnUiThread { binding.loginButton.isEnabled = true }
        }
    }
}

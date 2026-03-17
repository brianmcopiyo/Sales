package com.tajacore.distribution.ui.login

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.textfield.TextInputEditText
import com.tajacore.distribution.BuildConfig
import com.tajacore.distribution.R
import com.tajacore.distribution.data.AuthRepository
import com.tajacore.distribution.data.api.RetrofitModule
import com.tajacore.distribution.data.api.dto.LoginRequest
import com.tajacore.distribution.ui.main.MainActivity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class LoginActivity : AppCompatActivity() {

    private lateinit var authRepo: AuthRepository
    private lateinit var emailOrPhone: TextInputEditText
    private lateinit var password: TextInputEditText
    private lateinit var errorText: android.widget.TextView
    private lateinit var loginButton: com.google.android.material.button.MaterialButton

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)
        authRepo = AuthRepository(this)

        emailOrPhone = findViewById(R.id.login_email_or_phone)
        password = findViewById(R.id.login_password)
        errorText = findViewById(R.id.login_error)
        loginButton = findViewById(R.id.login_button)

        loginButton.setOnClickListener { doLogin() }

        lifecycleScope.launch {
            val token = authRepo.getToken()
            if (token != null) {
                startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                finish()
            }
        }
    }

    private fun doLogin() {
        val login = emailOrPhone.text?.toString()?.trim() ?: ""
        val pass = password.text?.toString() ?: ""
        if (login.isBlank() || pass.isBlank()) {
            errorText.text = "Enter email/phone and password"
            errorText.visibility = View.VISIBLE
            return
        }
        errorText.visibility = View.GONE
        loginButton.isEnabled = false

        lifecycleScope.launch(Dispatchers.IO) {
            try {
                val api = RetrofitModule.apiService(BuildConfig.API_BASE_URL)
                val response = api.login(LoginRequest(login, pass))
                withContext(Dispatchers.Main) {
                    loginButton.isEnabled = true
                    if (response.isSuccessful) {
                        response.body()?.let { authRepo.saveToken(it.token) }
                        startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                        finish()
                    } else {
                        val msg = response.errorBody()?.string() ?: "Login failed"
                        errorText.text = msg
                        errorText.visibility = View.VISIBLE
                    }
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    loginButton.isEnabled = true
                    errorText.text = e.message ?: "Error"
                    errorText.visibility = View.VISIBLE
                }
            }
        }
    }
}

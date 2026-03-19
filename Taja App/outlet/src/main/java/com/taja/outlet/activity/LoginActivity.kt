package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.textfield.TextInputEditText
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager

class LoginActivity : AppCompatActivity() {
    private lateinit var sessionManager: SessionManager
    private lateinit var progressBar: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)
        sessionManager = SessionManager(this)
        if (sessionManager.isLoggedIn) {
            startActivity(Intent(this, OutletMapActivity::class.java))
            finish()
            return
        }
        progressBar = findViewById(R.id.login_progress)
        val editUsername = findViewById<TextInputEditText>(R.id.edit_username)
        val editPassword = findViewById<TextInputEditText>(R.id.edit_password)
        findViewById<Button>(R.id.button_login).setOnClickListener {
            val user = editUsername.text?.toString()?.trim()
            val pass = editPassword.text?.toString()
            if (user.isNullOrEmpty()) {
                Toast.makeText(this, R.string.error_username_required, Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            if (pass.isNullOrEmpty()) {
                Toast.makeText(this, R.string.error_password_required, Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            progressBar.visibility = View.VISIBLE
            Thread {
                val result = ApiClient.login(user, pass ?: "")
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    when (result) {
                        is ApiClient.ApiResult.Success -> when (val data = result.data) {
                            is ApiClient.LoginResult.RequiresOtp -> {
                                startActivity(Intent(this, OtpActivity::class.java)
                                    .putExtra(OtpActivity.EXTRA_PENDING_TOKEN, data.pendingToken))
                                finish()
                            }
                            is ApiClient.LoginResult.Success -> {
                                sessionManager.token = data.token
                                sessionManager.userName = data.user.name
                                startActivity(Intent(this, OutletMapActivity::class.java))
                                finish()
                            }
                        }
                        is ApiClient.ApiResult.Error ->
                            Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }.start()
        }
    }
}

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

class OtpActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_PENDING_TOKEN = "pending_token"
    }

    private lateinit var sessionManager: SessionManager
    private var pendingToken: String = ""
    private lateinit var progressBar: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_otp)
        sessionManager = SessionManager(this)
        pendingToken = intent.getStringExtra(EXTRA_PENDING_TOKEN) ?: ""
        if (pendingToken.isEmpty()) {
            Toast.makeText(this, R.string.otp_error_required, Toast.LENGTH_SHORT).show()
            finish()
            return
        }
        progressBar = findViewById(R.id.otp_progress)
        val editOtp = findViewById<TextInputEditText>(R.id.edit_otp)
        findViewById<Button>(R.id.button_verify).setOnClickListener {
            val otp = editOtp.text?.toString()?.trim()
            if (otp.isNullOrEmpty() || otp.length != 6) {
                Toast.makeText(this, R.string.otp_error_required, Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            progressBar.visibility = View.VISIBLE
            Thread {
                val result = ApiClient.verifyOtp(pendingToken, otp)
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    when (result) {
                        is ApiClient.ApiResult.Success -> {
                            sessionManager.token = result.data.token
                            sessionManager.userName = result.data.user.name
                            startActivity(Intent(this, OutletMapActivity::class.java))
                            finish()
                        }
                        is ApiClient.ApiResult.Error ->
                            Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }.start()
        }
        findViewById<Button>(R.id.button_resend).setOnClickListener {
            progressBar.visibility = View.VISIBLE
            Thread {
                val result = ApiClient.resendOtp(pendingToken)
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    when (result) {
                        is ApiClient.ApiResult.Success ->
                            Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        is ApiClient.ApiResult.Error ->
                            Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }.start()
        }
    }
}

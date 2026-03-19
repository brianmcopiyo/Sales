package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager

class OtpActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_PENDING_TOKEN = "pending_token"
        const val EXTRA_MESSAGE = "message"
    }

    private lateinit var messageText: TextView
    private lateinit var otpInput: EditText
    private lateinit var verifyButton: Button
    private lateinit var resendButton: Button
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingMessage: TextView
    private lateinit var sessionManager: SessionManager

    private var pendingToken: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_otp)
        sessionManager = SessionManager(this)
        pendingToken = intent.getStringExtra(EXTRA_PENDING_TOKEN) ?: ""
        if (pendingToken.isEmpty()) {
            Toast.makeText(this, R.string.otp_error_required, Toast.LENGTH_LONG).show()
            finish()
            return
        }
        bindViews()
        findViewById<Button>(R.id.back_button).setOnClickListener { finish() }
        messageText.text = intent.getStringExtra(EXTRA_MESSAGE) ?: getString(R.string.otp_message_placeholder)
        verifyButton.setOnClickListener { onVerifyPressed() }
        resendButton.setOnClickListener { onResendPressed() }
    }

    private fun bindViews() {
        messageText = findViewById(R.id.otp_message_text)
        otpInput = findViewById(R.id.otp_input)
        verifyButton = findViewById(R.id.otp_verify_button)
        resendButton = findViewById(R.id.otp_resend_button)
        progressBar = findViewById(R.id.otp_progress)
        loadingMessage = findViewById(R.id.otp_loading_message)
    }

    private fun onVerifyPressed() {
        val otp = otpInput.text.toString().trim()
        if (otp.length != 6) {
            otpInput.error = getString(R.string.otp_error_required)
            return
        }
        loadingMessage.text = getString(R.string.loading_verifying)
        setLoading(true)
        Thread {
            val result = ApiClient.verifyOtp(pendingToken, otp)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        sessionManager.token = result.data.token
                        sessionManager.userName = result.data.user.name
                        startActivity(Intent(this, OutletListActivity::class.java))
                        finish()
                    }
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun onResendPressed() {
        loadingMessage.text = getString(R.string.loading_sending)
        setLoading(true)
        Thread {
            val result = ApiClient.resendOtp(pendingToken)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success ->
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        loadingMessage.visibility = if (loading) View.VISIBLE else View.GONE
        verifyButton.isEnabled = !loading
        resendButton.isEnabled = !loading
        otpInput.isEnabled = !loading
    }
}

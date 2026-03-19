package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import com.google.android.material.bottomsheet.BottomSheetDialog
import androidx.appcompat.app.AppCompatActivity
import android.view.LayoutInflater
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import com.taja.app.ApiClient
import com.taja.app.R
import com.taja.app.SessionManager

class LoginActivity : AppCompatActivity() {

    private lateinit var loginButton: Button
    private lateinit var forgotPasswordButton: Button
    private lateinit var usernameEdit: EditText
    private lateinit var passwordEdit: EditText
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingMessage: TextView
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        sessionManager = SessionManager(this)
        if (sessionManager.isLoggedIn) {
            startDashboardAndFinish()
            return
        }
        setContentView(R.layout.activity_login)
        bindViews()
        loginButton.setOnClickListener { onLoginPressed() }
        forgotPasswordButton.setOnClickListener { onForgotPasswordPressed() }
    }

    private fun bindViews() {
        loginButton = findViewById(R.id.group89_button)
        forgotPasswordButton = findViewById(R.id.forgot_password_button)
        usernameEdit = findViewById(R.id.edit_username)
        passwordEdit = findViewById(R.id.edit_password)
        progressBar = findViewById(R.id.login_progress)
        loadingMessage = findViewById(R.id.login_loading_message)
    }

    private fun onLoginPressed() {
        val login = usernameEdit.text.toString().trim()
        val password = passwordEdit.text.toString()
        if (login.isBlank()) {
            usernameEdit.error = getString(R.string.error_username_required)
            return
        }
        if (password.isBlank()) {
            passwordEdit.error = getString(R.string.error_password_required)
            return
        }
        setLoading(true)
        Thread {
            val result = ApiClient.login(login, password)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> when (val data = result.data) {
                        is ApiClient.LoginResult.RequiresOtp -> {
                            val intent = Intent(this, OtpActivity::class.java)
                            intent.putExtra(OtpActivity.EXTRA_PENDING_TOKEN, data.pendingToken)
                            intent.putExtra(OtpActivity.EXTRA_MESSAGE, data.message)
                            startActivity(intent)
                            finish()
                        }
                        is ApiClient.LoginResult.Success -> {
                            sessionManager.token = data.token
                            sessionManager.userName = data.user.name
                            Thread {
                                val userResult = ApiClient.getUser(data.token)
                                if (userResult is ApiClient.ApiResult.Success) {
                                    sessionManager.branchName = userResult.data.branch?.name
                                    if (userResult.data.name.isNotBlank()) {
                                        sessionManager.userName = userResult.data.name
                                    }
                                }
                            }.start()
                            startDashboardAndFinish()
                        }
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun startDashboardAndFinish() {
        startActivity(Intent(this, DashboardActivity::class.java))
        finish()
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        loadingMessage.visibility = if (loading) View.VISIBLE else View.GONE
        loginButton.isEnabled = !loading
        forgotPasswordButton.isEnabled = !loading
        usernameEdit.isEnabled = !loading
        passwordEdit.isEnabled = !loading
    }

    private fun onForgotPasswordPressed() {
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
        bottomSheet.setContentView(sheetView)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_title).text = getString(R.string.iphone_xxs04_activity_forgot_password_button_text)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_message).text = getString(R.string.forgot_password_web_message)
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_cancel).visibility = View.GONE
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_ok).apply {
            text = getString(android.R.string.ok)
            setOnClickListener { bottomSheet.dismiss() }
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }
}

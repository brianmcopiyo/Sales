package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager

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
            startActivity(Intent(this, OutletListActivity::class.java))
            finish()
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
                            startActivity(
                                Intent(this, OtpActivity::class.java)
                                    .putExtra(OtpActivity.EXTRA_PENDING_TOKEN, data.pendingToken)
                                    .putExtra(OtpActivity.EXTRA_MESSAGE, data.message)
                            )
                            finish()
                        }
                        is ApiClient.LoginResult.Success -> {
                            sessionManager.token = data.token
                            sessionManager.userName = data.user.name
                            startActivity(Intent(this, OutletListActivity::class.java))
                            finish()
                        }
                    }
                    is ApiClient.ApiResult.Error ->
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
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
        val bottomSheet = BottomSheetDialog(this, R.style.AppBottomSheetDialogTheme)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
        bottomSheet.setContentView(sheetView)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_title).text =
            getString(R.string.iphone_xxs04_activity_forgot_password_button_text)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_message).text =
            getString(R.string.forgot_password_web_message)
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_cancel).visibility = View.GONE
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_ok).apply {
            text = getString(android.R.string.ok)
            setOnClickListener { bottomSheet.dismiss() }
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }
}

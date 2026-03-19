package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.google.android.material.navigation.NavigationBarView
import com.taja.app.R
import com.taja.app.SessionManager

class ProfileActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var nameText: TextView
    private lateinit var branchLabel: TextView
    private lateinit var branchText: TextView
    private lateinit var logoutButton: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_profile)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            startLoginAndFinish()
            return
        }
        nameText = findViewById(R.id.profile_name)
        branchLabel = findViewById(R.id.profile_branch_label)
        branchText = findViewById(R.id.profile_branch)
        logoutButton = findViewById(R.id.profile_logout)

        val name = intent.getStringExtra(EXTRA_NAME) ?: sessionManager.userName
        val branch = intent.getStringExtra(EXTRA_BRANCH) ?: sessionManager.branchName
        nameText.text = name ?: "User"
        if (!branch.isNullOrBlank()) {
            branchLabel.visibility = android.view.View.VISIBLE
            branchText.visibility = android.view.View.VISIBLE
            branchText.text = branch
        } else {
            branchLabel.visibility = android.view.View.GONE
            branchText.visibility = android.view.View.GONE
        }

        logoutButton.setOnClickListener { onLogoutPressed() }
        setupBottomNav()
    }

    private fun setupBottomNav() {
        val bottomNav = findViewById<NavigationBarView>(R.id.bottom_navigation)
        bottomNav.selectedItemId = R.id.nav_profile
        bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_dashboard -> {
                    startActivity(Intent(this, DashboardActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_profile -> true
                R.id.nav_outlets -> {
                    startActivity(Intent(this, OutletsListActivity::class.java))
                    finish()
                    false
                }
                else -> false
            }
        }
    }

    private fun onLogoutPressed() {
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
        bottomSheet.setContentView(sheetView)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_title).text = getString(R.string.logout)
        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_message).text = getString(R.string.logout)
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_cancel).apply {
            text = getString(android.R.string.cancel)
            setOnClickListener { bottomSheet.dismiss() }
        }
        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_ok).apply {
            text = getString(android.R.string.ok)
            setOnClickListener {
                bottomSheet.dismiss()
                sessionManager.logout()
                startLoginAndFinish()
            }
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }

    private fun startLoginAndFinish() {
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }

    companion object {
        const val EXTRA_NAME = "name"
        const val EXTRA_BRANCH = "branch"
    }
}

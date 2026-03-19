package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import com.google.android.material.navigation.NavigationBarView
import androidx.appcompat.app.AppCompatActivity
import android.view.View
import android.widget.ProgressBar
import android.widget.TextView
import com.taja.app.ApiClient
import com.taja.app.CheckInQueue
import com.taja.app.R
import com.taja.app.SessionManager

class DashboardActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var welcomeText: TextView
    private lateinit var branchText: TextView
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingMessage: TextView
    private lateinit var statOutlets: TextView
    private lateinit var statCheckInsToday: TextView
    private lateinit var statCheckInsWeek: TextView
    private lateinit var statCoverage: TextView
    private lateinit var bottomNavigation: NavigationBarView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_dashboard)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            startLoginAndFinish()
            return
        }
        bindViews()
        bottomNavigation.selectedItemId = R.id.nav_dashboard
        bottomNavigation.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_dashboard -> true
                R.id.nav_outlets -> {
                    startActivity(Intent(this, OutletsListActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_profile -> {
                    startActivity(Intent(this, ProfileActivity::class.java).apply {
                        sessionManager.userName?.let { putExtra(ProfileActivity.EXTRA_NAME, it) }
                        sessionManager.branchName?.let { putExtra(ProfileActivity.EXTRA_BRANCH, it) }
                    })
                    finish()
                    false
                }
                else -> false
            }
        }
    }

    override fun onResume() {
        super.onResume()
        if (sessionManager.isLoggedIn) {
            loadDashboard()
            trySyncPendingCheckIns()
        }
    }

    private fun trySyncPendingCheckIns() {
        val token = sessionManager.token ?: return
        val queue = CheckInQueue(this)
        val pending = queue.getAll()
        if (pending.isEmpty() || !CheckInQueue.isNetworkAvailable(this)) return
        Thread {
            val result = ApiClient.syncCheckIns(token, pending)
            runOnUiThread {
                if (result is ApiClient.ApiResult.Success && result.data.synced.isNotEmpty()) {
                    queue.removeByClientIds(result.data.synced.map { it.clientId }.toSet())
                    loadDashboard() // refresh KPIs after sync
                }
            }
        }.start()
    }

    private fun bindViews() {
        welcomeText = findViewById(R.id.dashboard_welcome_text)
        branchText = findViewById(R.id.dashboard_branch_text)
        progressBar = findViewById(R.id.dashboard_progress)
        loadingMessage = findViewById(R.id.dashboard_loading_message)
        statOutlets = findViewById(R.id.stat_outlets)
        statCheckInsToday = findViewById(R.id.stat_check_ins_today)
        statCheckInsWeek = findViewById(R.id.stat_check_ins_week)
        statCoverage = findViewById(R.id.stat_coverage)
        bottomNavigation = findViewById(R.id.bottom_navigation)
    }

    private fun loadDashboard() {
        val name = sessionManager.userName ?: getString(R.string.dashboard_welcome).replace("Welcome, ", "").trim()
        welcomeText.text = getString(R.string.dashboard_hello, name)
        sessionManager.branchName?.let { branchName ->
            branchText.text = getString(R.string.dashboard_branch, branchName)
            branchText.visibility = View.VISIBLE
        } ?: run { branchText.visibility = View.GONE }

        val token = sessionManager.token
        if (token.isNullOrBlank()) {
            startLoginAndFinish()
            return
        }
        progressBar.visibility = View.VISIBLE
        loadingMessage.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.getDashboardSummary(token)
            runOnUiThread {
                progressBar.visibility = View.GONE
                loadingMessage.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val d = result.data
                        statOutlets.text = d.outletsCount.toString()
                        statCheckInsToday.text = d.checkInsToday.toString()
                        statCheckInsWeek.text = d.checkInsThisWeek.toString()
                        statCoverage.text = if (d.outletsCount > 0) {
                            "${(d.checkInsThisWeek * 100 / d.outletsCount)}%"
                        } else "—"
                    }
                    is ApiClient.ApiResult.Error -> {
                        statOutlets.text = "0"
                        statCheckInsToday.text = "0"
                        statCheckInsWeek.text = "0"
                        statCoverage.text = "—"
                    }
                }
            }
        }.start()
    }

    private fun startLoginAndFinish() {
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }
}

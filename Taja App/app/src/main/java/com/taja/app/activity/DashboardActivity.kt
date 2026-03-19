package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import com.google.android.material.navigation.NavigationBarView
import androidx.appcompat.app.AppCompatActivity
import android.view.View
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import com.taja.app.ApiClient
import com.taja.app.R
import com.taja.app.SessionManager
import java.util.Locale

class DashboardActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var welcomeText: TextView
    private lateinit var branchText: TextView
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingMessage: TextView
    private var userBranchId: String? = null
    private var lastUserName: String? = null
    private var lastBranchName: String? = null

    private lateinit var statSalesTotal: TextView
    private lateinit var statSalesToday: TextView
    private lateinit var statSalesMonth: TextView
    private lateinit var statRevenueTotal: TextView
    private lateinit var statRevenueMonth: TextView
    private lateinit var statTicketsOpen: TextView
    private lateinit var statTicketsTotal: TextView
    private lateinit var statLowStock: TextView
    private lateinit var statOutOfStock: TextView
    private lateinit var statDevicesAvailable: TextView
    private lateinit var statDevicesTotal: TextView
    private lateinit var statPendingTransfers: TextView
    private lateinit var statPendingStockTakes: TextView
    private lateinit var statPendingRestockOrders: TextView
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
                R.id.nav_stock -> {
                    startActivity(Intent(this, PendingOrdersActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_stock_takes -> {
                    startActivity(Intent(this, StockTakeListActivity::class.java).apply {
                        userBranchId?.let { putExtra(StockTakeListActivity.EXTRA_USER_BRANCH_ID, it) }
                    })
                    finish()
                    false
                }
                R.id.nav_profile -> {
                    startActivity(Intent(this, ProfileActivity::class.java).apply {
                        lastUserName?.let { putExtra(ProfileActivity.EXTRA_NAME, it) }
                        lastBranchName?.let { putExtra(ProfileActivity.EXTRA_BRANCH, it) }
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
        }
    }

    private fun bindViews() {
        welcomeText = findViewById(R.id.dashboard_welcome_text)
        branchText = findViewById(R.id.dashboard_branch_text)
        progressBar = findViewById(R.id.dashboard_progress)
        loadingMessage = findViewById(R.id.dashboard_loading_message)
        bottomNavigation = findViewById(R.id.bottom_navigation)
        statSalesTotal = findViewById(R.id.stat_sales_total)
        statSalesToday = findViewById(R.id.stat_sales_today)
        statSalesMonth = findViewById(R.id.stat_sales_month)
        statRevenueTotal = findViewById(R.id.stat_revenue_total)
        statRevenueMonth = findViewById(R.id.stat_revenue_month)
        statTicketsOpen = findViewById(R.id.stat_tickets_open)
        statTicketsTotal = findViewById(R.id.stat_tickets_total)
        statLowStock = findViewById(R.id.stat_low_stock)
        statOutOfStock = findViewById(R.id.stat_out_of_stock)
        statDevicesAvailable = findViewById(R.id.stat_devices_available)
        statDevicesTotal = findViewById(R.id.stat_devices_total)
        statPendingTransfers = findViewById(R.id.stat_pending_transfers)
        statPendingStockTakes = findViewById(R.id.stat_pending_stock_takes)
        statPendingRestockOrders = findViewById(R.id.stat_pending_restock_orders)
    }

    private fun loadDashboard() {
        val token = sessionManager.token ?: run {
            startLoginAndFinish()
            return
        }
        setLoading(true)
        Thread {
            val result = ApiClient.getDashboard(token)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        userBranchId = result.data.user.branchId
                        lastUserName = result.data.user.name
                        lastBranchName = result.data.branchName
                        sessionManager.userName = result.data.user.name
                        sessionManager.branchName = result.data.branchName
                        welcomeText.text = getString(R.string.dashboard_hello, result.data.user.name)
                        result.data.branchName?.let { name ->
                            branchText.text = getString(R.string.dashboard_branch, name)
                            branchText.visibility = View.VISIBLE
                        } ?: run { branchText.visibility = View.GONE }
                        val s = result.data.stats
                        statSalesTotal.text = s.totalSales.toString()
                        statSalesToday.text = s.salesToday.toString()
                        statSalesMonth.text = s.salesThisMonth.toString()
                        statRevenueTotal.text = formatMoney(s.totalRevenue)
                        statRevenueMonth.text = formatMoney(s.revenueThisMonth)
                        statTicketsOpen.text = s.openTickets.toString()
                        statTicketsTotal.text = s.totalTickets.toString()
                        statLowStock.text = s.lowStockItems.toString()
                        statOutOfStock.text = s.outOfStockItems.toString()
                        statDevicesAvailable.text = s.availableDevices.toString()
                        statDevicesTotal.text = s.totalDevices.toString()
                        statPendingTransfers.text = s.pendingTransfers.toString()
                        statPendingStockTakes.text = s.pendingStockTakes.toString()
                        statPendingRestockOrders.text = s.pendingRestockOrders.toString()
                    }
                    is ApiClient.ApiResult.Error -> {
                        if (result.code == 401) {
                            sessionManager.logout()
                            startLoginAndFinish()
                        } else {
                            Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        }
                    }
                }
            }
        }.start()
    }

    private fun formatMoney(value: Double): String {
        return if (value >= 1_000_000) String.format(Locale.US, "%.1fM", value / 1_000_000)
        else if (value >= 1_000) String.format(Locale.US, "%.1fK", value / 1_000)
        else String.format(Locale.US, "%.0f", value)
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        loadingMessage.visibility = if (loading) View.VISIBLE else View.GONE
        bottomNavigation.isEnabled = !loading
    }

    private fun startLoginAndFinish() {
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }
}

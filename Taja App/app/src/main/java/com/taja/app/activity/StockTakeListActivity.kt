package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import android.os.Handler
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.google.android.material.navigation.NavigationBarView
import androidx.appcompat.app.AppCompatActivity
import android.view.LayoutInflater
import android.view.View
import android.widget.Button
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import com.taja.app.ApiClient
import com.taja.app.R
import com.taja.app.SessionManager

class StockTakeListActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_USER_BRANCH_ID = "user_branch_id"
        const val EXTRA_OPEN_STOCK_TAKE_ID = "open_stock_take_id"
        private const val REQUEST_CREATE_STOCK_TAKE = 1001
    }

    private lateinit var sessionManager: SessionManager
    private var openStockTakeId: String? = null
    private var userBranchId: String? = null
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var listContainer: LinearLayout
    private var detailSheet: BottomSheetDialog? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_stocktake_list)
        sessionManager = SessionManager(this)
        userBranchId = intent.getStringExtra(EXTRA_USER_BRANCH_ID)?.takeIf { it.isNotEmpty() }
        bindViews()
        setupBottomNav()
        loadList()
    }

    override fun onResume() {
        super.onResume()
        if (sessionManager.isLoggedIn) {
            loadList()
            openStockTakeId?.let { id ->
                openStockTakeId = null
                Handler().post { showDetailSheet(id) }
            }
        }
    }

    private fun bindViews() {
        progressBar = findViewById(R.id.stocktake_list_progress)
        emptyText = findViewById(R.id.stocktake_list_empty)
        listContainer = findViewById(R.id.stocktake_list_container)
        findViewById<android.widget.Button>(R.id.stocktake_list_back).setOnClickListener { finish() }
        findViewById<android.widget.Button>(R.id.stocktake_list_new_btn).setOnClickListener {
            startActivityForResult(Intent(this, StockTakeCreateActivity::class.java).apply {
                userBranchId?.let { putExtra(StockTakeCreateActivity.EXTRA_USER_BRANCH_ID, it) }
            }, REQUEST_CREATE_STOCK_TAKE)
        }
    }

    private fun setupBottomNav() {
        val bottomNav = findViewById<NavigationBarView>(R.id.bottom_navigation)
        bottomNav.selectedItemId = R.id.nav_stock_takes
        bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_dashboard -> {
                    startActivity(Intent(this, DashboardActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_stock_takes -> true
                R.id.nav_stock -> {
                    startActivity(Intent(this, PendingOrdersActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_profile -> {
                    startActivity(Intent(this, ProfileActivity::class.java))
                    finish()
                    false
                }
                else -> false
            }
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == REQUEST_CREATE_STOCK_TAKE && resultCode == RESULT_OK) {
            data?.getStringExtra(EXTRA_OPEN_STOCK_TAKE_ID)?.let { id -> openStockTakeId = id }
        }
    }

    private fun loadList() {
        val token = sessionManager.token ?: run { finish(); return }
        setLoading(true)
        emptyText.visibility = View.GONE
        listContainer.removeAllViews()
        Thread {
            val result = ApiClient.getStockTakes(token)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val list = result.data.stock_takes
                        listContainer.removeAllViews()
                        if (list.isEmpty()) {
                            emptyText.visibility = View.VISIBLE
                        } else {
                            list.forEach { st -> addRow(st) }
                        }
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        if (result.code == 403) finish()
                        else emptyText.visibility = View.VISIBLE
                    }
                }
            }
        }.start()
    }

    private fun addRow(st: ApiClient.StockTakeListItem) {
        val row = LayoutInflater.from(this).inflate(R.layout.item_stocktake_row, listContainer, false)
        row.findViewById<TextView>(R.id.item_stocktake_number).text = st.stock_take_number
        row.findViewById<TextView>(R.id.item_stocktake_branch).text = st.branch_name ?: st.branch_code ?: ""
        row.findViewById<TextView>(R.id.item_stocktake_date_status).text =
            listOfNotNull(st.stock_take_date, statusLabel(st.status)).joinToString(" · ")
        row.findViewById<TextView>(R.id.item_stocktake_counts).text =
            getString(R.string.stocktake_counted) + ": " + st.counted_count + " / " + st.items_count
        row.setOnClickListener { showDetailSheet(st.id) }
        listContainer.addView(row)
    }

    private fun showDetailSheet(stockTakeId: String) {
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_stocktake_detail, null) as LinearLayout
        detailSheet = BottomSheetDialog(this).apply {
            setContentView(sheetView)
            window?.setBackgroundDrawableResource(android.R.color.transparent)
        }
        val sheetProgress = sheetView.findViewById<ProgressBar>(R.id.stocktake_sheet_progress)
        val sheetTitle = sheetView.findViewById<TextView>(R.id.stocktake_sheet_title)
        val sheetBranchDate = sheetView.findViewById<TextView>(R.id.stocktake_sheet_branch_date)
        val sheetStatus = sheetView.findViewById<TextView>(R.id.stocktake_sheet_status)
        val sheetSummary = sheetView.findViewById<TextView>(R.id.stocktake_sheet_summary)
        val sheetCompleteHint = sheetView.findViewById<TextView>(R.id.stocktake_sheet_complete_hint)
        val sheetItemsContainer = sheetView.findViewById<LinearLayout>(R.id.stocktake_sheet_items)
        val editBtn = sheetView.findViewById<Button>(R.id.stocktake_sheet_edit)
        val completeBtn = sheetView.findViewById<Button>(R.id.stocktake_sheet_complete)
        val approveBtn = sheetView.findViewById<Button>(R.id.stocktake_sheet_approve)
        val cancelBtn = sheetView.findViewById<Button>(R.id.stocktake_sheet_cancel)
        sheetView.findViewById<Button>(R.id.stocktake_sheet_close).setOnClickListener {
            detailSheet?.dismiss()
            detailSheet = null
        }
        sheetProgress.visibility = View.VISIBLE
        sheetItemsContainer.removeAllViews()
        editBtn.visibility = View.GONE
        completeBtn.visibility = View.GONE
        approveBtn.visibility = View.GONE
        cancelBtn.visibility = View.GONE
        detailSheet!!.show()

        val token = sessionManager.token ?: return
        Thread {
            val result = ApiClient.getStockTake(token, stockTakeId)
            runOnUiThread {
                sheetProgress.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val st = result.data.stock_take
                        val sum = result.data.summary
                        sheetTitle.text = getString(R.string.stocktake_detail_title, st.stock_take_number)
                        sheetBranchDate.text = listOfNotNull(st.branch_name, st.stock_take_date).joinToString(" · ")
                        sheetStatus.text = statusLabel(st.status)
                        sheetSummary.text = getString(R.string.stocktake_counted) + ": " + sum.counted_items + " / " + sum.total_items +
                            (if (sum.items_with_variance != 0) " · " + getString(R.string.stocktake_variance) + ": " + sum.total_variance else "")
                        st.items.forEach { item ->
                            val row = LayoutInflater.from(this).inflate(R.layout.item_stocktake_sheet_row, sheetItemsContainer, false)
                            row.findViewById<TextView>(R.id.sheet_row_product).text = item.product_name ?: item.product_sku ?: ""
                            row.findViewById<TextView>(R.id.sheet_row_counts).text =
                                "Opening: ${item.system_quantity} · Closing: ${item.physical_quantity ?: "—"} · Var: ${item.variance}"
                            sheetItemsContainer.addView(row)
                        }
                        val canEdit = st.status == "draft" || st.status == "in_progress"
                        val allCounted = sum.pending_items == 0
                        val canComplete = (st.status == "draft" || st.status == "in_progress") && allCounted
                        val canApprove = st.status == "completed"
                        val canCancel = canEdit || st.status == "completed"
                        editBtn.visibility = if (canEdit) View.VISIBLE else View.GONE
                        completeBtn.visibility = if (canComplete) View.VISIBLE else View.GONE
                        approveBtn.visibility = if (canApprove) View.VISIBLE else View.GONE
                        cancelBtn.visibility = if (canCancel) View.VISIBLE else View.GONE
                        sheetCompleteHint.visibility = if (canEdit && !allCounted) View.VISIBLE else View.GONE
                        editBtn.setOnClickListener {
                            detailSheet?.dismiss()
                            detailSheet = null
                            startActivity(Intent(this, StockTakeEditActivity::class.java).putExtra(StockTakeEditActivity.EXTRA_STOCK_TAKE_ID, stockTakeId))
                            loadList()
                        }
                        completeBtn.setOnClickListener { showCompleteConfirm(stockTakeId) }
                        approveBtn.setOnClickListener { showApproveConfirm(stockTakeId) }
                        cancelBtn.setOnClickListener { showCancelConfirm(stockTakeId) }
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        detailSheet?.dismiss()
                        detailSheet = null
                    }
                }
            }
        }.start()
    }

    private fun showCompleteConfirm(stockTakeId: String) {
        val sheet = BottomSheetDialog(this)
        val view = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
        sheet.setContentView(view)
        view.findViewById<TextView>(R.id.bottomsheet_confirm_title).text = getString(R.string.stocktake_complete)
        view.findViewById<TextView>(R.id.bottomsheet_confirm_message).text = getString(R.string.stocktake_complete_confirm)
        view.findViewById<Button>(R.id.bottomsheet_confirm_cancel).text = getString(android.R.string.cancel)
        view.findViewById<Button>(R.id.bottomsheet_confirm_cancel).setOnClickListener { sheet.dismiss() }
        view.findViewById<Button>(R.id.bottomsheet_confirm_ok).text = getString(android.R.string.ok)
        view.findViewById<Button>(R.id.bottomsheet_confirm_ok).setOnClickListener {
            sheet.dismiss()
            postComplete(stockTakeId)
        }
        sheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        sheet.show()
    }

    private fun showApproveConfirm(stockTakeId: String) {
        val sheet = BottomSheetDialog(this)
        val view = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
        sheet.setContentView(view)
        view.findViewById<TextView>(R.id.bottomsheet_confirm_title).text = getString(R.string.stocktake_approve)
        view.findViewById<TextView>(R.id.bottomsheet_confirm_message).text = getString(R.string.stocktake_approve_confirm)
        view.findViewById<Button>(R.id.bottomsheet_confirm_cancel).text = getString(android.R.string.cancel)
        view.findViewById<Button>(R.id.bottomsheet_confirm_cancel).setOnClickListener { sheet.dismiss() }
        view.findViewById<Button>(R.id.bottomsheet_confirm_ok).text = getString(android.R.string.ok)
        view.findViewById<Button>(R.id.bottomsheet_confirm_ok).setOnClickListener {
            sheet.dismiss()
            postApprove(stockTakeId)
        }
        sheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        sheet.show()
    }

    private fun showCancelConfirm(stockTakeId: String) {
        val sheet = BottomSheetDialog(this)
        val view = LayoutInflater.from(this).inflate(R.layout.bottomsheet_stocktake_cancel, null)
        sheet.setContentView(view)
        val reasonEdit = view.findViewById<android.widget.EditText>(R.id.stocktake_cancel_reason)
        view.findViewById<Button>(R.id.stocktake_cancel_sheet_cancel).setOnClickListener { sheet.dismiss() }
        view.findViewById<Button>(R.id.stocktake_cancel_sheet_confirm).setOnClickListener {
            sheet.dismiss()
            postCancel(stockTakeId, reasonEdit.text.toString().trim().takeIf { it.isNotEmpty() })
        }
        sheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        sheet.show()
    }

    private fun postComplete(stockTakeId: String) {
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.completeStockTake(token, stockTakeId, null)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        detailSheet?.dismiss()
                        detailSheet = null
                        loadList()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun postApprove(stockTakeId: String) {
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.approveStockTake(token, stockTakeId, null)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        detailSheet?.dismiss()
                        detailSheet = null
                        loadList()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun postCancel(stockTakeId: String, reason: String? = null) {
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.cancelStockTake(token, stockTakeId, reason)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        detailSheet?.dismiss()
                        detailSheet = null
                        loadList()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun statusLabel(status: String): String = when (status) {
        "draft" -> getString(R.string.stocktake_status_draft)
        "in_progress" -> getString(R.string.stocktake_status_in_progress)
        "completed" -> getString(R.string.stocktake_status_completed)
        "approved" -> getString(R.string.stocktake_status_approved)
        "cancelled" -> getString(R.string.stocktake_status_cancelled)
        else -> status
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
    }
}

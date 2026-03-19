package com.taja.app.activity

import android.content.Intent
import android.os.Bundle
import com.google.android.material.bottomsheet.BottomSheetDialog
import androidx.appcompat.app.AppCompatActivity
import android.view.LayoutInflater
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.Spinner
import android.widget.TextView
import android.widget.Toast
import com.taja.app.ApiClient
import com.taja.app.R
import com.taja.app.SessionManager

class StockTakeEditActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_STOCK_TAKE_ID = "stock_take_id"
        private const val REQUEST_SCAN_IMEI = 3001
    }

    private lateinit var sessionManager: SessionManager
    private var stockTakeId: String = ""
    private var stockTake: ApiClient.StockTakeFull? = null
    private var editData: ApiClient.StockTakeEditDataResponse? = null
    private var currentImeiEdit: EditText? = null

    private lateinit var progressBar: ProgressBar
    private lateinit var addProductSpinner: Spinner
    private lateinit var itemsContainer: LinearLayout

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_stocktake_edit)
        sessionManager = SessionManager(this)
        stockTakeId = intent.getStringExtra(EXTRA_STOCK_TAKE_ID) ?: ""
        if (stockTakeId.isEmpty()) { finish(); return }
        bindViews()
        loadData()
    }

    private fun bindViews() {
        progressBar = findViewById(R.id.stocktake_edit_progress)
        addProductSpinner = findViewById(R.id.stocktake_edit_add_product)
        itemsContainer = findViewById(R.id.stocktake_edit_items)
        findViewById<Button>(R.id.stocktake_edit_back).setOnClickListener { finish() }
        findViewById<Button>(R.id.stocktake_edit_add_btn).setOnClickListener { onAddProduct() }
        findViewById<Button>(R.id.stocktake_edit_complete).setOnClickListener { onComplete() }
    }

    private fun loadData() {
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val showResult = ApiClient.getStockTake(token, stockTakeId)
            val editResult = ApiClient.getStockTakeEditData(token, stockTakeId)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when {
                    showResult is ApiClient.ApiResult.Success && editResult is ApiClient.ApiResult.Success -> {
                        stockTake = showResult.data.stock_take
                        editData = editResult.data
                        renderAddProductSpinner()
                        renderItems()
                    }
                    showResult is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, showResult.message, Toast.LENGTH_LONG).show()
                        if (showResult.code == 403) finish()
                    }
                    editResult is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, editResult.message, Toast.LENGTH_LONG).show()
                        if (editResult.code == 403) finish()
                    }
                }
            }
        }.start()
    }

    private fun renderAddProductSpinner() {
        val data = editData ?: return
        val available = data.branch_stocks.filter { it.product_id !in data.existing_product_ids }
        val labels = available.map { "${it.product_name ?: it.product_sku} (${it.quantity})" }
        addProductSpinner.adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            listOf(getString(R.string.restock_select_product)) + labels
        )
        addProductSpinner.tag = available
        addProductSpinner.setSelection(0)
    }

    private fun renderItems() {
        val st = stockTake ?: return
        itemsContainer.removeAllViews()
        st.items.forEach { item ->
            addItemRow(item)
        }
    }

    private fun addItemRow(item: ApiClient.StockTakeItemData) {
        val row = LayoutInflater.from(this).inflate(R.layout.item_stocktake_edit_row, itemsContainer, false)
        row.findViewById<TextView>(R.id.row_edit_product_name).text = item.product_name ?: item.product_sku ?: ""
        row.findViewById<TextView>(R.id.row_edit_system).text = item.system_quantity.toString()
        row.findViewById<TextView>(R.id.row_edit_physical).text = item.physical_quantity?.toString() ?: "—"
        row.findViewById<TextView>(R.id.row_edit_variance).text = item.variance.toString()
        row.tag = item

        row.findViewById<Button>(R.id.row_edit_count_btn).setOnClickListener { showCountSheet(item) }
        row.setOnClickListener { showCountSheet(item) }
        row.findViewById<Button>(R.id.row_edit_remove).setOnClickListener { removeItem(row, item.id) }
        itemsContainer.addView(row)
    }

    private fun showCountSheet(item: ApiClient.StockTakeItemData) {
        try {
            val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_stocktake_count_item, null)
            val sheet = BottomSheetDialog(this, com.taja.app.R.style.AppBottomSheetDialogTheme).apply {
                setContentView(sheetView)
                window?.setBackgroundDrawableResource(android.R.color.transparent)
            }
            sheetView.findViewById<TextView>(R.id.count_sheet_product).text = item.product_name ?: item.product_sku ?: ""
            sheetView.findViewById<TextView>(R.id.count_sheet_system).text = item.system_quantity.toString()
            val physicalEdit = sheetView.findViewById<EditText>(R.id.count_sheet_physical)
            physicalEdit.setText(item.physical_quantity?.toString() ?: "")
            val imeiEdit = sheetView.findViewById<EditText>(R.id.count_sheet_imei)
            imeiEdit.setText((item.submitted_imeis ?: emptyList()).joinToString("\n"))
            sheetView.findViewById<Button>(R.id.count_sheet_scan_imei).setOnClickListener {
                currentImeiEdit = imeiEdit
                startActivityForResult(Intent(this@StockTakeEditActivity, ScanImeiActivity::class.java), REQUEST_SCAN_IMEI)
            }
            sheetView.findViewById<Button>(R.id.count_sheet_cancel).setOnClickListener { sheet.dismiss() }
            sheetView.findViewById<Button>(R.id.count_sheet_save).setOnClickListener {
                val physicalStr = physicalEdit.text.toString().trim()
                val physical = physicalStr.toIntOrNull()
                if (physical == null || physical < 0) {
                    Toast.makeText(this, getString(R.string.stocktake_physical_count), Toast.LENGTH_SHORT).show()
                    return@setOnClickListener
                }
                val imeis = imeiEdit.text.toString().trim().takeIf { it.isNotEmpty() }
                sheet.dismiss()
                saveItemFromSheet(item.id, physical, imeis)
            }
            sheet.show()
        } catch (e: Exception) {
            android.util.Log.e("StockTakeEdit", "Failed to show count sheet", e)
            Toast.makeText(this, getString(R.string.stocktake_physical_count) + ": " + e.message, Toast.LENGTH_LONG).show()
        }
    }

    private fun saveItemFromSheet(itemId: String, physical: Int, imeis: String?) {
        if (itemId.isBlank()) {
            Toast.makeText(this, getString(R.string.stocktake_physical_count), Toast.LENGTH_SHORT).show()
            return
        }
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.updateStockTakeItem(token, stockTakeId, itemId, physical, null, imeis)
            runOnUiThread {
                if (isFinishing) return@runOnUiThread
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        loadData()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun removeItem(row: View, itemId: String) {
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.removeStockTakeItem(token, stockTakeId, itemId)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        itemsContainer.removeView(row)
                        loadData()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun onAddProduct() {
        @Suppress("UNCHECKED_CAST")
        val available = addProductSpinner.tag as? List<ApiClient.StockTakeBranchStockRow> ?: return
        val pos = addProductSpinner.selectedItemPosition
        if (pos < 1) {
            Toast.makeText(this, getString(R.string.restock_select_product), Toast.LENGTH_SHORT).show()
            return
        }
        val productId = available[pos - 1].product_id
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.addStockTakeItem(token, stockTakeId, productId)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, getString(R.string.stocktake_add_product) + " ✓", Toast.LENGTH_SHORT).show()
                        loadData()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == REQUEST_SCAN_IMEI && resultCode == RESULT_OK) {
            val imeis = data?.getStringExtra("scanned_imeis") ?: ""
            currentImeiEdit?.setText(imeis)
            currentImeiEdit = null
        }
    }

    private fun onComplete() {
        val st = stockTake ?: return
        val items = st.items.mapNotNull { item ->
            val physical = item.physical_quantity ?: return@mapNotNull null
            Triple(item.id, physical, (item.submitted_imeis ?: emptyList()).joinToString("\n").takeIf { it.isNotEmpty() })
        }
        if (items.size != st.items.size) {
            Toast.makeText(this, getString(R.string.stocktake_pending) + " " + (st.items.size - items.size), Toast.LENGTH_SHORT).show()
            return
        }
        val token = sessionManager.token ?: return
        progressBar.visibility = View.VISIBLE
        Thread {
            val result = ApiClient.completeStockTake(token, stockTakeId, items)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        finish()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }
}

package com.kimaro.activity

import android.app.DatePickerDialog
import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import android.view.LayoutInflater
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.Spinner
import android.widget.Toast
import com.kimaro.ApiClient
import com.kimaro.R
import com.kimaro.SessionManager
import java.util.Calendar

class StockTakeCreateActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_USER_BRANCH_ID = "user_branch_id"
    }

    private lateinit var sessionManager: SessionManager
    private var userBranchId: String? = null
    private var branches: List<ApiClient.RestockBranch> = emptyList()
    private var products: List<ApiClient.StockTakeProductData> = emptyList()

    private lateinit var branchContainer: LinearLayout
    private lateinit var branchSpinner: Spinner
    private lateinit var dateEdit: EditText
    private lateinit var notesEdit: EditText
    private lateinit var productsContainer: LinearLayout
    private lateinit var progressBar: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_stocktake_create)
        sessionManager = SessionManager(this)
        userBranchId = intent.getStringExtra(EXTRA_USER_BRANCH_ID)?.takeIf { it.isNotEmpty() }
        bindViews()
        loadData()
    }

    private fun bindViews() {
        branchContainer = findViewById(R.id.stocktake_create_branch_container)
        branchSpinner = findViewById(R.id.stocktake_create_branch)
        dateEdit = findViewById(R.id.stocktake_create_date)
        notesEdit = findViewById(R.id.stocktake_create_notes)
        productsContainer = findViewById(R.id.stocktake_create_products)
        progressBar = findViewById(R.id.stocktake_create_progress)

        findViewById<Button>(R.id.stocktake_create_back).setOnClickListener { finish() }
        dateEdit.setOnClickListener { showDatePicker() }
        findViewById<Button>(R.id.stocktake_create_add_product).setOnClickListener { addProductRow() }
        findViewById<Button>(R.id.stocktake_create_submit).setOnClickListener { onSubmit() }
    }

    private fun showDatePicker() {
        val cal = Calendar.getInstance()
        DatePickerDialog(
            this,
            { _, year, month, dayOfMonth ->
                val mm = (month + 1).toString().padStart(2, '0')
                val dd = dayOfMonth.toString().padStart(2, '0')
                dateEdit.setText("$year-$mm-$dd")
            },
            cal.get(Calendar.YEAR),
            cal.get(Calendar.MONTH),
            cal.get(Calendar.DAY_OF_MONTH)
        ).show()
    }

    private fun loadData() {
        val token = sessionManager.token ?: run { finish(); return }
        progressBar.visibility = android.view.View.VISIBLE
        Thread {
            val result = ApiClient.getStockTakeCreateData(token)
            runOnUiThread {
                progressBar.visibility = android.view.View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        products = result.data.products
                        if (userBranchId == null) {
                            branches = result.data.branches
                            branchContainer.visibility = android.view.View.VISIBLE
                            branchSpinner.adapter = ArrayAdapter(
                                this,
                                android.R.layout.simple_spinner_item,
                                listOf(getString(R.string.restock_select_branch)) + branches.map { "${it.name} (${it.code})" }
                            )
                            branchSpinner.setSelection(0)
                        } else {
                            branchContainer.visibility = android.view.View.GONE
                        }
                        val today = Calendar.getInstance()
                        dateEdit.setText(String.format("%04d-%02d-%02d", today.get(Calendar.YEAR), today.get(Calendar.MONTH) + 1, today.get(Calendar.DAY_OF_MONTH)))
                        if (productsContainer.childCount == 0) addProductRow()
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        if (result.code == 403) finish()
                    }
                }
            }
        }.start()
    }

    private fun addProductRow() {
        val row = LayoutInflater.from(this).inflate(R.layout.item_stocktake_create_product, productsContainer, false)
        val productSpinner = row.findViewById<Spinner>(R.id.row_product)
        val names = products.map { "${it.name} (${it.sku})" }
        productSpinner.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, listOf(getString(R.string.restock_select_product)) + names)
        productSpinner.setSelection(0)
        val openingEdit = row.findViewById<EditText>(R.id.row_opening_stock)
        productSpinner.setOnItemSelectedListener(object : android.widget.AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: android.widget.AdapterView<*>?, view: android.view.View?, position: Int, id: Long) {
                if (position > 0) openingEdit.setText(products[position - 1].current_stock.toString())
            }
            override fun onNothingSelected(parent: android.widget.AdapterView<*>?) {}
        })
        productsContainer.addView(row)
    }

    private fun onSubmit() {
        val branchId = if (userBranchId != null) userBranchId!!
        else {
            val sel = branchSpinner.selectedItemPosition
            if (sel < 1) {
                Toast.makeText(this, getString(R.string.restock_select_branch), Toast.LENGTH_SHORT).show()
                return
            }
            branches[sel - 1].id
        }
        val dateStr = dateEdit.text.toString().trim()
        if (dateStr.isEmpty()) {
            Toast.makeText(this, getString(R.string.stocktake_date), Toast.LENGTH_SHORT).show()
            return
        }
        val notes = notesEdit.text.toString().trim().takeIf { it.isNotEmpty() }
        val items = mutableListOf<Pair<String, Int>>()
        for (i in 0 until productsContainer.childCount) {
            val child = productsContainer.getChildAt(i)
            val sp = child.findViewById<Spinner>(R.id.row_product)
            val pos = sp.selectedItemPosition
            if (pos < 1) continue
            val qtyStr = child.findViewById<EditText>(R.id.row_opening_stock).text.toString().trim()
            val qty = qtyStr.toIntOrNull() ?: 0
            if (qty < 0) continue
            items.add(products[pos - 1].id to qty)
        }
        val token = sessionManager.token ?: return
        progressBar.visibility = android.view.View.VISIBLE
        Thread {
            val result = ApiClient.createStockTake(token, branchId, dateStr, notes, if (items.isEmpty()) null else items)
            runOnUiThread {
                progressBar.visibility = android.view.View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data.first, Toast.LENGTH_SHORT).show()
                        setResult(RESULT_OK, Intent().putExtra(StockTakeListActivity.EXTRA_OPEN_STOCK_TAKE_ID, result.data.second.id))
                        finish()
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }
}

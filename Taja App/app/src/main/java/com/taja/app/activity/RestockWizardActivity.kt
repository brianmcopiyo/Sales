package com.taja.app.activity

import android.app.DatePickerDialog
import android.os.Bundle
import androidx.core.content.ContextCompat
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

class RestockWizardActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private var step = 1
    private var branches: List<ApiClient.RestockBranch> = emptyList()
    private var products: List<ApiClient.RestockProduct> = emptyList()
    private var userBranchId: String? = null

    private lateinit var step1Layout: View
    private lateinit var step2Layout: View
    private lateinit var step3Layout: View
    private lateinit var branchContainer: View
    private lateinit var branchSpinner: Spinner
    private lateinit var referenceEdit: EditText
    private lateinit var dealershipEdit: EditText
    private lateinit var expectedAtEdit: EditText
    private lateinit var productRowsContainer: LinearLayout
    private lateinit var addProductBtn: Button
    private lateinit var summaryOrder: TextView
    private lateinit var summaryProducts: TextView
    private lateinit var backBtn: Button
    private lateinit var nextBtn: Button
    private lateinit var submitBtn: Button
    private lateinit var progressBar: ProgressBar
    private lateinit var loadingMessage: TextView
    private lateinit var step1Dot: TextView
    private lateinit var step2Dot: TextView
    private lateinit var step3Dot: TextView
    private lateinit var connector1_2: View
    private lateinit var connector2_3: View

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_restock_wizard)
        sessionManager = SessionManager(this)
        userBranchId = intent.getStringExtra(EXTRA_USER_BRANCH_ID)?.takeIf { it.isNotEmpty() }
        bindViews()
        findViewById<Button>(R.id.back_button).setOnClickListener { finish() }
        backBtn.setOnClickListener { step--; updateStep() }
        nextBtn.setOnClickListener { onNext() }
        submitBtn.setOnClickListener { onSubmit() }
        addProductBtn.setOnClickListener { addProductRow() }
        loadData()
    }

    private fun bindViews() {
        step1Layout = findViewById(R.id.step1_layout)
        step2Layout = findViewById(R.id.step2_layout)
        step3Layout = findViewById(R.id.step3_layout)
        branchContainer = findViewById(R.id.restock_branch_container)
        branchSpinner = findViewById(R.id.restock_branch_spinner)
        referenceEdit = findViewById(R.id.restock_reference)
        dealershipEdit = findViewById(R.id.restock_dealership)
        expectedAtEdit = findViewById(R.id.restock_expected_at)
        productRowsContainer = findViewById(R.id.restock_product_rows)
        addProductBtn = findViewById(R.id.restock_add_product)
        summaryOrder = findViewById(R.id.restock_summary_order)
        summaryProducts = findViewById(R.id.restock_summary_products)
        backBtn = findViewById(R.id.restock_back)
        nextBtn = findViewById(R.id.restock_next)
        submitBtn = findViewById(R.id.restock_submit)
        progressBar = findViewById(R.id.restock_progress)
        loadingMessage = findViewById(R.id.restock_loading_message)
        step1Dot = findViewById(R.id.step1_dot)
        step2Dot = findViewById(R.id.step2_dot)
        step3Dot = findViewById(R.id.step3_dot)
        connector1_2 = findViewById(R.id.connector_1_2)
        connector2_3 = findViewById(R.id.connector_2_3)
        expectedAtEdit.setOnClickListener { showDatePicker() }
    }

    private fun showDatePicker() {
        val cal = java.util.Calendar.getInstance()
        val dialog = DatePickerDialog(
            this,
            { _, year, month, dayOfMonth ->
                val mm = (month + 1).toString().padStart(2, '0')
                val dd = dayOfMonth.toString().padStart(2, '0')
                expectedAtEdit.setText("$year-$mm-$dd")
            },
            cal.get(java.util.Calendar.YEAR),
            cal.get(java.util.Calendar.MONTH),
            cal.get(java.util.Calendar.DAY_OF_MONTH)
        )
        dialog.show()
    }

    private fun loadData() {
        val token = sessionManager.token ?: run { finish(); return }
        loadingMessage.text = getString(R.string.loading_loading)
        setLoading(true)
        Thread {
            val branchesResult = if (userBranchId == null) ApiClient.getRestockBranches(token) else null
            val productsResult = ApiClient.getRestockProducts(token)
            runOnUiThread {
                setLoading(false)
                if (userBranchId == null && branchesResult != null) {
                    when (branchesResult) {
                        is ApiClient.ApiResult.Success -> {
                            branches = branchesResult.data.branches
                            branchContainer.visibility = View.VISIBLE
                            branchSpinner.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item,
                                listOf(getString(R.string.restock_select_branch)) + branches.map { "${it.name} (${it.code})" })
                            branchSpinner.setSelection(0)
                        }
                        is ApiClient.ApiResult.Error -> {
                            Toast.makeText(this, branchesResult.message, Toast.LENGTH_LONG).show()
                            if (branchesResult.code == 403) finish()
                        }
                    }
                }
                when (productsResult) {
                    is ApiClient.ApiResult.Success -> {
                        products = productsResult.data.products
                        if (productRowsContainer.childCount == 0) addProductRow()
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, productsResult.message, Toast.LENGTH_LONG).show()
                        if (productsResult.code == 403) finish()
                    }
                }
            }
        }.start()
    }

    private fun addProductRow() {
        val row = LayoutInflater.from(this).inflate(R.layout.item_restock_product_row, productRowsContainer, false)
        val productSpinner = row.findViewById<Spinner>(R.id.row_product)
        val names = products.map { "${it.name} (${it.sku})" }
        val adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, mutableListOf<String>().apply { add(getString(R.string.restock_select_product)); addAll(names) })
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        productSpinner.adapter = adapter
        productSpinner.setSelection(0)
        row.findViewById<Button>(R.id.row_remove).setOnClickListener {
            if (productRowsContainer.childCount > 1) productRowsContainer.removeView(row)
        }
        productRowsContainer.addView(row)
    }

    private fun onNext() {
        if (step == 1) {
            if (userBranchId == null) {
                val sel = branchSpinner.selectedItemPosition
                if (sel <= 0) {
                    Toast.makeText(this, getString(R.string.restock_select_branch), Toast.LENGTH_SHORT).show()
                    return
                }
            }
        }
        if (step == 2) {
            val (ids, qties, costs) = getProductData()
            if (ids.isEmpty()) {
                Toast.makeText(this, getString(R.string.restock_select_product), Toast.LENGTH_SHORT).show()
                return
            }
            if (qties.any { it < 1 }) {
                Toast.makeText(this, "Quantity must be at least 1", Toast.LENGTH_SHORT).show()
                return
            }
            buildSummary()
        }
        step++
        updateStep()
    }

    private fun getProductData(): Triple<List<String>, List<Int>, List<Double?>> {
        val ids = mutableListOf<String>()
        val qties = mutableListOf<Int>()
        val costs = mutableListOf<Double?>()
        for (i in 0 until productRowsContainer.childCount) {
            val row = productRowsContainer.getChildAt(i)
            val productSpinner = row.findViewById<Spinner>(R.id.row_product)
            val qtyEdit = row.findViewById<EditText>(R.id.row_quantity)
            val costEdit = row.findViewById<EditText>(R.id.row_cost)
            val pos = productSpinner.selectedItemPosition
            if (pos <= 0) continue
            val product = products[pos - 1]
            ids.add(product.id)
            qties.add((qtyEdit.text.toString().toIntOrNull() ?: 1).coerceAtLeast(1))
            costs.add(costEdit.text.toString().toDoubleOrNull())
        }
        return Triple(ids, qties, costs)
    }

    private fun buildSummary() {
        val noVal = getString(R.string.restock_review_no_value)
        val branchName = if (userBranchId != null) getString(R.string.restock_branch) + ": Your branch" else run {
            val sel = branchSpinner.selectedItemPosition
            if (sel > 0) {
                val b = branches[sel - 1]
                getString(R.string.restock_branch) + ": ${b.name} (${b.code})"
            } else getString(R.string.restock_branch) + ": $noVal"
        }
        val ref = referenceEdit.text.toString().trim()
        val deal = dealershipEdit.text.toString().trim()
        val exp = expectedAtEdit.text.toString().trim()
        summaryOrder.text = listOf(
            branchName,
            getString(R.string.restock_reference).replace(" (optional)", "") + ": ${ref.ifEmpty { noVal }}",
            getString(R.string.restock_dealership).replace(" (optional)", "") + ": ${deal.ifEmpty { noVal }}",
            getString(R.string.restock_expected_date).replace(" (optional)", "") + ": ${exp.ifEmpty { noVal }}"
        ).joinToString("\n")

        val (ids, qties, costs) = getProductData()
        val productLines = ids.mapIndexed { i, id ->
            val p = products.find { it.id == id } ?: return@mapIndexed ""
            val costStr = costs.getOrNull(i)?.let { getString(R.string.restock_review_cost) + ": $it" } ?: getString(R.string.restock_review_cost) + ": $noVal"
            "• ${p.name} (${p.sku})\n  ${getString(R.string.restock_quantity)}: ${qties[i]}  |  $costStr"
        }.filter { it.isNotEmpty() }
        summaryProducts.text = if (productLines.isEmpty()) noVal else productLines.joinToString("\n\n")
    }

    private fun updateStep() {
        step1Layout.visibility = if (step == 1) View.VISIBLE else View.GONE
        step2Layout.visibility = if (step == 2) View.VISIBLE else View.GONE
        step3Layout.visibility = if (step == 3) View.VISIBLE else View.GONE
        backBtn.visibility = if (step > 1) View.VISIBLE else View.GONE
        nextBtn.visibility = if (step < 3) View.VISIBLE else View.GONE
        submitBtn.visibility = if (step == 3) View.VISIBLE else View.GONE
        val activeBg = R.drawable.restock_step_active_bg
        val inactiveBg = R.drawable.restock_step_inactive_bg
        val activeText = ContextCompat.getColor(this, R.color.restock_step_active_text)
        val inactiveText = ContextCompat.getColor(this, R.color.restock_step_inactive_text)
        step1Dot.setBackgroundResource(if (step >= 1) activeBg else inactiveBg)
        step1Dot.setTextColor(if (step >= 1) activeText else inactiveText)
        step2Dot.setBackgroundResource(if (step >= 2) activeBg else inactiveBg)
        step2Dot.setTextColor(if (step >= 2) activeText else inactiveText)
        step3Dot.setBackgroundResource(if (step >= 3) activeBg else inactiveBg)
        step3Dot.setTextColor(if (step >= 3) activeText else inactiveText)
        connector1_2.setBackgroundResource(if (step >= 2) R.drawable.restock_step_connector_active else R.drawable.restock_step_connector_inactive)
        connector2_3.setBackgroundResource(if (step >= 3) R.drawable.restock_step_connector_active else R.drawable.restock_step_connector_inactive)
    }

    private fun onSubmit() {
        val branchId = userBranchId ?: run {
            val sel = branchSpinner.selectedItemPosition
            if (sel <= 0) return
            branches[sel - 1].id
        }
        val (productIds, quantities, costs) = getProductData()
        if (productIds.isEmpty()) return
        val token = sessionManager.token ?: return
        val ref = referenceEdit.text.toString().trim().takeIf { it.isNotEmpty() }
        val deal = dealershipEdit.text.toString().trim().takeIf { it.isNotEmpty() }
        val expected = expectedAtEdit.text.toString().trim().takeIf { it.isNotEmpty() }
        loadingMessage.text = getString(R.string.loading_creating_order)
        setLoading(true)
        Thread {
            val result = ApiClient.createRestockOrder(token, branchId, ref, deal, expected, productIds, quantities, costs)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        val bottomSheet = BottomSheetDialog(this)
                        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_confirm, null)
                        bottomSheet.setContentView(sheetView)
                        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_title).text = getString(R.string.restock_success)
                        sheetView.findViewById<TextView>(R.id.bottomsheet_confirm_message).text = result.data.message
                        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_cancel).visibility = View.GONE
                        sheetView.findViewById<Button>(R.id.bottomsheet_confirm_ok).apply {
                            text = getString(android.R.string.ok)
                            setOnClickListener {
                                bottomSheet.dismiss()
                                finish()
                            }
                        }
                        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
                        bottomSheet.show()
                    }
                    is ApiClient.ApiResult.Error -> Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                }
            }
        }.start()
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        loadingMessage.visibility = if (loading) View.VISIBLE else View.GONE
        nextBtn.isEnabled = !loading
        submitBtn.isEnabled = !loading
        backBtn.isEnabled = !loading
        addProductBtn.isEnabled = !loading
    }

    companion object {
        const val EXTRA_USER_BRANCH_ID = "user_branch_id"
    }
}

package com.kimaro.activity

import android.content.Intent
import android.os.Bundle
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.google.android.material.navigation.NavigationBarView
import androidx.appcompat.app.AppCompatActivity
import android.view.LayoutInflater
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import com.kimaro.ApiClient
import com.kimaro.R
import com.kimaro.SessionManager
import java.io.BufferedReader
import java.io.InputStreamReader

class PendingOrdersActivity : AppCompatActivity() {

    companion object {
        private const val REQUEST_IMEI_FILE = 1001
        private const val REQUEST_IMEI_SCAN = 1002
    }

    private lateinit var sessionManager: SessionManager
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var listContainer: LinearLayout

    private var orders: List<ApiClient.PendingRestockOrder> = emptyList()
    private var currentReceiveImeiEdit: EditText? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_pending_orders)
        sessionManager = SessionManager(this)
        bindViews()
        setupBottomNav()
        loadOrders()
    }

    private fun bindViews() {
        progressBar = findViewById(R.id.pending_orders_progress)
        emptyText = findViewById(R.id.pending_orders_empty)
        listContainer = findViewById(R.id.pending_orders_list)
        findViewById<Button>(R.id.back_button).setOnClickListener { finish() }
    }

    private fun setupBottomNav() {
        val bottomNav = findViewById<NavigationBarView>(R.id.bottom_navigation)
        bottomNav.selectedItemId = R.id.nav_stock
        bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_dashboard -> {
                    startActivity(Intent(this, DashboardActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_stock -> true
                R.id.nav_stock_takes -> {
                    startActivity(Intent(this, StockTakeListActivity::class.java))
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

    private fun loadOrders() {
        val token = sessionManager.token ?: run { finish(); return }
        setLoading(true)
        emptyText.visibility = View.GONE
        listContainer.removeAllViews()
        Thread {
            val result = ApiClient.getRestockOrdersPending(token)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        orders = result.data.orders
                        if (orders.isEmpty()) {
                            emptyText.visibility = View.VISIBLE
                        } else {
                            emptyText.visibility = View.GONE
                            orders.forEach { order -> addOrderView(order) }
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

    private fun addOrderView(order: ApiClient.PendingRestockOrder) {
        val row = LayoutInflater.from(this).inflate(R.layout.item_pending_order, listContainer, false)
        row.findViewById<TextView>(R.id.item_order_number).text = order.order_number
        row.findViewById<TextView>(R.id.item_order_product).text =
            listOfNotNull(order.product_name, order.product_sku?.let { "($it)" }).joinToString(" ")
        row.findViewById<TextView>(R.id.item_order_branch).text =
            listOfNotNull(order.branch_name, order.branch_code?.let { "($it)" }).joinToString(" ")
        row.findViewById<TextView>(R.id.item_order_qty).text =
            getString(R.string.restock_quantity) + ": " + order.quantity_outstanding

        row.findViewById<Button>(R.id.item_btn_edit_quantity).setOnClickListener { onEditQuantity(order) }
        row.findViewById<Button>(R.id.item_btn_mark_complete).setOnClickListener { onMarkComplete(order, row) }
        row.findViewById<Button>(R.id.item_btn_approve_partial).setOnClickListener { onApprovePartial(order, row) }
        row.findViewById<Button>(R.id.item_btn_approve_full).setOnClickListener { onApproveFull(order, row) }
        row.findViewById<Button>(R.id.item_btn_reject).setOnClickListener { onReject(order, row) }
        listContainer.addView(row)
    }

    private fun onEditQuantity(order: ApiClient.PendingRestockOrder) {
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_edit_quantity, null)
        bottomSheet.setContentView(sheetView)

        val minQty = order.quantity_received
        sheetView.findViewById<TextView>(R.id.edit_quantity_title).text = getString(R.string.edit_quantity_title)
        sheetView.findViewById<TextView>(R.id.edit_quantity_message).text =
            getString(R.string.edit_quantity_message, order.order_number)
        val qtyEdit = sheetView.findViewById<EditText>(R.id.edit_quantity_input)
        qtyEdit.setText(order.quantity_ordered.toString())
        sheetView.findViewById<TextView>(R.id.edit_quantity_minimum).text =
            getString(R.string.edit_quantity_minimum, minQty)
        val passwordEdit = sheetView.findViewById<EditText>(R.id.edit_quantity_password)

        sheetView.findViewById<Button>(R.id.edit_quantity_cancel).setOnClickListener { bottomSheet.dismiss() }
        sheetView.findViewById<Button>(R.id.edit_quantity_confirm).setOnClickListener {
            val qtyStr = qtyEdit.text.toString().trim()
            val pwdStr = passwordEdit.text.toString()
            val newQty = qtyStr.toIntOrNull() ?: 0
            if (newQty < minQty || newQty > 99999) {
                Toast.makeText(
                    this,
                    getString(R.string.edit_quantity_minimum, minQty),
                    Toast.LENGTH_SHORT
                ).show()
                return@setOnClickListener
            }
            if (pwdStr.isBlank()) {
                Toast.makeText(this, getString(R.string.error_password_required), Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            bottomSheet.dismiss()
            doUpdateQuantity(order, newQty, pwdStr)
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }

    private fun onApprovePartial(order: ApiClient.PendingRestockOrder, row: View) {
        val maxQty = order.quantity_outstanding
        val qtyHint = getString(R.string.orders_partial_quantity_hint, maxQty)
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_receive_stock, null)
        bottomSheet.setContentView(sheetView)

        sheetView.findViewById<TextView>(R.id.receive_product_name).text = order.product_name ?: ""
        sheetView.findViewById<TextView>(R.id.receive_order_number).text = getString(R.string.receive_order_label, order.order_number)
        val qtyEdit = sheetView.findViewById<EditText>(R.id.receive_quantity)
        qtyEdit.hint = qtyHint
        sheetView.findViewById<TextView>(R.id.receive_quantity_max).text = getString(R.string.receive_quantity_max, maxQty)

        val imeiEdit = sheetView.findViewById<EditText>(R.id.receive_imeis)
        sheetView.findViewById<Button>(R.id.receive_imei_upload_btn).setOnClickListener {
            currentReceiveImeiEdit = imeiEdit
            startActivityForResult(
                Intent(Intent.ACTION_GET_CONTENT).setType("text/*").addCategory(Intent.CATEGORY_OPENABLE),
                REQUEST_IMEI_FILE
            )
        }
        sheetView.findViewById<Button>(R.id.receive_imei_scan_btn).setOnClickListener {
            currentReceiveImeiEdit = imeiEdit
            startActivityForResult(
                Intent(this, ScanImeiActivity::class.java),
                REQUEST_IMEI_SCAN
            )
        }

        sheetView.findViewById<Button>(R.id.receive_cancel).setOnClickListener { bottomSheet.dismiss() }
        sheetView.findViewById<Button>(R.id.receive_confirm).setOnClickListener {
            val qtyStr = qtyEdit.text.toString().trim()
            val qty = qtyStr.toIntOrNull() ?: 0
            if (qty < 1 || qty > maxQty) {
                Toast.makeText(this, qtyHint, Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            bottomSheet.dismiss()
            val notes = sheetView.findViewById<EditText>(R.id.receive_notes).text.toString().trim().takeIf { it.isNotEmpty() }
            val imeis = sheetView.findViewById<EditText>(R.id.receive_imeis).text.toString().trim().takeIf { it.isNotEmpty() }
            val markComplete = sheetView.findViewById<android.widget.CheckBox>(R.id.receive_mark_complete).isChecked
            doReceive(order, row, qty, markComplete, notes, imeis)
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        val edit = currentReceiveImeiEdit
        if (edit == null) return
        if (requestCode == REQUEST_IMEI_FILE && resultCode == RESULT_OK && data?.data != null) {
            currentReceiveImeiEdit = null
            try {
                contentResolver.openInputStream(data.data!!)?.use { stream ->
                    val lines = BufferedReader(InputStreamReader(stream)).readLines()
                    val imeis = mutableListOf<String>()
                    for (line in lines) {
                        val trimmed = line.trim()
                        if (trimmed.isEmpty()) continue
                        val first = trimmed.split(",").firstOrNull()?.trim() ?: trimmed
                        if (first.equals("imei", ignoreCase = true)) {
                            continue
                        }
                        if (first.isNotEmpty()) imeis.add(first)
                    }
                    appendUniqueImeis(edit, imeis)
                }
            } catch (e: Exception) {
                Toast.makeText(this, getString(R.string.receive_imei_file_error), Toast.LENGTH_SHORT).show()
            }
        } else if (requestCode == REQUEST_IMEI_SCAN && resultCode == RESULT_OK) {
            currentReceiveImeiEdit = null
            val scanned = data?.getStringExtra("scanned_imeis") ?: ""
            if (scanned.isNotBlank()) {
                val imeis = scanned.split("\n").map { it.trim() }.filter { it.isNotEmpty() }
                appendUniqueImeis(edit, imeis)
            }
        }
    }

    private fun appendUniqueImeis(edit: EditText, newImeis: List<String>) {
        val existingLines = edit.text.toString()
            .split("\n", ",")
            .map { it.trim() }
            .filter { it.isNotEmpty() }
        val existingSet = mutableSetOf<String>()
        existingLines.forEach { line ->
            val norm = normalizeImei(line)
            if (norm.isNotEmpty()) {
                existingSet.add(norm)
            }
        }
        val toAdd = mutableListOf<String>()
        val keptExisting = mutableListOf<String>()
        for (line in existingLines) {
            val norm = normalizeImei(line)
            if (norm.isNotEmpty() && !keptExisting.contains(norm)) {
                keptExisting.add(norm)
            }
        }
        for (imei in newImeis) {
            val norm = normalizeImei(imei)
            if (norm.isNotEmpty() && !existingSet.contains(norm)) {
                existingSet.add(norm)
                toAdd.add(norm)
            }
        }
        if (toAdd.isEmpty()) return
        val all = (keptExisting + toAdd).joinToString("\n")
        edit.setText(all)
    }

    private fun normalizeImei(raw: String): String {
        val digits = raw.filter { it.isDigit() }
        return if (digits.length == 15 && isValidImei(digits)) digits else ""
    }

    private fun isValidImei(imei: String): Boolean {
        if (imei.length != 15 || !imei.all { it.isDigit() }) return false
        var sum = 0
        for (i in 0 until 14) {
            var n = imei[14 - i] - '0'
            if (i % 2 == 0) {
                n *= 2
                if (n > 9) n -= 9
            }
            sum += n
        }
        val checkDigit = (10 - (sum % 10)) % 10
        return checkDigit == (imei[14] - '0')
    }

    private fun doReceive(
        order: ApiClient.PendingRestockOrder,
        row: View,
        quantity: Int,
        markComplete: Boolean,
        notes: String? = null,
        imeis: String? = null
    ) {
        val token = sessionManager.token ?: return
        row.isEnabled = false
        Thread {
            val result = ApiClient.receiveRestockOrder(token, order.id, quantity, markComplete, notes, imeis)
            runOnUiThread {
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        listContainer.removeView(row)
                    }
                    is ApiClient.ApiResult.Error -> {
                        row.isEnabled = true
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun onApproveFull(order: ApiClient.PendingRestockOrder, row: View) {
        val maxQty = order.quantity_outstanding
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_receive_stock, null)
        bottomSheet.setContentView(sheetView)

        sheetView.findViewById<TextView>(R.id.receive_sheet_title).text = getString(R.string.approve_full_order_title)
        sheetView.findViewById<TextView>(R.id.receive_product_name).text = order.product_name ?: ""
        sheetView.findViewById<TextView>(R.id.receive_order_number).text = getString(R.string.receive_order_label, order.order_number)
        val qtyEdit = sheetView.findViewById<EditText>(R.id.receive_quantity)
        qtyEdit.setText(maxQty.toString())
        qtyEdit.isEnabled = false
        qtyEdit.isFocusable = false
        sheetView.findViewById<TextView>(R.id.receive_quantity_max).text = getString(R.string.receive_quantity_max, maxQty)
        sheetView.findViewById<View>(R.id.receive_mark_complete).visibility = View.GONE

        val imeiEdit = sheetView.findViewById<EditText>(R.id.receive_imeis)
        sheetView.findViewById<Button>(R.id.receive_imei_upload_btn).setOnClickListener {
            currentReceiveImeiEdit = imeiEdit
            startActivityForResult(
                Intent(Intent.ACTION_GET_CONTENT).setType("text/*").addCategory(Intent.CATEGORY_OPENABLE),
                REQUEST_IMEI_FILE
            )
        }
        sheetView.findViewById<Button>(R.id.receive_imei_scan_btn).setOnClickListener {
            currentReceiveImeiEdit = imeiEdit
            startActivityForResult(
                Intent(this, ScanImeiActivity::class.java),
                REQUEST_IMEI_SCAN
            )
        }

        sheetView.findViewById<Button>(R.id.receive_cancel).setOnClickListener { bottomSheet.dismiss() }
        sheetView.findViewById<Button>(R.id.receive_confirm).apply {
            text = getString(R.string.approve_button)
            setOnClickListener {
                bottomSheet.dismiss()
                val imeis = sheetView.findViewById<EditText>(R.id.receive_imeis).text.toString().trim().takeIf { it.isNotEmpty() }
                doApproveFull(order, row, imeis)
            }
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }

    private fun doApproveFull(order: ApiClient.PendingRestockOrder, row: View, imeis: String?) {
        val token = sessionManager.token ?: return
        row.isEnabled = false
        Thread {
            val result = ApiClient.approveRestockOrder(token, order.id, imeis)
            runOnUiThread {
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        listContainer.removeView(row)
                    }
                    is ApiClient.ApiResult.Error -> {
                        row.isEnabled = true
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun onReject(order: ApiClient.PendingRestockOrder, row: View) {
        val bottomSheet = BottomSheetDialog(this)
        val sheetView = LayoutInflater.from(this).inflate(R.layout.bottomsheet_reject_order, null)
        bottomSheet.setContentView(sheetView)
        val reasonEdit = sheetView.findViewById<EditText>(R.id.bottomsheet_reject_reason)
        sheetView.findViewById<Button>(R.id.bottomsheet_reject_cancel).setOnClickListener { bottomSheet.dismiss() }
        sheetView.findViewById<Button>(R.id.bottomsheet_reject_confirm).setOnClickListener {
            bottomSheet.dismiss()
            val reason = reasonEdit.text.toString().trim().takeIf { it.isNotEmpty() }
            doReject(order, row, reason)
        }
        bottomSheet.window?.setBackgroundDrawableResource(android.R.color.transparent)
        bottomSheet.show()
    }

    private fun onMarkComplete(order: ApiClient.PendingRestockOrder, row: View) {
        doMarkComplete(order, row)
    }

    private fun doMarkComplete(order: ApiClient.PendingRestockOrder, row: View) {
        val token = sessionManager.token ?: return
        row.isEnabled = false
        Thread {
            val result = ApiClient.markRestockOrderComplete(token, order.id)
            runOnUiThread {
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        listContainer.removeView(row)
                    }
                    is ApiClient.ApiResult.Error -> {
                        row.isEnabled = true
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun doUpdateQuantity(order: ApiClient.PendingRestockOrder, newQuantity: Int, password: String) {
        val token = sessionManager.token ?: return
        setLoading(true)
        Thread {
            val result = ApiClient.updateRestockOrderQuantity(token, order.id, newQuantity, password)
            runOnUiThread {
                setLoading(false)
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        // Reload list so quantities and outstanding values are updated.
                        loadOrders()
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun doReject(order: ApiClient.PendingRestockOrder, row: View, reason: String?) {
        val token = sessionManager.token ?: return
        row.isEnabled = false
        Thread {
            val result = ApiClient.rejectRestockOrder(token, order.id, reason)
            runOnUiThread {
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        Toast.makeText(this, result.data, Toast.LENGTH_SHORT).show()
                        listContainer.removeView(row)
                    }
                    is ApiClient.ApiResult.Error -> {
                        row.isEnabled = true
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                    }
                }
            }
        }.start()
    }

    private fun setLoading(loading: Boolean) {
        progressBar.visibility = if (loading) View.VISIBLE else View.GONE
    }
}

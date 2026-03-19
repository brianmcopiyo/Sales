package com.taja.outlet.activity

import android.content.Intent
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.ActivityResultLauncher
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.bottomnavigation.BottomNavigationView
import com.taja.outlet.ApiClient
import com.taja.outlet.R
import com.taja.outlet.SessionManager
import java.util.Locale

class OutletListActivity : AppCompatActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var progressBar: ProgressBar
    private lateinit var emptyText: TextView
    private lateinit var listContainer: LinearLayout
    private lateinit var newButton: Button
    private lateinit var filterInput: EditText
    private lateinit var bottomNav: BottomNavigationView
    private lateinit var outletFormLauncher: ActivityResultLauncher<Intent>
    private var allOutlets: List<ApiClient.Outlet> = emptyList()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_outlet_list)
        sessionManager = SessionManager(this)
        if (!sessionManager.isLoggedIn) {
            Toast.makeText(this, R.string.error_session, Toast.LENGTH_SHORT).show()
            finish()
            return
        }
        findViewById<Button>(R.id.stocktake_list_back).setOnClickListener { finish() }
        progressBar = findViewById(R.id.stocktake_list_progress)
        emptyText = findViewById(R.id.stocktake_list_empty)
        listContainer = findViewById(R.id.stocktake_list_container)
        newButton = findViewById(R.id.stocktake_list_new_btn)
        filterInput = findViewById(R.id.stocktake_list_filter)
        bottomNav = findViewById(R.id.bottom_navigation)

        bottomNav.selectedItemId = R.id.nav_outlets
        bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_map -> {
                    startActivity(Intent(this, OutletMapActivity::class.java))
                    finish()
                    false
                }
                R.id.nav_outlets -> true
                R.id.nav_profile -> {
                    Toast.makeText(this, R.string.error_session, Toast.LENGTH_SHORT).show()
                    false
                }
                else -> false
            }
        }

        outletFormLauncher = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            if (result.resultCode == RESULT_OK) {
                loadOutlets()
            }
        }

        loadOutlets()
        newButton.setOnClickListener { outletFormLauncher.launch(Intent(this, OutletFormActivity::class.java)) }
        filterInput.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) = Unit
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) = Unit
            override fun afterTextChanged(s: Editable?) {
                renderOutlets(applyFilter(allOutlets, s?.toString()))
            }
        })
    }

    private fun loadOutlets() {
        progressBar.visibility = android.view.View.VISIBLE
        emptyText.visibility = View.GONE
        listContainer.visibility = View.GONE
        listContainer.removeAllViews()
        Thread {
            val token = sessionManager.token ?: ""
            if (token.isBlank()) {
                runOnUiThread {
                    progressBar.visibility = View.GONE
                    emptyText.visibility = View.VISIBLE
                    listContainer.visibility = View.GONE
                }
                return@Thread
            }
            val result = ApiClient.getOutlets(token)
            runOnUiThread {
                progressBar.visibility = View.GONE
                when (result) {
                    is ApiClient.ApiResult.Success -> {
                        allOutlets = result.data
                        renderOutlets(applyFilter(allOutlets, filterInput.text?.toString()))
                    }
                    is ApiClient.ApiResult.Error -> {
                        Toast.makeText(this, result.message, Toast.LENGTH_LONG).show()
                        emptyText.visibility = View.VISIBLE
                        listContainer.visibility = View.GONE
                    }
                }
            }
        }.start()
    }

    private fun applyFilter(outlets: List<ApiClient.Outlet>, query: String?): List<ApiClient.Outlet> {
        val q = query?.trim()?.lowercase(Locale.getDefault()).orEmpty()
        if (q.isEmpty()) return outlets
        return outlets.filter { outlet ->
            outlet.name.lowercase(Locale.getDefault()).contains(q) ||
                outlet.code?.lowercase(Locale.getDefault())?.contains(q) == true ||
                outlet.address?.lowercase(Locale.getDefault())?.contains(q) == true
        }
    }

    private fun renderOutlets(outlets: List<ApiClient.Outlet>) {
        listContainer.removeAllViews()
        if (outlets.isEmpty()) {
            emptyText.visibility = View.VISIBLE
            listContainer.visibility = View.GONE
            return
        }
        emptyText.visibility = View.GONE
        listContainer.visibility = View.VISIBLE
        val inflater = LayoutInflater.from(this)
        outlets.forEach { outlet ->
            val row = inflater.inflate(R.layout.item_outlet_row, listContainer, false)
            row.findViewById<TextView>(R.id.item_outlet_name).text = outlet.name
            val codeText = row.findViewById<TextView>(R.id.item_outlet_code)
            val code = outlet.code?.trim().orEmpty()
            if (code.isNotEmpty()) {
                codeText.text = code
                codeText.visibility = View.VISIBLE
            } else {
                codeText.visibility = View.GONE
            }
            val addressText = row.findViewById<TextView>(R.id.item_outlet_address)
            val address = outlet.address?.trim().orEmpty()
            addressText.text = if (address.isNotEmpty()) address else "—"
            row.setOnClickListener {
                outletFormLauncher.launch(
                    Intent(this, OutletDetailActivity::class.java)
                        .putExtra(OutletDetailActivity.EXTRA_OUTLET_ID, outlet.id)
                )
            }
            listContainer.addView(row)
        }
    }
}
